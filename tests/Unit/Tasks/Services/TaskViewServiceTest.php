<?php
declare(strict_types=1);

namespace Tests\Unit\Tasks\Services;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Services\TaskViewService;
use App\Modules\Users\Models\User;
use App\Shared\Services\LookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TaskViewServiceTest verifies task presentation and grouping logic.
 */
class TaskViewServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskViewService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $lookupService = $this->createMock(LookupService::class);
        $this->service = new TaskViewService($lookupService);
    }

    /**
     * Test that resource returns correct structure.
     */
    public function test_resource_returns_correct_structure(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $site = Site::factory()->create(['client_id' => $client->id]);
        $task = Task::factory()->create([
            'client_id' => $client->id,
            'site_id' => $site->id,
            'assigned_to' => $user->id,
        ]);

        $resource = $this->service->resource($task);

        $this->assertArrayHasKey('id', $resource);
        $this->assertArrayHasKey('title', $resource);
        $this->assertArrayHasKey('status', $resource);
        $this->assertArrayHasKey('priority', $resource);
        $this->assertArrayHasKey('assignee', $resource);
        $this->assertArrayHasKey('client', $resource);
        $this->assertArrayHasKey('site', $resource);
        $this->assertEquals($task->id, $resource['id']);
    }

    /**
     * Test that buildStats calculates correct counts.
     */
    public function test_build_stats_calculates_correct_counts(): void
    {
        $tasks = collect([
            Task::factory()->make(['status' => 'pending']),
            Task::factory()->make(['status' => 'pending']),
            Task::factory()->make(['status' => 'in_progress']),
            Task::factory()->make(['status' => 'completed']),
            Task::factory()->make(['status' => 'cancelled']),
        ]);

        $stats = $this->service->buildStats($tasks);

        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(2, $stats['pending']);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(1, $stats['cancelled']);
    }

    /**
     * Test that groupByStatus groups tasks correctly.
     */
    public function test_group_by_status_groups_tasks_correctly(): void
    {
        $tasks = collect([
            ['id' => 1, 'status' => 'pending'],
            ['id' => 2, 'status' => 'pending'],
            ['id' => 3, 'status' => 'in_progress'],
            ['id' => 4, 'status' => 'completed'],
            ['id' => 5, 'status' => 'cancelled'],
        ]);

        $grouped = $this->service->groupByStatus($tasks);

        $this->assertCount(2, $grouped['pending']);
        $this->assertCount(1, $grouped['in_progress']);
        $this->assertCount(1, $grouped['completed']);
        $this->assertCount(1, $grouped['cancelled']);
    }

    /**
     * Test that groupByStatus handles empty collection.
     */
    public function test_group_by_status_handles_empty_collection(): void
    {
        $grouped = $this->service->groupByStatus(collect());

        $this->assertEmpty($grouped['pending']);
        $this->assertEmpty($grouped['in_progress']);
        $this->assertEmpty($grouped['completed']);
        $this->assertEmpty($grouped['cancelled']);
    }

    /**
     * Test that buildStats includes urgent count from paginated tasks.
     */
    public function test_build_stats_includes_urgent_count(): void
    {
        $allTasks = collect([
            Task::factory()->make(['status' => 'pending', 'priority' => 'urgent']),
            Task::factory()->make(['status' => 'pending', 'priority' => 'high']),
        ]);

        $paginatedTasks = collect([
            ['id' => 1, 'status' => 'pending', 'priority' => 'urgent'],
        ]);

        $stats = $this->service->buildStats($allTasks, $paginatedTasks);

        $this->assertEquals(1, $stats['urgent']);
    }
}


