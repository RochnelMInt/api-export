<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientAccountCreation extends Mailable
{
    use Queueable, SerializesModels;

    public $details;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $details = [
            'title' => 'Création de compte client',
            'email' => 'john.doe@example.com',
            'password' => 'motdepasse',
        ];
        //return $this->subject('Création de compte client')->view('mail.client-account-creation')->with('details', $details);

        return $this->subject('Création de compte client')->view('mail.client-creation', ['details' => $details]);
        }
}
