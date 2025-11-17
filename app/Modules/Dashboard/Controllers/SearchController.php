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
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
        $query = trim($request->string('q')->toString());
        $scope = Str::of($request->string('scope')->toString() ?: 'pages')
            ->lower()
            ->value();

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $allowedScopes = ['pages', 'sites', 'clients', 'alerts', 'tasks', 'reports'];
        if (!in_array($scope, $allowedScopes, true)) {
            $scope = 'pages';
        }

        $results = match ($scope) {
            'sites' => $this->searchSites($query),
            'clients' => $this->searchClients($query),
            'alerts' => $this->searchAlerts($query),
            'tasks' => $this->searchTasks($query),
            'reports' => $this->searchReports($query),
            default => $this->searchPagesAndCards($query),
        };

        return response()->json(['results' => array_slice($results, 0, 20)]);
    }

    /**
     * Search only dashboard pages and featured cards for the command bar.
     *
     * @param string $query
     * @return array<int, array<string, mixed>>
     */
    private function searchPagesAndCards(string $query): array
    {
        $normalizedQuery = mb_strtolower($query);
        $queryTokens = array_values(array_filter(preg_split('/\s+/', $normalizedQuery)));

        $cardSuggestions = [
            [
                'type' => 'card',
                'label' => 'Site Monitoring',
                'subtitle' => 'Live uptime & alerts',
                'route' => 'sites.index',
                'params' => [],
                'icon' => 'GlobeAltIcon',
                'keywords' => ['site monitoring', 'uptime', 'status', 'health'],
                'preview' => 'https://images.unsplash.com/photo-1521791055366-0d553872125f?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'type' => 'card',
                'label' => 'SEO Performance',
                'subtitle' => 'Portfolio SEO insights',
                'route' => 'metrics.index',
                'params' => [],
                'icon' => 'ChartBarIcon',
                'keywords' => ['seo', 'performance', 'score', 'search'],
                'preview' => 'https://images.unsplash.com/photo-1494790108375-be55c52b42c6?auto=format&fit=crop&w=600&q=80',
            ],
            [
                'type' => 'card',
                'label' => 'Revenue Overview',
                'subtitle' => 'Shopify growth trends',
                'route' => 'revenue.index',
                'params' => [],
                'icon' => 'CurrencyDollarIcon',
                'keywords' => ['revenue', 'shopify', 'finance', 'sales'],
                'preview' => 'https://images.unsplash.com/photo-1460925891237-14d9a24401e9?auto=format&fit=crop&w=600&q=80',
            ],
        ];

        $pageSuggestions = [
            ['type' => 'page', 'label' => 'Dashboard', 'route' => 'dashboard', 'params' => [], 'icon' => 'HomeIcon', 'keywords' => ['dashboard', 'overview', 'main']],
            ['type' => 'page', 'label' => 'Sites', 'route' => 'sites.index', 'params' => [], 'icon' => 'GlobeAltIcon', 'keywords' => ['sites', 'wordpress', 'shopify', 'monitoring']],
            ['type' => 'page', 'label' => 'Clients', 'route' => 'clients.index', 'params' => [], 'icon' => 'UserGroupIcon', 'keywords' => ['clients', 'accounts']],
            ['type' => 'page', 'label' => 'Alerts', 'route' => 'alerts.index', 'params' => [], 'icon' => 'BellIcon', 'keywords' => ['alerts', 'notifications']],
            ['type' => 'page', 'label' => 'Reports', 'route' => 'reports.index', 'params' => [], 'icon' => 'DocumentChartBarIcon', 'keywords' => ['reports', 'pdf']],
            ['type' => 'page', 'label' => 'Metrics', 'route' => 'metrics.index', 'params' => [], 'icon' => 'ChartBarIcon', 'keywords' => ['metrics', 'analytics', 'performance', 'seo']],
            ['type' => 'page', 'label' => 'Team', 'route' => 'team.index', 'params' => [], 'icon' => 'UsersIcon', 'keywords' => ['team', 'members']],
            ['type' => 'page', 'label' => 'Tasks', 'route' => 'tasks.index', 'params' => [], 'icon' => 'DocumentTextIcon', 'keywords' => ['tasks', 'todos']],
            ['type' => 'page', 'label' => 'Activity', 'route' => 'activity.index', 'params' => [], 'icon' => 'ClockIcon', 'keywords' => ['activity', 'history']],
            ['type' => 'page', 'label' => 'Revenue', 'route' => 'revenue.index', 'params' => [], 'icon' => 'CurrencyDollarIcon', 'keywords' => ['revenue', 'finance']],
            ['type' => 'page', 'label' => 'Settings', 'route' => 'settings.index', 'params' => [], 'icon' => 'Cog6ToothIcon', 'keywords' => ['settings', 'preferences']],
        ];

        $filterCallback = function (array $item) use ($normalizedQuery, $queryTokens): bool {
            if ($this->labelMatchesQuery($item, $normalizedQuery)) {
                return true;
            }

            $keywords = Arr::get($item, 'keywords', []);
            return $this->keywordsMatchQuery($keywords, $normalizedQuery, $queryTokens);
        };

        $matchingCards = array_values(array_filter($cardSuggestions, $filterCallback));
        $matchingPages = array_values(array_filter($pageSuggestions, $filterCallback));

        return array_merge($matchingCards, $matchingPages);
    }

    /**
     * Determine if an item's label matches the provided query.
     *
     * @param array<string, mixed> $item
     * @param string $normalizedQuery
     * @return bool
     */
    private function labelMatchesQuery(array $item, string $normalizedQuery): bool
    {
        $label = mb_strtolower($item['label']);
        return str_contains($label, $normalizedQuery) || str_contains($normalizedQuery, $label);
    }

    /**
     * Determine if any keyword matches the provided query or tokens.
     *
     * @param array<int, string> $keywords
     * @param string $normalizedQuery
     * @param array<int, string> $queryTokens
     * @return bool
     */
    private function keywordsMatchQuery(array $keywords, string $normalizedQuery, array $queryTokens): bool
    {
        foreach ($keywords as $keyword) {
            $needle = mb_strtolower($keyword);
            if ($this->needleMatchesQuery($needle, $normalizedQuery, $queryTokens)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether a given keyword matches the query or its tokens.
     *
     * @param string $needle
     * @param string $normalizedQuery
     * @param array<int, string> $queryTokens
     * @return bool
     */
    private function needleMatchesQuery(string $needle, string $normalizedQuery, array $queryTokens): bool
    {
        if (str_contains($needle, $normalizedQuery) || str_contains($normalizedQuery, $needle)) {
            return true;
        }

        foreach ($queryTokens as $token) {
            if ($token === '') {
                continue;
            }

            if (str_contains($needle, $token) || str_contains($token, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Search only sites for the Sites workspace.
     *
     * @param string $query
     * @return array<int, array<string, mixed>>
     */
    private function searchSites(string $query): array
    {
        return Site::where('name', 'like', "%{$query}%")
            ->orWhere('url', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'url', 'type'])
            ->map(fn (Site $site) => [
                'type' => 'site',
                'id' => $site->id,
                'label' => $site->name,
                'subtitle' => $site->url,
                'route' => 'sites.show',
                'params' => ['site' => $site->id],
                'icon' => 'GlobeAltIcon',
                'badge' => ucfirst($site->type),
            ])
            ->all();
    }

    /**
     * Search only clients.
     *
     * @param string $query
     * @return array<int, array<string, mixed>>
     */
    private function searchClients(string $query): array
    {
        return Client::where('name', 'like', "%{$query}%")
            ->orWhere('company', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'company'])
            ->map(fn (Client $client) => [
                'type' => 'client',
                'id' => $client->id,
                'label' => $client->name,
                'subtitle' => $client->company,
                'route' => 'clients.show',
                'params' => ['client' => $client->id],
                'icon' => 'UserGroupIcon',
            ])
            ->all();
    }

    /**
     * Search only alerts.
     *
     * @param string $query
     * @return array<int, array<string, mixed>>
     */
    private function searchAlerts(string $query): array
    {
        return Alert::where('title', 'like', "%{$query}%")
            ->orWhere('message', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'title', 'severity', 'type'])
            ->map(fn (Alert $alert) => [
                'type' => 'alert',
                'id' => $alert->id,
                'label' => $alert->title,
                'subtitle' => ucfirst($alert->severity) . ' - ' . ucfirst($alert->type),
                'route' => 'alerts.index',
                'params' => ['alert' => $alert->id],
                'icon' => 'BellIcon',
            ])
            ->all();
    }

    /**
     * Search tasks for the tasks workspace.
     *
     * @param string $query
     * @return array<int, array<string, mixed>>
     */
    private function searchTasks(string $query): array
    {
        return Task::where('title', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'title', 'status', 'priority'])
            ->map(fn (Task $task) => [
                'type' => 'task',
                'id' => $task->id,
                'label' => $task->title,
                'subtitle' => ucfirst($task->status) . ' - ' . ucfirst($task->priority),
                'route' => 'tasks.show',
                'params' => ['task' => $task->id],
                'icon' => 'DocumentTextIcon',
            ])
            ->all();
    }

    /**
     * Search reports scoped to the reports workspace.
     *
     * @param string $query
     * @return array<int, array<string, mixed>>
     */
    private function searchReports(string $query): array
    {
        return Report::with('site')
            ->whereHas('site', function ($q) use ($query): void {
                $q->where('name', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'report_month', 'site_id'])
            ->map(fn (Report $report) => [
                'type' => 'report',
                'id' => $report->id,
                'label' => ($report->site?->name ?? 'Site') . ' - ' . $report->report_month->format('F Y'),
                'subtitle' => 'Report',
                'route' => 'reports.index',
                'params' => [],
                'icon' => 'DocumentChartBarIcon',
            ])
            ->all();
    }
}

