<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySalesReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{product_id:int, product_name:string, quantity_sold:int, revenue_cents:int}>  $items
     */
    public function __construct(
        public readonly string $reportDate,
        public readonly array $items,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Daily Sales Report ({$this->reportDate})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-sales-report',
            with: [
                'reportDate' => $this->reportDate,
                'items' => $this->items,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
