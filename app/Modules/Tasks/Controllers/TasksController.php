<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Requests\StoreTaskRequest;
use App\Modules\Tasks\Requests\UpdateTaskRequest;
use App\Modules\Users\Models\User;
use App\Modules\Tasks\Services\TaskViewService;
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
    public function __construct(private readonly TaskViewService $taskViewService)
    {
    }

    /**
     * Display a listing of all tasks with optional filters (Kanban board view).
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Task::class);
        
        $user = $request->user();
        $query = Task::query()
            ->with(['assignee:id,name,email', 'site:id,name', 'client:id,name'])
            ->forUser($user);

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
            $query->search($request->string('query')->toString());
        }

        $perPage = $request->integer('per_page', 20);
        $statsCollection = (clone $query)->get();
        $tasks = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(fn (Task $task): array => $this->taskViewService->resource($task));
        $paginatedTasksCollection = collect($tasks->items());

        $tasksByStatus = $this->taskViewService->groupByStatus($paginatedTasksCollection);
        $stats = $this->taskViewService->buildStats($statsCollection, $paginatedTasksCollection);

        $lookups = $this->taskViewService->lookups();

        return Inertia::render('Tasks/Pages/Index', [
            'tasks' => $tasksByStatus,
            'tasksPaginated' => $tasks,
            'stats' => $stats,
            'users' => $lookups['users'],
            'sites' => $lookups['sites'],
            'clients' => $lookups['clients'],
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
     * @return Response
     */
    public function create(): Response
    {
        $this->authorize('create', Task::class);
        
        $lookups = $this->taskViewService->lookups();

        return Inertia::render('Tasks/Pages/Create', [
            'developers' => $lookups['users'],
            'users' => $lookups['users'],
            'sites' => $lookups['sites'],
            'clients' => $lookups['clients'],
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
        $this->authorize('create', Task::class);
        
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
     * Display the specified task.
     *
     * @param Task $task
     *
     * @return Response
     */
    public function show(Task $task): Response
    {
        $this->authorize('view', $task);
        
        $task->load(['assignee:id,name,email', 'site:id,name', 'client:id,name']);

        return Inertia::render('Tasks/Pages/Show', [
            'task' => $this->taskViewService->resource($task),
        ]);
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
        $this->authorize('update', $task);
        
        $task->load(['assignee:id,name,email', 'site:id,name', 'client:id,name']);

        $lookups = $this->taskViewService->lookups();

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
            'developers' => $lookups['users'],
            'users' => $lookups['users'],
            'sites' => $lookups['sites'],
            'clients' => $lookups['clients'],
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
        $this->authorize('update', $task);
        
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
        $this->authorize('update', $task);
        
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
     * Cancel the specified task instead of permanently deleting it.
     *
     * @param Request $request
     * @param Task $task
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        
        $taskTitle = $task->title;
        $previousStatus = $task->status;

        // Move the task into the cancelled column so it remains visible on the board
        $task->status = 'cancelled';
        $task->completed_at = null;
        $task->save();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'task_cancelled',
            'description' => "Cancelled task (delete action) from {$previousStatus} to cancelled: {$taskTitle}",
        ]);

        return redirect()->route('tasks.index')
            ->with('success', 'Task has been moved to the Cancelled column.');
    }

    /**
     * Get all tasks assigned to a specific user.
     *
     * @param User $user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserTasks(User $user): \Illuminate\Http\JsonResponse
    {
        // Only allow viewing own tasks or if admin
        $currentUser = auth()->user();
        abort_unless($currentUser->id === $user->id || $currentUser->role === 'admin', 403);
        
        $taskCollection = Task::where('assigned_to', $user->id)
            ->with(['site:id,name', 'client:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        $tasks = $taskCollection->map(fn (Task $task): array => $this->taskViewService->resource($task));
        $stats = $this->taskViewService->buildStats($taskCollection);

        return response()->json([
            'tasks' => $tasks,
            'stats' => $stats,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

}

