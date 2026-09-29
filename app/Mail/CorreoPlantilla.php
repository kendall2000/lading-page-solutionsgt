<?php

namespace App\Mail;

use App\Models\ConfiguracionCorreo;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Correo armado con una plantilla, dentro del marco del sistema (logo y colores). */
class CorreoPlantilla extends Mailable
{
    public function __construct(public string $asunto, public string $cuerpo, public ?string $responderA = null) {}

    public function envelope(): Envelope
    {
        $cfg = ConfiguracionCorreo::actual();

        return new Envelope(
            subject: $this->asunto,
            replyTo: match (true) {
                filled($this->responderA) => [new Address($this->responderA)],
                $cfg->is_active && filled($cfg->responder_a) => [new Address($cfg->responder_a)],
                default => [],
            },
        );
    }

    public function content(): Content
    {
        return new Content(view: 'correos.marco', with: ['cuerpo' => $this->cuerpo]);
    }
}
