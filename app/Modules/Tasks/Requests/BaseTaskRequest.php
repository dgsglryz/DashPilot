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
        return true; // Authorization handled elsewhere
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
        return true; // Authorization handled via policies/middleware
    }

    /**
     * Base validation rules common to task requests.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function baseRules(): array
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
     * Helper to merge shared rules with due date rule variations.
     *
     * @param  array<int, mixed>  $dueDateRules
     * @return array<string, mixed>
     */
    protected function rulesWithDueDate(array $dueDateRules): array
    {
        return array_merge($this->baseRules(), [
            'due_date' => $dueDateRules,
        ]);
    }
}
