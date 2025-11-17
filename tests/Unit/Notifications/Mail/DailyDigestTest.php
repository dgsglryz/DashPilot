<?php
declare(strict_types=1);

namespace Tests\Unit\Notifications\Mail;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Notifications\Mail\DailyDigest;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_digest_mailable_has_correct_subject(): void
    {
        $user = User::factory()->create();
        $alerts = Alert::factory()->count(2)->create();
        $sites = Site::factory()->count(1)->create();

        $mailable = new DailyDigest($user, $alerts, $sites);

        $this->assertStringContainsString('Daily Digest', $mailable->envelope()->subject);
    }

    public function test_daily_digest_mailable_uses_correct_view(): void
    {
        $user = User::factory()->create();
        $alerts = Alert::factory()->count(2)->create();
        $sites = Site::factory()->count(1)->create();

        $mailable = new DailyDigest($user, $alerts, $sites);

        $this->assertEquals('emails.daily-digest', $mailable->content()->view);
    }
}

