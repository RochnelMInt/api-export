<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PurchaseMail extends Mailable
{
    use Queueable, SerializesModels;

    public $details;
    public $files;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details, $files)
    {
        $this->details = $details;
        $this->files = $files;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        //return $this->subject('PURCHASE STATUS MEDIA INTELLIGENCE')->view('mail.send-mail');

        $email = $this->subject('PURCHASE STATUS MEDIA INTELLIGENCE')
            ->view('mail.send-mail')
            ->with([
                'details' => $this->details,
            ]);
            
        // if ($this->article_file) {
        //     $email->attach(Storage::path("articles/" . $this->article_file), [
        //         'as' => basename($this->article_file),
        //         'mime' => Storage::mimeType("articles/" . $this->article_file),
        //     ]);
        // }

        if ($this->files) {
            foreach ($this->files as $file) {
                $email->attach(Storage::path("articles/" . $file['path']), [
                    'as' => basename($file['path']),
                    'mime' => Storage::mimeType("articles/" . $file['path']),
                ]);
            }

        }


        return $email;
    }
}
