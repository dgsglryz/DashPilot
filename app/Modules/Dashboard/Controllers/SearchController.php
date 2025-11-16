<?php
declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Alerts\Models\Alert;
use App\Modules\Clients\Models\Client;
use App\Modules\Reports\Models\Report;
use App\Modules\Sites\Models\Site;
use App\Modules\Tasks\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SearchController provides global search functionality across all modules.
 */
class SearchController extends Controller
{
    /**
     * Perform global search across all entities.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->string('q')->toString();
        
        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // Search Sites
        $sites = Site::where('name', 'like', "%{$query}%")
            ->orWhere('url', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'url', 'type', 'status'])
            ->map(fn (Site $site) => [
                'type' => 'site',
                'id' => $site->id,
                'label' => $site->name,
                'subtitle' => $site->url,
                'route' => 'sites.show',
                'params' => ['site' => $site->id],
                'icon' => 'GlobeAltIcon',
                'badge' => ucfirst($site->type),
            ]);

        $results = array_merge($results, $sites->all());

        // Search Clients
        $clients = Client::where('name', 'like', "%{$query}%")
            ->orWhere('company', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'company', 'email'])
            ->map(fn (Client $client) => [
                'type' => 'client',
                'id' => $client->id,
                'label' => $client->name,
                'subtitle' => $client->company,
                'route' => 'clients.show',
                'params' => ['client' => $client->id],
                'icon' => 'UserGroupIcon',
            ]);

        $results = array_merge($results, $clients->all());

        // Search Alerts
        $alerts = Alert::where('title', 'like', "%{$query}%")
            ->orWhere('message', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'title', 'severity', 'type'])
            ->map(fn (Alert $alert) => [
                'type' => 'alert',
                'id' => $alert->id,
                'label' => $alert->title,
                'subtitle' => ucfirst($alert->severity) . ' - ' . ucfirst($alert->type),
                'route' => 'alerts.index',
                'params' => ['alert' => $alert->id],
                'icon' => 'BellIcon',
            ]);

        $results = array_merge($results, $alerts->all());

        // Search Tasks
        $tasks = Task::where('title', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'title', 'status', 'priority'])
            ->map(fn (Task $task) => [
                'type' => 'task',
                'id' => $task->id,
                'label' => $task->title,
                'subtitle' => ucfirst($task->status) . ' - ' . ucfirst($task->priority),
                'route' => 'tasks.show',
                'params' => ['task' => $task->id],
                'icon' => 'DocumentTextIcon',
            ]);

        $results = array_merge($results, $tasks->all());

        // Search Reports
        $reports = Report::with('site')
            ->whereHas('site', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get(['id', 'report_month', 'site_id'])
            ->map(fn (Report $report) => [
                'type' => 'report',
                'id' => $report->id,
                'label' => ($report->site?->name ?? 'Site') . ' - ' . $report->report_month->format('F Y'),
                'subtitle' => 'Report',
                'route' => 'reports.index',
                'params' => [],
                'icon' => 'DocumentChartBarIcon',
            ]);

        $results = array_merge($results, $reports->all());

        // Add page suggestions
        $pageSuggestions = [
            ['type' => 'page', 'label' => 'Metrics', 'route' => 'metrics.index', 'params' => [], 'icon' => 'ChartBarIcon', 'keywords' => ['metrics', 'metric', 'performance', 'chart', 'graph', 'analytics', 'seo', 'uptime', 'response time']],
            ['type' => 'page', 'label' => 'Sites', 'route' => 'sites.index', 'params' => [], 'icon' => 'GlobeAltIcon', 'keywords' => ['sites', 'site', 'website', 'wordpress', 'shopify']],
            ['type' => 'page', 'label' => 'Alerts', 'route' => 'alerts.index', 'params' => [], 'icon' => 'BellIcon', 'keywords' => ['alerts', 'alert', 'warning', 'notification', 'critical']],
            ['type' => 'page', 'label' => 'Tasks', 'route' => 'tasks.index', 'params' => [], 'icon' => 'DocumentTextIcon', 'keywords' => ['tasks', 'task', 'todo', 'kanban', 'pending', 'completed']],
            ['type' => 'page', 'label' => 'Reports', 'route' => 'reports.index', 'params' => [], 'icon' => 'DocumentChartBarIcon', 'keywords' => ['reports', 'report', 'pdf', 'export', 'generate']],
            ['type' => 'page', 'label' => 'Clients', 'route' => 'clients.index', 'params' => [], 'icon' => 'UserGroupIcon', 'keywords' => ['clients', 'client', 'customer', 'company']],
            ['type' => 'page', 'label' => 'Dashboard', 'route' => 'dashboard', 'params' => [], 'icon' => 'HomeIcon', 'keywords' => ['dashboard', 'home', 'overview', 'main']],
            ['type' => 'page', 'label' => 'Activity', 'route' => 'activity.index', 'params' => [], 'icon' => 'ClockIcon', 'keywords' => ['activity', 'activities', 'log', 'history']],
            ['type' => 'page', 'label' => 'Revenue', 'route' => 'revenue.index', 'params' => [], 'icon' => 'CurrencyDollarIcon', 'keywords' => ['revenue', 'money', 'earnings', 'sales', 'shopify']],
            ['type' => 'page', 'label' => 'Team', 'route' => 'team.index', 'params' => [], 'icon' => 'UsersIcon', 'keywords' => ['team', 'users', 'members', 'staff']],
            ['type' => 'page', 'label' => 'Settings', 'route' => 'settings.index', 'params' => [], 'icon' => 'Cog6ToothIcon', 'keywords' => ['settings', 'setting', 'preferences', 'config', 'configuration']],
        ];

        $filteredPages = array_filter($pageSuggestions, function ($page) use ($query) {
            $labelMatch = stripos($page['label'], $query) !== false;
            $keywordMatch = isset($page['keywords']) && 
                collect($page['keywords'])->some(fn($k) => stripos($k, $query) !== false);
            return $labelMatch || $keywordMatch;
        });

        $results = array_merge($results, array_values($filteredPages));

        return response()->json(['results' => array_slice($results, 0, 20)]);
    }
}

