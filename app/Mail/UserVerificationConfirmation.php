<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserVerificationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $uniqueCode;

    /**
     * Create a new message instance.
     */
    public function __construct($uniqueCode)
    {
        //
        $this->uniqueCode = $uniqueCode;
    }
    public function build()
    {
        return $this->subject('User Verification Confirmation')
                    ->view('userverificationConfimation')
                    ->with([
                        'uniqueCode' => $this->uniqueCode,
                    ]);
    }

}
