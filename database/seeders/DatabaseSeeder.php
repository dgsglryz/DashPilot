<?php

namespace Database\Seeders;

use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Clients\Models\Client;
use App\Modules\Monitoring\Models\SiteCheck;
use App\Modules\Notifications\Models\Webhook;
use App\Modules\Reports\Models\Report;
use App\Modules\Shopify\Models\LiquidSnippet;
use App\Modules\Sites\Models\Site;
use App\Modules\Tasks\Models\Task;
use App\Modules\Users\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $developers = $this->seedDevelopers();
        $clients = $this->seedClients($developers);
        $this->seedSitesWithDemoData($clients, $developers);
        $this->seedSnippetLibrary($developers);
        $this->seedWebhooks($developers);
        $this->ensureDemoAccount();
    }

    /**
     * @return Collection<int, User>
     */
    private function seedDevelopers(): Collection
    {
        return User::factory()
            ->count(12)
            ->state(fn () => [
                'role' => fake()->randomElement(['member', 'manager', 'admin']),
                'status' => 'active',
                'language' => 'en',
            ])
            ->create();
    }

    /**
     * @param Collection<int, User> $developers
     *
     * @return Collection<int, Client>
     */
    private function seedClients(Collection $developers): Collection
    {
        return Client::factory()
            ->count(35)
            ->state(fn () => [
                'assigned_developer_id' => $developers->random()->id,
                'status' => 'active',
            ])
            ->create();
    }

    /**
     * Build the catalog of 125 demo sites, plus related operational data.
     *
     * @param Collection<int, Client> $clients
     * @param Collection<int, User>   $developers
     */
    private function seedSitesWithDemoData(Collection $clients, Collection $developers): void
    {
        $blueprints = $this->generateSiteBlueprints(125);
        $clientCount = $clients->count();

        foreach ($blueprints as $index => $blueprint) {
            $client = $clients[$index % $clientCount];

            $site = Site::factory()
                ->for($client)
                ->state(array_merge($blueprint, [
                    'client_id' => $client->id,
                    'last_checked_at' => now()->subMinutes(random_int(5, 90)),
                    'last_backup_at' => now()->subHours(random_int(8, 96)),
                    'ssl_expires_at' => now()->addMonths(random_int(1, 12)),
                ]))
                ->create();

            $this->seedSiteOperationalData($site, $developers);
        }
    }

    /**
     * Attach monitoring history, alerts, and tasks to a site.
     *
     * @param Collection<int, User> $developers
     */
    private function seedSiteOperationalData(Site $site, Collection $developers): void
    {
        $seeder = $this;

        SiteCheck::factory()
            ->count(random_int(5, 8))
            ->state(function () use ($site, $seeder) {
                return [
                    'site_id' => $site->id,
                    'check_type' => Arr::random(['uptime', 'performance', 'security', 'backup']),
                    'status' => $seeder->checkStatusForSite($site->status),
                    'response_time' => $seeder->responseTimeForStatus($site->status),
                    'details' => [
                        'http_status' => $site->status === 'critical' ? 503 : 200,
                        'notes' => fake()->sentence(),
                    ],
                    'checked_at' => now()->subMinutes(random_int(5, 720)),
                ];
            })
            ->create();

        $this->seedAlerts($site, $developers);
        $this->seedTasks($site, $developers);
        $this->seedReports($site);
        $this->seedActivity($site, $developers);
    }

    /**
     * @param Collection<int, User> $developers
     */
    private function seedAlerts(Site $site, Collection $developers): void
    {
        $catalog = [
            ['type' => 'site_down', 'title' => 'Downtime detected', 'severity' => 'critical'],
            ['type' => 'security', 'title' => 'Security scan flagged a file', 'severity' => 'high'],
            ['type' => 'backup_failed', 'title' => 'Backup failed overnight', 'severity' => 'medium'],
            ['type' => 'ssl_expiry', 'title' => 'SSL certificate expiring soon', 'severity' => 'medium'],
            ['type' => 'performance', 'title' => 'Response time regression', 'severity' => 'low'],
        ];

        collect(range(1, random_int(1, 3)))->each(function () use ($site, $developers, $catalog) {
            $template = Arr::random($catalog);
            $resolved = fake()->boolean(45);

            Alert::factory()->state([
                'site_id' => $site->id,
                'title' => $template['title'],
                'type' => $template['type'],
                'severity' => $template['severity'],
                                    'status' => $resolved ? 'resolved' : 'active',
                'message' => fake()->sentence(),
                                    'is_resolved' => $resolved,
                'is_read' => $resolved ? true : fake()->boolean(60),
                'resolved_at' => $resolved ? now()->subMinutes(random_int(20, 300)) : null,
                                    'resolved_by' => $resolved ? $developers->random()->id : null,
                'resolution_notes' => $resolved ? fake()->sentence() : null,
                'acknowledged_at' => $resolved ? now()->subMinutes(random_int(60, 600)) : null,
                                    'acknowledged_by' => $resolved ? $developers->random()->id : null,
            ])->create();
        });
    }

    /**
     * @param Collection<int, User> $developers
     */
    private function seedTasks(Site $site, Collection $developers): void
    {
                        Task::factory()
            ->count(random_int(1, 3))
            ->state(fn () => [
                'site_id' => $site->id,
                                'client_id' => $site->client_id,
                                'assigned_to' => $developers->random()->id,
                'status' => Arr::random(['pending', 'in_progress', 'completed']),
                'priority' => Arr::random(['low', 'medium', 'high', 'urgent']),
                'due_date' => now()->addDays(random_int(2, 14)),
            ])
            ->create();
    }

    private function seedReports(Site $site): void
    {
                        Report::factory()
                            ->count(1)
            ->state(fn () => [
                                'client_id' => $site->client_id,
                                'site_id' => $site->id,
                'report_month' => now()->subMonths(random_int(0, 2))->startOfMonth(),
                'uptime_percentage' => $site->uptime_percentage,
                'avg_load_time' => $site->avg_load_time,
                'total_backups' => random_int(20, 40),
                'security_scans' => random_int(4, 10),
                'incidents_count' => random_int(0, 3),
                'generated_at' => now()->subDays(random_int(1, 14)),
            ])
            ->create();
    }

    /**
     * @param Collection<int, User> $developers
     */
    private function seedActivity(Site $site, Collection $developers): void
    {
        $actions = [
            'Updated core plugins',
            'Synced product catalog',
            'Optimised homepage hero',
            'Cleared cache & rebuilt assets',
            'Checked uptime monitors',
        ];

                        ActivityLog::factory()
                            ->count(5)
            ->state(fn () => [
                'site_id' => $site->id,
                                'user_id' => $developers->random()->id,
                'action' => Arr::random($actions),
                'description' => fake()->sentence(),
                'created_at' => now()->subMinutes(random_int(10, 720)),
            ])
            ->create();
    }

    /**
     * Create a reusable snippet library for the Shopify editor.
     *
     * @param Collection<int, User> $developers
     */
    private function seedSnippetLibrary(Collection $developers): void
    {
        LiquidSnippet::factory()
            ->count(20)
            ->state(fn () => ['user_id' => $developers->random()->id])
            ->create();
    }

    /**
     * Seed a handful of notification webhooks owned by ops engineers.
     *
     * @param Collection<int, User> $developers
     */
    private function seedWebhooks(Collection $developers): void
    {
        Webhook::factory()
            ->count(5)
            ->state(fn () => ['user_id' => $developers->random()->id])
            ->create();
    }

    /**
     * Keep a dedicated demo login handy.
     */
    private function ensureDemoAccount(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'demo@dashpilot.test'],
            [
            'name' => 'Demo Admin',
                'password' => Hash::make('Password123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        // E2E test kullanıcısı oluştur
        User::query()->updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Test Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * Build a catalogue of site blueprints (names, industries, imagery).
     *
     * @return array<int, array<string, mixed>>
     */
    private function generateSiteBlueprints(int $count): array
    {
        $industries = [
            [
                'label' => 'E-commerce & Retail',
                'keywords' => ['ecommerce', 'retail', 'fashion'],
                'nouns' => ['Boutique', 'Market', 'Collective', 'Commerce', 'Supply Co.'],
                'platforms' => ['shopify', 'woocommerce'],
            ],
            [
                'label' => 'Hospitality & Travel',
                'keywords' => ['hotel', 'resort', 'travel'],
                'nouns' => ['Resort', 'Retreat', 'Collective', 'Nomad', 'Voyage'],
                'platforms' => ['wordpress', 'custom'],
            ],
            [
                'label' => 'Healthcare',
                'keywords' => ['health', 'clinic', 'wellness'],
                'nouns' => ['Clinic', 'Wellness', 'Care', 'Health Group', 'Diagnostics'],
                'platforms' => ['wordpress', 'custom'],
            ],
            [
                'label' => 'Financial Services',
                'keywords' => ['finance', 'bank', 'fintech'],
                'nouns' => ['Capital', 'Advisors', 'Ledger', 'Wealth', 'Finance'],
                'platforms' => ['custom', 'wordpress'],
            ],
            [
                'label' => 'Education & Non-Profit',
                'keywords' => ['education', 'nonprofit', 'learning'],
                'nouns' => ['Academy', 'Institute', 'Initiative', 'Foundation', 'Network'],
                'platforms' => ['wordpress'],
            ],
            [
                'label' => 'Media & Publishing',
                'keywords' => ['media', 'news', 'studio'],
                'nouns' => ['Studios', 'Chronicle', 'Press', 'Digital', 'Network'],
                'platforms' => ['wordpress', 'custom'],
            ],
            [
                'label' => 'SaaS & Technology',
                'keywords' => ['saas', 'startup', 'software'],
                'nouns' => ['Labs', 'Systems', 'Cloud', 'Platform', 'Solutions'],
                'platforms' => ['custom', 'wordpress'],
            ],
        ];

        $regions = ['North America', 'Europe', 'Asia-Pacific', 'Latin America', 'Middle East & Africa'];
        $gallery = $this->galleryImages();
        $logos = $this->logoPalette();
        $prefixes = ['Aurora', 'Atlas', 'Zenith', 'Harbor', 'Evergreen', 'Solstice', 'Nimbus', 'Summit', 'Catalyst', 'Blue Oak', 'Vertex', 'Riverstone', 'Silverline', 'Monarch', 'Driftwood', 'Solaris', 'Foxglove', 'Lumen', 'Coastal', 'Granite'];
        $statusWeights = ['healthy' => 70, 'warning' => 20, 'critical' => 10];
        $usedNames = [];
        $blueprints = [];

        $index = 0;
        while (count($blueprints) < $count) {
            $industry = Arr::random($industries);
            $name = $this->uniqueSiteName($industry, $prefixes, $usedNames);
            $slug = Str::slug($name);
            $status = $this->weightedStatus($statusWeights);

            $blueprints[] = [
                'name' => $name,
                'url' => "https://{$slug}.dashpilot-demo.com",
                'type' => Arr::random($industry['platforms']),
                'industry' => $industry['label'],
                'region' => Arr::random($regions),
                'thumbnail_url' => $this->featuredImage($gallery, $slug, $index),
                'logo_url' => $this->featuredLogo($logos, $slug, $index),
                'status' => $status,
                'health_score' => $this->healthScoreForStatus($status),
                'uptime_percentage' => $this->uptimeForStatus($status),
                'avg_load_time' => $this->loadTimeForStatus($status),
            ];
            $index++;
        }

        return $blueprints;
    }

    /**
     * Ensure site names are unique within the catalogue.
     */
    private function uniqueSiteName(array $industry, array $prefixes, array &$usedNames): string
    {
        do {
            $prefix = Arr::random($prefixes);
            $suffix = Arr::random($industry['nouns']);
            $name = trim("{$prefix} {$suffix}");
        } while (array_key_exists($name, $usedNames));

        $usedNames[$name] = true;

        return $name;
    }

    private function weightedStatus(array $weights): string
    {
        $total = array_sum($weights);
        $target = random_int(1, $total);

        foreach ($weights as $status => $weight) {
            if ($target <= $weight) {
                return $status;
            }
            $target -= $weight;
        }

        return 'healthy';
    }

    private function healthScoreForStatus(string $status): int
    {
        return match ($status) {
            'healthy' => random_int(85, 100),
            'warning' => random_int(65, 84),
            default => random_int(40, 64),
        };
    }

    private function uptimeForStatus(string $status): float
    {
        return match ($status) {
            'healthy' => round(fake()->randomFloat(3, 99.1, 99.95), 2),
            'warning' => round(fake()->randomFloat(3, 96.5, 98.9), 2),
            default => round(fake()->randomFloat(3, 90.0, 95.5), 2),
        };
    }

    private function loadTimeForStatus(string $status): float
    {
        return match ($status) {
            'healthy' => round(fake()->randomFloat(2, 0.9, 2.3), 2),
            'warning' => round(fake()->randomFloat(2, 2.1, 3.8), 2),
            default => round(fake()->randomFloat(2, 3.5, 5.5), 2),
        };
    }

    private function responseTimeForStatus(string $status): int
    {
        return match ($status) {
            'healthy' => random_int(120, 450),
            'warning' => random_int(451, 1200),
            default => random_int(1201, 2500),
        };
    }

    private function checkStatusForSite(string $status): string
    {
        return match ($status) {
            'healthy' => 'pass',
            'warning' => 'warning',
            default => 'fail',
        };
    }

    /**
     * Curated set of hero images so the dashboard mirrors the Home.png aesthetic.
     *
     * @return array<int, string>
     */
    private function galleryImages(): array
    {
        return [
            'https://images.unsplash.com/photo-1506765515384-028b60a970df?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1521791055366-0d553872125f?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1529333166437-7750a6dd5a70?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1517430816045-df4b7de11d1d?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1474403078171-7f199e9d1336?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1487058792275-0ad4aaf24ca7?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1522199786352-3f7b4e60a6ab?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1529333166437-7750a6dd5a70?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1200&q=80',
        ];
    }

    /**
     * Logo palette guarantees at least a dozen recognisable marks for cards.
     *
     * @return array<int, string>
     */
    private function logoPalette(): array
    {
        return [
            'https://logo.clearbit.com/shopify.com',
            'https://logo.clearbit.com/wordpress.org',
            'https://logo.clearbit.com/stripe.com',
            'https://logo.clearbit.com/slack.com',
            'https://logo.clearbit.com/figma.com',
            'https://logo.clearbit.com/asana.com',
            'https://logo.clearbit.com/airbnb.com',
            'https://logo.clearbit.com/notion.so',
            'https://logo.clearbit.com/linear.app',
            'https://logo.clearbit.com/loom.com',
            'https://logo.clearbit.com/monday.com',
            'https://logo.clearbit.com/hubspot.com',
        ];
    }

    private function featuredImage(array $gallery, string $slug, int $index): string
    {
        $image = $gallery[$index % count($gallery)];

        return "{$image}&sig=".crc32($slug.$index);
    }

    private function featuredLogo(array $logos, string $slug, int $index): string
    {
        $logo = $logos[$index % count($logos)];

        return "{$logo}?size=80&greyscale=false&sig=".crc32($slug.$index);
    }
}
