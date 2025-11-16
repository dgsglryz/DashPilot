<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Mail;

use App\Modules\Alerts\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * AlertResolved mailable sends email notification when an alert is resolved.
 */
class AlertResolved extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param Alert $alert The alert that was resolved
     */
    public function __construct(public readonly Alert $alert)
    {
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $siteName = $this->alert->site?->name ?? 'Unknown Site';

        return new Envelope(
            subject: "✅ Alert Resolved: {$siteName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.alert-resolved',
            with: [
                'alert' => $this->alert,
                'site' => $this->alert->site,
                'resolver' => $this->alert->resolver,
            ],
        );
    }
}

