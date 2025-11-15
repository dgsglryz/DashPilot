<?php

namespace Database\Seeders;

use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Clients\Models\Client;
use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use App\Modules\Tasks\Models\Task;
use App\Modules\Users\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $developers = User::factory()->count(5)->create();

        Client::factory()
            ->count(8)
            ->state(fn () => ['assigned_developer_id' => $developers->random()->id])
            ->has(
                Site::factory()
                    ->count(random_int(2, 4))
                    ->has(SiteCheck::factory()->count(4), 'checks')
                    ->has(
                        Alert::factory()
                            ->count(2)
                            ->state(function (array $attributes, Site $site) use ($developers) {
                                $resolved = (bool) random_int(0, 1);

                                return [
                                    'site_id' => $site->id,
                                    'is_resolved' => $resolved,
                                    'resolved_at' => $resolved ? now()->subMinutes(random_int(10, 120)) : null,
                                    'resolved_by' => $resolved ? $developers->random()->id : null,
                                    'resolution_notes' => $resolved ? fake()->sentence() : null,
                                ];
                            }),
                        'alerts'
                    )
                    ->has(
                        Task::factory()
                            ->count(3)
                            ->state(fn (array $attributes, Site $site) => [
                                'client_id' => $site->client_id,
                                'assigned_to' => $developers->random()->id,
                                'site_id' => $site->id,
                            ]),
                        'tasks'
                    )
                    ->has(
                        Report::factory()
                            ->count(1)
                            ->state(fn (array $attributes, Site $site) => [
                                'client_id' => $site->client_id,
                                'site_id' => $site->id,
                            ]),
                        'reports'
                    )
                    ->has(
                        ActivityLog::factory()
                            ->count(5)
                            ->state(fn (array $attributes, Site $site) => [
                                'user_id' => $developers->random()->id,
                                'site_id' => $site->id,
                                'created_at' => now()->subMinutes(random_int(10, 600)),
                            ]),
                        'activityLogs'
                    ),
                'sites'
            )
            ->create();

        // Fallback demo account
        User::factory()->create([
            'name' => 'Demo Admin',
            'email' => 'demo@dashpilot.test',
        ]);
    }
}
