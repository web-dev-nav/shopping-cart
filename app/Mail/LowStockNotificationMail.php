<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $stockQuantity,
        public readonly int $lowStockThreshold,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Low stock: {$this->productName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock',
            with: [
                'productId' => $this->productId,
                'productName' => $this->productName,
                'stockQuantity' => $this->stockQuantity,
                'lowStockThreshold' => $this->lowStockThreshold,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
