<?php
declare(strict_types=1);

namespace App\Policies;

use App\Modules\Tasks\Models\Task;
use App\Modules\Users\Models\User;

/**
 * TaskPolicy controls access to Task resources.
 * Users can only access tasks for clients they are assigned to.
 */
class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view tasks for clients they are assigned to
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        // Admin can view all tasks
        if ($user->role === 'admin') {
            return true;
        }

        // User can view task if:
        // 1. They are assigned to the task, OR
        // 2. The task belongs to a client they are assigned to
        if ($task->assigned_to === $user->id) {
            return true;
        }

        if ($task->client && $task->client->assigned_developer_id === $user->id) {
            return true;
        }

        if ($task->site && $task->site->client && $task->site->client->assigned_developer_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Users can create tasks for clients they are assigned to
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
        // Admin can update all tasks
        if ($user->role === 'admin') {
            return true;
        }

        // User can update task if:
        // 1. They are assigned to the task, OR
        // 2. The task belongs to a client they are assigned to
        if ($task->assigned_to === $user->id) {
            return true;
        }

        if ($task->client && $task->client->assigned_developer_id === $user->id) {
            return true;
        }

        if ($task->site && $task->site->client && $task->site->client->assigned_developer_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        // Admin can delete all tasks
        if ($user->role === 'admin') {
            return true;
        }

        // User can delete task if they are assigned as developer to the client
        if ($task->client && $task->client->assigned_developer_id === $user->id) {
            return true;
        }

        if ($task->site && $task->site->client && $task->site->client->assigned_developer_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return $this->delete($user, $task);
    }
}

