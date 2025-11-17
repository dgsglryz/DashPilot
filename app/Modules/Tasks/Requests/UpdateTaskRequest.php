<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Requests;

/**
 * UpdateTaskRequest validates incoming data for updating an existing task.
 */
class UpdateTaskRequest extends BaseTaskRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->rulesWithDueDate(['nullable', 'date']);
    }
}

