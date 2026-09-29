<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Contador de visitas propio (sin cookies ni servicios externos, elegido por el usuario el 2026-09-29).
 * No guarda la IP: el visitante es un hash que cambia cada día.
 */
class Visitas
{
    /** Robots, vistas previas de enlaces y herramientas: no son personas. */
    private const ROBOTS = '~bot|crawl|spider|slurp|facebookexternalhit|meta-externalagent|whatsapp|telegram|preview|embedly|'
        .'curl|wget|python|java/|okhttp|go-http|axios|node-fetch|guzzle|scrapy|headless|lighthouse|pagespeed|'
        .'monitor|uptime|pingdom|statuscake|validator|feed|http-client|postman|insomnia~i';

    /** Dominio que contiene → origen. */
    private const ORIGENES = [
        'google.' => 'google', 'bing.' => 'bing', 'yahoo.' => 'yahoo', 'duckduckgo.' => 'duckduckgo',
        'facebook.' => 'facebook', 'fb.' => 'facebook', 'instagram.' => 'instagram', 'whatsapp.' => 'whatsapp', 'wa.me' => 'whatsapp',
        'linkedin.' => 'linkedin', 'lnkd.in' => 'linkedin', 'youtube.' => 'youtube', 'tiktok.' => 'tiktok',
        't.co' => 'x', 'twitter.' => 'x', 'x.com' => 'x', 'chatgpt.' => 'chatgpt', 'openai.' => 'chatgpt',
    ];

    /** origen => [texto, color] para el panel. */
    public const NOMBRES_ORIGEN = [
        'directo' => ['Directo o escrito', 'secondary'], 'google' => ['Google', 'primary'], 'bing' => ['Bing', 'info'],
        'yahoo' => ['Yahoo', 'info'], 'duckduckgo' => ['DuckDuckGo', 'info'], 'facebook' => ['Facebook', 'primary'],
        'instagram' => ['Instagram', 'danger'], 'whatsapp' => ['WhatsApp', 'success'], 'linkedin' => ['LinkedIn', 'info'],
        'youtube' => ['YouTube', 'danger'], 'tiktok' => ['TikTok', 'secondary'], 'x' => ['X (Twitter)', 'secondary'],
        'chatgpt' => ['ChatGPT', 'success'], 'otro' => ['Otros sitios', 'warning'], 'campana' => ['Campañas (utm)', 'warning'],
    ];

    public const NOMBRES_DISPOSITIVO = ['movil' => 'Celular', 'tableta' => 'Tableta', 'escritorio' => 'Computadora'];

    public function registrar(Request $request, string $tipo, ?string $titulo): void
    {
        $agente = (string) $request->userAgent();
        if (! $this->esPersona($request, $agente)) {
            return;
        }
        [$origen, $sitio] = $this->origen($request);

        // Si falla (base ocupada, etc.), la página se muestra igual.
        rescue(fn () => DB::table('visitas')->insert([
            'visitante' => hash('sha256', $request->ip().'|'.$agente.'|'.now()->toDateString().'|'.config('app.key')),
            'ruta' => Str::limit('/'.ltrim($request->path(), '/'), 250, ''),
            'tipo' => $tipo,
            'titulo' => $titulo !== null ? Str::limit($titulo, 145) : null,
            'origen' => $origen,
            'origen_sitio' => $sitio,
            'dispositivo' => $this->dispositivo($agente),
            'navegador' => $this->navegador($agente),
            'created_at' => now(),
        ]), null, false);
    }

    private function esPersona(Request $request, string $agente): bool
    {
        // Precargas del navegador (no las vio nadie), robots y quien está en el panel (usted mismo).
        $precarga = str_contains(strtolower((string) ($request->header('Sec-Purpose') ?: $request->header('Purpose'))), 'prefetch');

        return $agente !== '' && ! $precarga && ! preg_match(self::ROBOTS, $agente) && ! $request->user();
    }

    /** @return array{0: string, 1: ?string} [origen, dominio] */
    private function origen(Request $request): array
    {
        if ($campana = Str::lower(Str::limit((string) $request->query('utm_source'), 30, ''))) {
            return [in_array($campana, array_values(self::ORIGENES), true) ? $campana : 'campana', $campana];
        }
        $host = Str::lower((string) parse_url((string) $request->headers->get('referer'), PHP_URL_HOST));
        if ($host === '') {
            return ['directo', null];
        }
        if ($host === Str::lower($request->getHost())) {
            return ['interno', null];
        }
        $host = Str::limit(preg_replace('/^(www\.|m\.|l\.|lm\.)/', '', $host), 100, '');
        foreach (self::ORIGENES as $parte => $origen) {
            if (str_starts_with($host, $parte) || str_contains($host, '.'.$parte) || $host === rtrim($parte, '.')) {
                return [$origen, $host];
            }
        }

        return ['otro', $host];
    }

    private function dispositivo(string $agente): string
    {
        return match (true) {
            (bool) preg_match('~ipad|tablet|kindle|silk|(android(?!.*mobile))~i', $agente) => 'tableta',
            (bool) preg_match('~mobi|iphone|ipod|android|blackberry|opera mini|iemobile~i', $agente) => 'movil',
            default => 'escritorio',
        };
    }

    private function navegador(string $agente): string
    {
        return match (true) {
            str_contains($agente, 'Edg/') => 'Edge',
            str_contains($agente, 'OPR/') || str_contains($agente, 'Opera') => 'Opera',
            str_contains($agente, 'SamsungBrowser') => 'Samsung',
            str_contains($agente, 'Firefox/') || str_contains($agente, 'FxiOS') => 'Firefox',
            str_contains($agente, 'Chrome/') || str_contains($agente, 'CriOS') => 'Chrome',
            str_contains($agente, 'Safari/') => 'Safari',
            default => 'Otro',
        };
    }
}
