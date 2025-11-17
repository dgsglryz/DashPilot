<?php
declare(strict_types=1);

namespace App\Policies;

use App\Modules\Clients\Models\Client;
use App\Modules\Users\Models\User;

/**
 * ClientPolicy controls access to Client resources.
 * Users can only access clients they are assigned to as developers.
 */
class ClientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(): bool
    {
        // Users can view clients they are assigned to
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Client $client): bool
    {
        // User can view client if they are assigned as developer
        return $client->assigned_developer_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        // Users can create clients (assignment happens separately)
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Client $client): bool
    {
        // User can update client if they are assigned as developer
        return $client->assigned_developer_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool
    {
        // User can delete client if they are assigned as developer
        return $client->assigned_developer_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Client $client): bool
    {
        return $this->update($user, $client);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Client $client): bool
    {
        return $this->delete($user, $client);
    }
}
