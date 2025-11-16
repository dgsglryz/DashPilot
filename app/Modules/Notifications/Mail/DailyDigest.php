<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Mail;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * DailyDigest mailable sends a daily summary of alerts and site status.
 */
class DailyDigest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param User $user The user receiving the digest
     * @param Collection<int, Alert> $alerts Recent alerts from the last 24 hours
     * @param Collection<int, Site> $sites Sites with issues
     */
    public function __construct(
        public readonly User $user,
        public readonly Collection $alerts,
        public readonly Collection $sites
    ) {
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $date = now()->format('F j, Y');

        return new Envelope(
            subject: "DashPilot Daily Digest - {$date}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $stats = [
            'totalAlerts' => $this->alerts->count(),
            'criticalAlerts' => $this->alerts->where('severity', 'critical')->where('is_resolved', false)->count(),
            'resolvedAlerts' => $this->alerts->where('is_resolved', true)->count(),
            'sitesWithIssues' => $this->sites->count(),
        ];

        return new Content(
            view: 'emails.daily-digest',
            with: [
                'user' => $this->user,
                'alerts' => $this->alerts,
                'sites' => $this->sites,
                'stats' => $stats,
                'date' => now()->format('F j, Y'),
            ],
        );
    }
}

