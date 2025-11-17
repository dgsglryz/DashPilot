<?php
declare(strict_types=1);

namespace App\Policies;

use App\Modules\Alerts\Models\Alert;
use App\Modules\Users\Models\User;

/**
 * AlertPolicy controls access to Alert resources.
 * Users can only access alerts for sites belonging to clients they are assigned to.
 */
class AlertPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(): bool
    {
        // Users can view alerts for sites they have access to
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Alert $alert): bool
    {
        // User can view alert if they have access to the associated site
        if (!$alert->site || !$alert->site->client) {
            return false;
        }

        return $alert->site->client->assigned_developer_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        // Alerts are typically created by the system, not users
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Alert $alert): bool
    {
        // User can update alert if they have access to the associated site
        return $this->view($user, $alert);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Alert $alert): bool
    {
        // User can delete alert if they have access to the associated site
        return $this->view($user, $alert);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Alert $alert): bool
    {
        return $this->update($user, $alert);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Alert $alert): bool
    {
        return $this->delete($user, $alert);
    }
}
