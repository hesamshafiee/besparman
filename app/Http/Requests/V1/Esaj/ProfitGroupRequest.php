<?php

namespace App\Http\Requests\V1\Esaj;

use Illuminate\Foundation\Http\FormRequest;

class ProfitGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        if (str_contains($this->path(), 'api/profit-groups/assign-profit-group-to-user')) {
            return [
                'profit_group_id' => 'numeric|exists:profit_groups,id',
            ];
        }

        return [
            'title' => 'required|string|min:3|max:255',
            'profit_split_ids' => 'required|array|exists:profit_splits,id',
        ];
    }
}
