<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Requests\StoreTaskRequest;
use App\Modules\Tasks\Requests\UpdateTaskRequest;
use App\Modules\Users\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * TasksController manages task CRUD operations and displays task-related data.
 */
class TasksController extends Controller
{
    /**
     * Display a listing of all tasks with optional filters (Kanban board view).
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $query = Task::query()->with(['assignee:id,name,email', 'site:id,name', 'client:id,name']);

        // Filter by status
        if ($request->filled('status') && $request->string('status')->toString() !== 'all') {
            $query->where('status', $request->string('status')->toString());
        }

        // Filter by priority
        if ($request->filled('priority') && $request->string('priority')->toString() !== 'all') {
            $query->where('priority', $request->string('priority')->toString());
        }

        // Filter: My Tasks
        if ($request->filled('my_tasks') && $request->boolean('my_tasks')) {
            $query->where('assigned_to', $request->user()->id);
        }

        // Filter: Urgent
        if ($request->filled('urgent') && $request->boolean('urgent')) {
            $query->where('priority', 'urgent');
        }

        // Search
        if ($request->filled('query')) {
            $search = $request->string('query')->toString();
            $query->where(function ($inner) use ($search): void {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->orderBy('created_at', 'desc')->get()->map(fn (Task $task) => [
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
        ]);

        // Group tasks by status for Kanban board
        $tasksByStatus = [
            'pending' => $tasks->where('status', 'pending')->values()->all(),
            'in_progress' => $tasks->where('status', 'in_progress')->values()->all(),
            'completed' => $tasks->where('status', 'completed')->values()->all(),
            'cancelled' => $tasks->where('status', 'cancelled')->values()->all(),
        ];

        $stats = [
            'total' => $tasks->count(),
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'urgent' => $tasks->where('priority', 'urgent')->count(),
        ];

        $users = User::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        $sites = Site::orderBy('name')->get(['id', 'name'])->map(fn (Site $site) => [
            'id' => $site->id,
            'name' => $site->name,
        ]);

        $clients = Client::orderBy('name')->get(['id', 'name'])->map(fn (Client $client) => [
            'id' => $client->id,
            'name' => $client->name,
        ]);

        return Inertia::render('Tasks/Pages/Index', [
            'tasks' => $tasksByStatus,
            'stats' => $stats,
            'users' => $users,
            'sites' => $sites,
            'clients' => $clients,
            'filters' => [
                'query' => $request->string('query')->toString(),
                'status' => $request->string('status')->toString() ?: 'all',
                'priority' => $request->string('priority')->toString() ?: 'all',
                'my_tasks' => $request->boolean('my_tasks', false),
                'urgent' => $request->boolean('urgent', false),
            ],
        ]);
    }

    /**
     * Show the form for creating a new task.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function create(Request $request): Response
    {
        $users = User::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        $sites = Site::orderBy('name')->get(['id', 'name'])->map(fn (Site $site) => [
            'id' => $site->id,
            'name' => $site->name,
        ]);

        $clients = Client::orderBy('name')->get(['id', 'name'])->map(fn (Client $client) => [
            'id' => $client->id,
            'name' => $client->name,
        ]);

        return Inertia::render('Tasks/Pages/Create', [
            'users' => $users,
            'sites' => $sites,
            'clients' => $clients,
        ]);
    }

    /**
     * Store a newly created task in the database.
     *
     * @param StoreTaskRequest $request
     *
     * @return RedirectResponse
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $task = Task::create($request->validated());

        // Mark as completed if status is completed
        if ($task->status === 'completed') {
            $task->completed_at = Carbon::now();
            $task->save();
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'task_created',
            'description' => "Created task: {$task->title}",
        ]);

        return redirect()->route('tasks.index')
            ->with('success', 'Task created successfully.');
    }

    /**
     * Show the form for editing the specified task.
     *
     * @param Task $task
     *
     * @return Response
     */
    public function edit(Task $task): Response
    {
        $task->load(['assignee:id,name,email', 'site:id,name', 'client:id,name']);

        $users = User::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        $sites = Site::orderBy('name')->get(['id', 'name'])->map(fn (Site $site) => [
            'id' => $site->id,
            'name' => $site->name,
        ]);

        $clients = Client::orderBy('name')->get(['id', 'name'])->map(fn (Client $client) => [
            'id' => $client->id,
            'name' => $client->name,
        ]);

        return Inertia::render('Tasks/Pages/Edit', [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'site_id' => $task->site_id,
                'client_id' => $task->client_id,
                'assigned_to' => $task->assigned_to,
                'priority' => $task->priority,
                'status' => $task->status,
                'due_date' => $task->due_date?->toDateString(),
            ],
            'users' => $users,
            'sites' => $sites,
            'clients' => $clients,
        ]);
    }

    /**
     * Update the specified task in the database.
     *
     * @param UpdateTaskRequest $request
     * @param Task $task
     *
     * @return RedirectResponse
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $oldStatus = $task->status;
        $task->update($request->validated());

        // Update completed_at if status changed to/from completed
        if ($task->status === 'completed' && $oldStatus !== 'completed') {
            $task->completed_at = Carbon::now();
            $task->save();
        } elseif ($task->status !== 'completed' && $oldStatus === 'completed') {
            $task->completed_at = null;
            $task->save();
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'task_updated',
            'description' => "Updated task: {$task->title}",
        ]);

        return redirect()->route('tasks.index')
            ->with('success', 'Task updated successfully.');
    }

    /**
     * Update task status (quick status change from Kanban board).
     *
     * @param Request $request
     * @param Task $task
     *
     * @return RedirectResponse
     */
    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,in_progress,completed,cancelled'],
        ]);

        $oldStatus = $task->status;
        $task->status = $validated['status'];

        // Update completed_at if status changed to/from completed
        if ($task->status === 'completed' && $oldStatus !== 'completed') {
            $task->completed_at = Carbon::now();
        } elseif ($task->status !== 'completed' && $oldStatus === 'completed') {
            $task->completed_at = null;
        }

        $task->save();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'task_status_changed',
            'description' => "Changed task status from {$oldStatus} to {$task->status}: {$task->title}",
        ]);

        return back()->with('success', 'Task status updated successfully.');
    }

    /**
     * Remove the specified task from the database.
     *
     * @param Request $request
     * @param Task $task
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $taskTitle = $task->title;
        $task->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'task_deleted',
            'description' => "Deleted task: {$taskTitle}",
        ]);

        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }
}

