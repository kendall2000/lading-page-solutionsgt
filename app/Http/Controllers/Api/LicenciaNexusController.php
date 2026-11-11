<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Licencias;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Consulta de cada instalación de Nexus: con su token (Bearer) recibe su licencia firmada con Ed25519
 * y de paso informa versión, usuarios y empresas (monitoreo). Protocolo: nexus-erp,
 * docs/instalacion-clientes/licencia.md.
 */
class LicenciaNexusController extends Controller
{
    public function __invoke(Request $request, Licencias $licencias): JsonResponse
    {
        $instalacion = $licencias->buscarPorToken((string) $request->bearerToken());
        if (! $instalacion) {
            return response()->json(['message' => 'Token no válido.'], 401);
        }
        if ($request->input('instalacion') !== $instalacion->identificador) {
            return response()->json(['message' => 'El token no es de esta instalación.'], 403);
        }

        return response()->json($licencias->responder($instalacion->load('contrato'), $request->only(['version', 'usuarios_activos', 'empresas_activas', 'etapa']), $request->ip()));
    }
}
