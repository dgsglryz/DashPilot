<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Services;

use App\Modules\Tasks\Models\Task;
use App\Shared\Services\LookupService;
use Illuminate\Support\Collection;

/**
 * TaskViewService centralizes task presentation helpers to keep controllers lean.
 */
class TaskViewService
{
    public function __construct(private readonly LookupService $lookupService)
    {
    }

    /**
     * Build a reusable task representation for API/SSR responses.
     *
     * @param Task $task
     *
     * @return array<string, mixed>
     */
    public function resource(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'dueDate' => $task->due_date?->toDateString(),
            'completedAt' => $task->completed_at?->toIso8601String(),
            'assignee' => [
                'id' => $task->assigned_to,
                'name' => $task->assignee?->name,
                'email' => $task->assignee?->email,
            ],
            'site' => $task->site ? [
                'id' => $task->site->id,
                'name' => $task->site->name,
            ] : null,
            'client' => $task->client ? [
                'id' => $task->client->id,
                'name' => $task->client->name,
            ] : null,
        ];
    }

    /**
     * Build stats from task collections.
     *
     * @param Collection<int, Task|array<string, mixed>> $allTasks
     * @param Collection<int, array<string, mixed>>|null $paginatedTasks
     *
     * @return array<string, int>
     */
    public function buildStats(Collection $allTasks, ?Collection $paginatedTasks = null): array
    {
        $paginatedTasks ??= collect();

        return [
            'total' => $allTasks->count(),
            'pending' => $allTasks->where('status', 'pending')->count(),
            'in_progress' => $allTasks->where('status', 'in_progress')->count(),
            'completed' => $allTasks->where('status', 'completed')->count(),
            'cancelled' => $allTasks->where('status', 'cancelled')->count(),
            'urgent' => $paginatedTasks->where('priority', 'urgent')->count(),
        ];
    }

    /**
     * Group tasks by status for Kanban style rendering.
     *
     * @param Collection<int, array<string, mixed>> $tasks
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function groupByStatus(Collection $tasks): array
    {
        return [
            'pending' => $tasks->where('status', 'pending')->values()->all(),
            'in_progress' => $tasks->where('status', 'in_progress')->values()->all(),
            'completed' => $tasks->where('status', 'completed')->values()->all(),
            'cancelled' => $tasks->where('status', 'cancelled')->values()->all(),
        ];
    }

    /**
     * Prepare reusable lookup datasets for forms.
     *
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    public function lookups(): array
    {
        $clients = $this->lookupService->clientOptions()
            ->map(fn (array $client): array => [
                'id' => $client['id'],
                'name' => $client['name'],
            ]);

        return [
            'users' => $this->lookupService->activeDevelopers(),
            'sites' => $this->lookupService->siteOptions(),
            'clients' => $clients,
        ];
    }
}



