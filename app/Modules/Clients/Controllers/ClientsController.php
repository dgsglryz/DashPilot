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
use App\Shared\Services\LookupService;
use Illuminate\Support\Collection;
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
     * @param LookupService $lookupService
     */
    public function __construct(private readonly LookupService $lookupService)
    {
    }

    /**
     * Display a listing of all clients with optional filters.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);
        
        $user = $request->user();
        $query = Client::query()->with(['assignedDeveloper:id,name,email', 'sites:id,client_id,status']);
        
        // Scope to user's assigned clients (admin sees all)
        if ($user->role !== 'admin') {
            $query->where('assigned_developer_id', $user->id);
        }

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

        return Inertia::render('Clients/Pages/Index', [
            'clients' => $clients,
            'developers' => $this->developers(),
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
        $this->authorize('create', Client::class);
        
        return Inertia::render('Clients/Pages/Create', [
            'developers' => $this->developers(),
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
        $this->authorize('create', Client::class);
        
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
        $this->authorize('view', $client);
        
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
            'developers' => $this->developers(),
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
        $this->authorize('update', $client);
        
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
            'developers' => $this->developers(),
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
        $this->authorize('update', $client);
        
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
        $this->authorize('delete', $client);
        
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

    /**
     * Display all reports for a specific client.
     *
     * @param Client $client
     *
     * @return Response
     */
    public function reports(Client $client): Response
    {
        $this->authorize('view', $client);
        
        $reports = $client->reports()
            ->with('site:id,name,url')
            ->latest('report_month')
            ->get()
            ->map(fn (Report $report) => [
                'id' => $report->id,
                'siteName' => $report->site?->name ?? 'N/A',
                'siteUrl' => $report->site?->url ?? null,
                'month' => $report->report_month?->format('F Y'),
                'uptime' => $report->uptime_percentage,
                'avgLoadTime' => $report->avg_load_time,
                'totalBackups' => $report->total_backups,
                'securityScans' => $report->security_scans,
                'incidentsCount' => $report->incidents_count,
                'generatedAt' => $report->generated_at?->toIso8601String(),
                'downloadUrl' => $report->pdf_path ? route('reports.download', $report) : null,
            ]);

        return Inertia::render('Clients/Pages/Reports', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'company' => $client->company,
            ],
            'reports' => $reports,
        ]);
    }

    /**
     * Get cached developer dropdown options.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function developers(): Collection
    {
        return $this->lookupService->activeDevelopers();
    }
}

