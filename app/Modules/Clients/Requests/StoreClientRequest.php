<?php
declare(strict_types=1);

namespace App\Modules\Clients\Requests;

use App\Modules\Clients\Models\Client;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreClientRequest validates incoming data for creating a new client.
 */
class StoreClientRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(Client::class)],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'assigned_developer_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

