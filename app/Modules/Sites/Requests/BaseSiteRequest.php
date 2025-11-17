<?php
declare(strict_types=1);

namespace App\Modules\Sites\Requests;

use App\Modules\Clients\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BaseSiteRequest centralizes shared validation logic for site payloads.
 */
abstract class BaseSiteRequest extends FormRequest
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
        
        // Verify user has access to the client_id they're trying to use
        if ($this->has('client_id')) {
            $client = Client::find($this->input('client_id'));
            
            // Client must exist and be assigned to the user
            return $client && $client->assigned_developer_id === $user->id;
        }
        
        // If no client_id provided, allow (validation will catch it)
        return true;
    }

    /**
     * Shared rules excluding URL uniqueness differences.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function sharedRules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['wordpress', 'shopify', 'woocommerce', 'custom'])],
            'status' => ['required', 'string', Rule::in(['healthy', 'warning', 'critical', 'offline'])],
            'industry' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'wp_api_url' => ['nullable', 'string', 'url', 'starts_with:https://', 'max:255'],
            'wp_api_key' => ['nullable', 'string', 'max:255'],
            'shopify_store_url' => ['nullable', 'string', 'url', 'starts_with:https://', 'regex:/^https:\/\/[a-zA-Z0-9-]+\.myshopify\.com/', 'max:255'],
            'shopify_api_key' => ['nullable', 'string', 'max:255'],
            'shopify_access_token' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Merge shared rules with a custom URL rule definition.
     *
     * @param array<int, mixed> $urlRules
     * @return array<string, mixed>
     */
    protected function rulesWithUrl(array $urlRules): array
    {
        return array_merge($this->sharedRules(), [
            'url' => $urlRules,
        ]);
    }
}
