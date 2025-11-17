<?php
declare(strict_types=1);

namespace Tests\Unit\Notifications\Mail;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Notifications\Mail\AlertCreated;
use App\Modules\Sites\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertCreatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_created_mailable_has_correct_subject(): void
    {
        $site = Site::factory()->create(['name' => 'Test Site']);
        $alert = Alert::factory()->create([
            'site_id' => $site->id,
            'severity' => 'critical',
        ]);

        $mailable = new AlertCreated($alert);

        $this->assertStringContainsString('CRITICAL', $mailable->envelope()->subject);
        $this->assertStringContainsString('Test Site', $mailable->envelope()->subject);
    }

    public function test_alert_created_mailable_uses_correct_view(): void
    {
        $alert = Alert::factory()->create();

        $mailable = new AlertCreated($alert);

        $this->assertEquals('emails.alert-created', $mailable->content()->view);
    }
}

