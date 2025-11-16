<?php
declare(strict_types=1);

namespace App\Modules\Clients\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Activity\Models\ActivityLog;
use App\Modules\Clients\Models\Client;
use App\Modules\Clients\Requests\StoreClientRequest;
use App\Modules\Clients\Requests\UpdateClientRequest;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use App\Modules\Tasks\Models\Task;
use App\Modules\Users\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ClientsController manages client CRUD operations and displays client-related data.
 */
class ClientsController extends Controller
{
    /**
     * Display a listing of all clients with optional filters.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $query = Client::query()->with(['assignedDeveloper:id,name,email', 'sites:id,client_id,status']);

        if ($request->filled('status') && $request->string('status')->toString() !== 'all') {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('query')) {
            $search = $request->string('query')->toString();
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $clients = $query
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'company' => $client->company,
                'email' => $client->email,
                'phone' => $client->phone,
                'status' => $client->status,
                'sitesCount' => $client->sites->count(),
                'assignedDeveloper' => [
                    'id' => $client->assigned_developer_id,
                    'name' => $client->assignedDeveloper?->name,
                    'email' => $client->assignedDeveloper?->email,
                ],
            ]);

        $developers = User::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return Inertia::render('Clients/Pages/Index', [
            'clients' => $clients,
            'developers' => $developers,
            'filters' => [
                'query' => $request->string('query')->toString(),
                'status' => $request->string('status')->toString() ?: 'all',
            ],
        ]);
    }

    /**
     * Show the form for creating a new client.
     *
     * @return Response
     */
    public function create(): Response
    {
        $developers = User::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return Inertia::render('Clients/Pages/Create', [
            'developers' => $developers,
        ]);
    }

    /**
     * Store a newly created client in the database.
     *
     * @param StoreClientRequest $request
     *
     * @return RedirectResponse
     */
    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'client_created',
            'description' => "Created client: {$client->name}",
        ]);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client created successfully.');
    }

    /**
     * Display the specified client with related data.
     *
     * @param Client $client
     *
     * @return Response
     */
    public function show(Client $client): Response
    {
        $client->load(['assignedDeveloper:id,name,email', 'sites', 'tasks', 'reports']);

        $sites = $client->sites()
            ->orderBy('name')
            ->get(['id', 'name', 'url', 'type', 'status', 'health_score', 'uptime_percentage', 'thumbnail_url', 'logo_url'])
            ->map(fn (Site $site) => [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'type' => $site->type,
                'status' => $site->status,
                'healthScore' => $site->health_score,
                'uptime' => $site->uptime_percentage,
                'thumbnail' => $site->thumbnail_url,
                'logo' => $site->logo_url,
            ]);

        $recentTasks = $client->tasks()
            ->latest()
            ->limit(10)
            ->with('assignee:id,name')
            ->get(['id', 'title', 'status', 'priority', 'due_date', 'assigned_to'])
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'dueDate' => $task->due_date?->toDateString(),
                'assignee' => [
                    'id' => $task->assigned_to,
                    'name' => $task->assignee?->name,
                ],
            ]);

        $latestReport = $client->reports()
            ->latest('report_month')
            ->first();

        $developers = User::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return Inertia::render('Clients/Pages/Show', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'company' => $client->company,
                'email' => $client->email,
                'phone' => $client->phone,
                'status' => $client->status,
                'notes' => $client->notes,
                'assignedDeveloper' => [
                    'id' => $client->assigned_developer_id,
                    'name' => $client->assignedDeveloper?->name,
                    'email' => $client->assignedDeveloper?->email,
                ],
            ],
            'sites' => $sites,
            'recentTasks' => $recentTasks,
            'latestReport' => $latestReport ? [
                'id' => $latestReport->id,
                'month' => $latestReport->report_month?->format('M Y'),
                'uptime' => $latestReport->uptime_percentage,
                'avgLoadTime' => $latestReport->avg_load_time,
                'incidentsCount' => $latestReport->incidents_count,
            ] : null,
            'developers' => $developers,
        ]);
    }

    /**
     * Show the form for editing the specified client.
     *
     * @param Client $client
     *
     * @return Response
     */
    public function edit(Client $client): Response
    {
        $developers = User::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return Inertia::render('Clients/Pages/Edit', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'company' => $client->company,
                'email' => $client->email,
                'phone' => $client->phone,
                'status' => $client->status,
                'assigned_developer_id' => $client->assigned_developer_id,
                'notes' => $client->notes,
            ],
            'developers' => $developers,
        ]);
    }

    /**
     * Update the specified client in the database.
     *
     * @param UpdateClientRequest $request
     * @param Client $client
     *
     * @return RedirectResponse
     */
    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'client_updated',
            'description' => "Updated client: {$client->name}",
        ]);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    /**
     * Remove the specified client from the database.
     *
     * @param Request $request
     * @param Client $client
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $clientName = $client->name;
        $client->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'client_deleted',
            'description' => "Deleted client: {$clientName}",
        ]);

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted successfully.');
    }
}

