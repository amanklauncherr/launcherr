<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserBusTicketCancel extends Mailable
{
    use Queueable, SerializesModels;

  
    public $pnr, $BookingRef;

    /**
     * Create a new message instance.
     */
    public function __construct($pnr,$BookingRef)
    {
        //
        $this->pnr = $pnr;
        $this->BookingRef = $BookingRef; 
    }

    public function build()
    {
        return $this->subject('User Bus Ticket Cancel')
                    ->view('userBusTicketCanel')
                    ->with([
                        'Pnr'=>$this->pnr,
                        'BookingRef'=>$this->BookingRef, 
                    ]);
    }

    // /**
    //  * Get the message envelope.
    //  */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'User Bus Ticket Cancel',
    //     );
    // }

    // /**
    //  * Get the message content definition.
    //  */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

    // /**
    //  * Get the attachments for the message.
    //  *
    //  * @return array<int, \Illuminate\Mail\Mailables\Attachment>
    //  */
    // public function attachments(): array
    // {
    //     return [];
    // }
}
