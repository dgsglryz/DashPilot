<?php
declare(strict_types=1);

namespace App\Policies;

use App\Modules\Reports\Models\Report;
use App\Modules\Users\Models\User;

/**
 * ReportPolicy controls access to Report resources.
 * Users can only access reports for sites belonging to clients they are assigned to.
 */
class ReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(): bool
    {
        // Users can view reports for sites they have access to
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Report $report): bool
    {
        // User can view report if they have access to the associated site
        if (!$report->site || !$report->site->client) {
            return false;
        }

        return $report->site->client->assigned_developer_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        // Users can generate reports for sites they have access to
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(): bool
    {
        // Reports are typically not updated after creation
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Report $report): bool
    {
        // User can delete report if they have access to the associated site
        return $this->view($user, $report);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(): bool
    {
        // Reports are typically not updated after creation, so restore is also not allowed
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Report $report): bool
    {
        return $this->delete($user, $report);
    }
}
