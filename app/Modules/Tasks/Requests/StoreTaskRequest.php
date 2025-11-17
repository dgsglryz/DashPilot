<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Requests;

/**
 * StoreTaskRequest validates incoming data for creating a new task.
 */
class StoreTaskRequest extends BaseTaskRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->rulesWithDueDate(['nullable', 'date', 'after_or_equal:today']);
    }
}

