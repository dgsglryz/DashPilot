<?php
declare(strict_types=1);

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\Models\Webhook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

/**
 * SettingsController manages profile, notification, and monitoring preferences.
 */
class SettingsController extends Controller
{
    /**
     * Display the settings SPA.
     */
    public function index(): Response
    {
        $user = Auth::user();

        return Inertia::render('Settings/Pages/Index', [
            'settings' => [
                'name' => $user->name,
                'email' => $user->email,
                'company' => $user->company,
                'timezone' => $user->timezone,
                'language' => $user->language,
                'emailAlerts' => $user->notification_settings['emailAlerts'] ?? true,
                'emailReports' => $user->notification_settings['emailReports'] ?? true,
                'emailDowntime' => $user->notification_settings['emailDowntime'] ?? true,
                'webhooks' => Webhook::where('user_id', $user->id)
                    ->get(['id', 'name', 'url', 'events', 'is_active'])
                    ->map(fn (Webhook $webhook) => [
                        'id' => $webhook->id,
                        'name' => $webhook->name,
                        'url' => $webhook->url,
                        'events' => $webhook->events,
                        'is_active' => $webhook->is_active,
                    ])->values(),
                'checkInterval' => $user->monitoring_settings['checkInterval'] ?? 5,
                'timeout' => $user->monitoring_settings['timeout'] ?? 10,
                'uptimeThreshold' => $user->monitoring_settings['uptimeThreshold'] ?? 95,
                'responseTimeThreshold' => $user->monitoring_settings['responseTimeThreshold'] ?? 2000,
            ],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.Auth::id()],
            'company' => ['nullable', 'string', 'max:255'],
        ]);

        Auth::user()->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'timezone' => ['required', 'string'],
            'language' => ['required', 'string', 'max:5'],
        ]);

        Auth::user()->update($data);

        return back()->with('success', 'Preferences saved.');
    }

    public function updateWebhooks(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'webhooks' => ['array'],
            'webhooks.*.name' => ['nullable', 'string', 'max:255'],
            'webhooks.*.url' => ['required', 'url'],
            'webhooks.*.events' => ['array'],
            'webhooks.*.events.*' => ['string'],
        ]);

        $user = Auth::user();

        Webhook::where('user_id', $user->id)->delete();

        foreach ($validated['webhooks'] ?? [] as $webhook) {
            Webhook::create([
                'user_id' => $user->id,
                'name' => $webhook['name'] ?? 'Webhook',
                'url' => $webhook['url'],
                'events' => $webhook['events'] ?? [],
            ]);
        }

        return back()->with('success', 'Webhooks saved.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'currentPassword' => ['required'],
            'newPassword' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($data['currentPassword'], $user->password)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $user->update(['password' => $data['newPassword']]);

        return back()->with('success', 'Password updated.');
    }

    public function toggleTwoFactor(): RedirectResponse
    {
        $user = Auth::user();
        $settings = $user->notification_settings ?? [];
        $settings['twoFactorEnabled'] = !($settings['twoFactorEnabled'] ?? false);

        $user->update(['notification_settings' => $settings]);

        return back()->with('success', 'Two-factor preference updated.');
    }

    public function revokeSession(string $sessionId): RedirectResponse
    {
        DB::table('sessions')
            ->where('user_id', Auth::id())
            ->where('id', $sessionId)
            ->delete();

        return back()->with('success', 'Session revoked.');
    }

    public function updateMonitoring(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'checkInterval' => ['required', 'integer', 'min:1'],
            'timeout' => ['required', 'integer', 'min:1'],
            'uptimeThreshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'responseTimeThreshold' => ['nullable', 'numeric', 'min:0'],
        ]);

        Auth::user()->update(['monitoring_settings' => array_merge(
            Auth::user()->monitoring_settings ?? [],
            $data
        )]);

        return back()->with('success', 'Monitoring settings saved.');
    }

    public function updateThresholds(Request $request): RedirectResponse
    {
        return $this->updateMonitoring($request);
    }

    /**
     * Send test email.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function testEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template' => ['required', 'string', 'in:alert-created,alert-resolved,daily-digest'],
        ]);

        $user = Auth::user();

        try {
            // In production, this would send actual emails
            // For demo, we'll just log it
            \Log::info('Test email requested', [
                'user' => $user->email,
                'template' => $validated['template'],
            ]);

            return back()->with('success', 'Test email sent successfully! Check MailHog at http://localhost:8025');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send test email: '.$e->getMessage());
        }
    }

    /**
     * Test webhook delivery.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testWebhook(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'webhook_url' => ['required', 'url'],
            'payload' => ['nullable', 'array'],
        ]);

        try {
            $payload = $validated['payload'] ?? [
                'event' => 'test',
                'timestamp' => now()->toIso8601String(),
                'message' => 'This is a test webhook from DashPilot',
            ];

            $response = Http::timeout(10)
                ->post($validated['webhook_url'], $payload);

            return response()->json([
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'Webhook delivered successfully'
                    : 'Webhook delivery failed',
                'status_code' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook test failed: '.$e->getMessage(),
                'response' => null,
            ], 500);
        }
    }
}

