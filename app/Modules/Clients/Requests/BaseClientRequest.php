<?php
declare(strict_types=1);

namespace App\Modules\Clients\Requests;

use App\Modules\Clients\Models\Client;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BaseClientRequest centralizes shared validation logic for client requests.
 */
abstract class BaseClientRequest extends FormRequest
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
     * Shared rules for client requests.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function sharedRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'assigned_developer_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

