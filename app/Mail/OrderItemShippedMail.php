<?php

namespace App\Mail;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderItemShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public OrderItem $orderItem;

    /**
     * Create a new message instance.
     */
    public function __construct(OrderItem $orderItem)
    {
        $this->orderItem = $orderItem;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Item from Order #' . $this->orderItem->order->order_number . ' Has Been Shipped',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-item-shipped',
            with: [
                'orderItem' => $this->orderItem,
                'order' => $this->orderItem->order,
            ],
        );
    }
}
