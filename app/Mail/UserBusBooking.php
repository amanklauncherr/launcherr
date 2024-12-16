<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserBusBooking extends Mailable
{
    use Queueable, SerializesModels;

    public $pnr, $BookingRef, $pdf_url;
    
    /**
     * Create a new message instance.
    */
    public function __construct($pnr,$BookingRef,$pdf_url)
    {
        $this->pnr = $pnr;
        $this->BookingRef = $BookingRef; 
        $this->pdf_url = $pdf_url;
    }

    public function build()
    {
        return $this->subject('User Bus Booking')
                    ->view('userBusBooking')
                    ->with([
                        'Pnr'=>$this->pnr,
                        'BookingRef'=>$this->BookingRef, 
                        'pdf_url'=>$this->pdf_url
                    ]);
    }

}
