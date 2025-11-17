<?php
declare(strict_types=1);

namespace App\Modules\Clients\Requests;

use App\Modules\Clients\Models\Client;
use Illuminate\Validation\Rule;

/**
 * StoreClientRequest validates incoming data for creating a new client.
 */
class StoreClientRequest extends BaseClientRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->sharedRules(), [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(Client::class)],
        ]);
    }
}

