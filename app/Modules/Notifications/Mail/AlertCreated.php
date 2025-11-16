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
 * AlertCreated mailable sends email notification when a critical/high severity alert is created.
 */
class AlertCreated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param Alert $alert The alert that was created
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
        $severity = $this->alert->severity === 'critical' ? '🚨 CRITICAL' : '⚠️ HIGH';
        $siteName = $this->alert->site?->name ?? 'Unknown Site';
        $subject = "[{$severity}] Alert: {$siteName}";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.alert-created',
            with: [
                'alert' => $this->alert,
                'site' => $this->alert->site,
                'severityLabel' => $this->getSeverityLabel(),
            ],
        );
    }

    /**
     * Get human-readable severity label.
     */
    private function getSeverityLabel(): string
    {
        return match ($this->alert->severity) {
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            default => 'Low',
        };
    }
}

