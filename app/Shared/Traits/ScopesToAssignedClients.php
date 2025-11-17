<?php
declare(strict_types=1);

namespace App\Shared\Traits;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * ScopesToAssignedClients provides reusable methods for scoping queries to user's assigned clients.
 * 
 * This trait eliminates duplication of admin role checks and client scoping logic
 * across multiple controllers and request classes.
 */
trait ScopesToAssignedClients
{
    /**
     * Scope a query to only include records accessible to the given user.
     * Admin users see all records, others see only their assigned clients.
     *
     * @param Builder $query The query builder instance
     * @param User $user The user to scope for
     * @param string $clientRelationPath The relationship path to the client (e.g., 'client' or 'site.client')
     * @return Builder
     */
    protected function scopeToUserClients(Builder $query, User $user, string $clientRelationPath = 'client'): Builder
    {
        if ($this->isAdmin($user)) {
            return $query;
        }

        return $query->whereHas($clientRelationPath, function ($q) use ($user) {
            $q->where('assigned_developer_id', $user->id);
        });
    }

    /**
     * Check if a user has admin role.
     *
     * @param User $user The user to check
     * @return bool
     */
    protected function isAdmin(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Check if a user is NOT an admin (developer role).
     *
     * @param User $user The user to check
     * @return bool
     */
    protected function isNotAdmin(User $user): bool
    {
        return !$this->isAdmin($user);
    }
}

