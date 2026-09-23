<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class PostpaidInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $invoice;

    /**
     * Create a new message instance.
     *
     * @param array $invoice
     */
    public function __construct(array $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre facture de recharge EVON #' . $this->invoice['invoice_number'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.postpaid-invoice',
            with: [
                'invoice' => $this->invoice
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
        // Générer le PDF dynamique
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $this->invoice]);
        
        return [
            Attachment::fromData(fn () => $pdf->output(), 'facture_' . $this->invoice['invoice_number'] . '.pdf')
                    ->withMime('application/pdf'),
        ];
    }
}
