<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Requests;

use App\Modules\Clients\Models\Client;
use App\Modules\Sites\Models\Site;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateTaskRequest validates incoming data for updating an existing task.
 */
class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'site_id' => ['nullable', 'integer', Rule::exists(Site::class, 'id')],
            'client_id' => ['nullable', 'integer', Rule::exists(Client::class, 'id')],
            'assigned_to' => ['required', 'integer', Rule::exists(User::class, 'id')],
            'priority' => ['required', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'status' => ['required', 'string', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'due_date' => ['nullable', 'date'],
        ];
    }
}

