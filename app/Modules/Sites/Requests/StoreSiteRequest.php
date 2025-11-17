<?php
declare(strict_types=1);

namespace App\Modules\Sites\Requests;

/**
 * StoreSiteRequest validates the incoming data for creating a new site.
 */
class StoreSiteRequest extends BaseSiteRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->rulesWithUrl(['required', 'string', 'url', 'max:255', 'unique:sites']);
    }
}

