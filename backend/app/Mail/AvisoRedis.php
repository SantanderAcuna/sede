<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class AvisoRedis extends Mailable
{
    /** @param list<string> $senales */
    public function __construct(public array $senales) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Sede] Aviso de Redis: '.count($this->senales).' señal(es)');
    }

    public function content(): Content
    {
        return new Content(text: 'correo.aviso-redis');
    }
}
