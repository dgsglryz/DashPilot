<?php
declare(strict_types=1);

namespace App\Modules\Sites\Requests;

use Illuminate\Validation\Rule;

/**
 * UpdateSiteRequest validates the incoming data for updating an existing site.
 */
class UpdateSiteRequest extends BaseSiteRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $urlRule = ['required', 'string', 'url', 'max:255', Rule::unique('sites')->ignore($this->site)];

        return $this->rulesWithUrl($urlRule);
    }
}

