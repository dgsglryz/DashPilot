<?php
declare(strict_types=1);

namespace Tests\Unit\Notifications\Mail;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Notifications\Mail\AlertResolved;
use App\Modules\Sites\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertResolvedTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_resolved_mailable_has_correct_subject(): void
    {
        $site = Site::factory()->create(['name' => 'Test Site']);
        $alert = Alert::factory()->create(['site_id' => $site->id]);

        $mailable = new AlertResolved($alert);

        $this->assertStringContainsString('Alert Resolved', $mailable->envelope()->subject);
        $this->assertStringContainsString('Test Site', $mailable->envelope()->subject);
    }

    public function test_alert_resolved_mailable_uses_correct_view(): void
    {
        $alert = Alert::factory()->create();

        $mailable = new AlertResolved($alert);

        $this->assertEquals('emails.alert-resolved', $mailable->content()->view);
    }
}

