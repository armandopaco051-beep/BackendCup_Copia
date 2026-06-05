<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class CredencialesPostulanteMail extends Mailable
{
    public function __construct(
        public string $nombre,
        public string $username,
        public string $passwordTemporal,
    ) {
    }

    public function build(): self
    {
        return $this->subject('Credenciales de acceso CUP - UAGRM')
            ->view('emails.credenciales-postulante');
    }
}
