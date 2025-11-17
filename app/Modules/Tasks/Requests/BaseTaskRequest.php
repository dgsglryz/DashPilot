<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Requests;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BaseTaskRequest centralizes shared validation logic for task requests.
 */
abstract class BaseTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user->role === 'admin') {
            return true;
        }

        // Verify user has access to the client_id or site_id they're trying to use
        if ($this->has('client_id') && !$this->canAccessClient($user)) {
            return false;
        }

        if ($this->has('site_id') && !$this->canAccessSite($user)) {
            return false;
        }

        // Verify assigned_to is valid (user can assign to themselves or admin can assign to anyone)
        if ($this->has('assigned_to') && !$this->canAssignTo($user)) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can access the specified client.
     *
     * @param \App\Modules\Users\Models\User $user
     * @return bool
     */
    private function canAccessClient($user): bool
    {
        $client = Client::find($this->input('client_id'));

        return $client && $client->assigned_developer_id === $user->id;
    }

    /**
     * Check if user can access the specified site.
     *
     * @param \App\Modules\Users\Models\User $user
     * @return bool
     */
    private function canAccessSite($user): bool
    {
        $site = Site::with('client')->find($this->input('site_id'));

        return $site && $site->client && $site->client->assigned_developer_id === $user->id;
    }

    /**
     * Check if user can assign task to the specified user.
     *
     * @param \App\Modules\Users\Models\User $user
     * @return bool
     */
    private function canAssignTo($user): bool
    {
        $assignedTo = (int) $this->input('assigned_to');

        // Non-admin users can only assign tasks to themselves
        return $assignedTo === $user->id || $user->role === 'admin';
    }

    /**
     * Shared rules for task requests.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function sharedRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'site_id' => ['nullable', 'integer', Rule::exists(Site::class, 'id')],
            'client_id' => ['nullable', 'integer', Rule::exists(Client::class, 'id')],
            'assigned_to' => ['required', 'integer', Rule::exists(User::class, 'id')],
            'priority' => ['required', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'status' => ['required', 'string', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
        ];
    }

    /**
     * Helper to merge shared rules with due date variations.
     *
     * @param array<int, mixed> $dueDateRules
     * @return array<string, mixed>
     */
    protected function rulesWithDueDate(array $dueDateRules): array
    {
        return array_merge($this->sharedRules(), [
            'due_date' => $dueDateRules,
        ]);
    }
}
