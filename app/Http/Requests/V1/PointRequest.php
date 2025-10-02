<?php

namespace App\Http\Requests\V1;

use App\Models\Point;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PointRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'operator_id' => 'required|numeric|exists:operators,id',
            'type' => ['required', 'string', Rule::in([
                Point::TYPE_CELL_INTERNET_PACKAGE,
                Point::TYPE_TD_LTE_INTERNET_PACKAGE,
                Point::TYPE_CELL_INTERNET_DIRECT_CHARGE,
                Point::TYPE_AMAZING_CELL_DIRECT_CHARGE,
                Point::TYPE_CELL_DIRECT_CHARGE
            ])],
            'value' => 'required|integer|min:1000|max:1000000',
            'point' => 'required|integer|min:1|max:1000',
            'status' => 'boolean',
        ];
    }
}
