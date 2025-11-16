<?php
declare(strict_types=1);

namespace App\Modules\Sites\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateSiteRequest validates the incoming data for updating an existing site.
 */
class UpdateSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'url', 'max:255', Rule::unique('sites')->ignore($this->site)],
            'type' => ['required', 'string', Rule::in(['wordpress', 'shopify', 'woocommerce', 'custom'])],
            'status' => ['required', 'string', Rule::in(['healthy', 'warning', 'critical', 'offline'])],
            'industry' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'wp_api_url' => ['nullable', 'string', 'url', 'max:255'],
            'wp_api_key' => ['nullable', 'string', 'max:255'],
            'shopify_store_url' => ['nullable', 'string', 'url', 'max:255'],
            'shopify_api_key' => ['nullable', 'string', 'max:255'],
            'shopify_access_token' => ['nullable', 'string', 'max:255'],
        ];
    }
}

