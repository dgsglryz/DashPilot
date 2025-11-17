<?php
declare(strict_types=1);

namespace App\Policies;

use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;

/**
 * SitePolicy controls access to Site resources.
 * Users can only access sites belonging to clients they are assigned to.
 */
class SitePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $_user): bool
    {
        // Users can view sites for clients they are assigned to
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Site $site): bool
    {
        // User can view site if they are assigned as developer to the client
        return $site->client && $site->client->assigned_developer_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $_user): bool
    {
        // Users can create sites for clients they are assigned to
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Site $site): bool
    {
        // User can update site if they are assigned as developer to the client
        return $site->client && $site->client->assigned_developer_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Site $site): bool
    {
        // User can delete site if they are assigned as developer to the client
        return $site->client && $site->client->assigned_developer_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Site $site): bool
    {
        return $this->update($user, $site);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Site $site): bool
    {
        return $this->delete($user, $site);
    }
}
