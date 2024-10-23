<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class pagoEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $estado;
    public $periodo;
    public $monto;

    /**
     * Create a new message instance.
     */
    public function __construct($estado, $periodo, $monto)
    {
        $this->estado = $estado;
        $this->periodo = $periodo;
        $this->monto = $monto;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Notificaciones Club Sincelejo',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'pagosEmail',
            with: [
                'fecha' => now()->format('d/m/Y'),
                'estado' => $this->estado,
                'periodo' => $this->periodo,
                'monto' => $this->monto
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    public function build()
    {
        return $this->subject('Notificación de Estado')
            ->from('clubsincelejo.prismau@gmail.com', 'Club Sincelejo')
            ->view('pagosEmail')
            ->with([
                'fecha' => now()->format('d/m/Y'),
                'estado' => $this->estado,
                'periodo' => $this->periodo,
                'monto' => $this->monto
            ]);
    }
}
