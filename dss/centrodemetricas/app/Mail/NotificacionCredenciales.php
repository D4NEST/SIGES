<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacionCredenciales extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // Declaramos las propiedades públicas para que la vista tenga acceso automático a ellas
    public $user;
    public $password;

    /**
     * Construir la instancia pasando el Modelo User y el string de la contraseña plana
     */
    public function __construct(User $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Definir el sobre del correo (Asunto)
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenido al Centro de Métricas Nacionales - Credenciales de Acceso',
        );
    }

    /**
     * Definir el contenido mediante la plantilla Markdown
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.usuarios.bienvenida',
        );
    }

    /**
     * Definir los archivos adjuntos (opcional)
     */
    public function attachments(): array
    {
        return [];
    }
}
