<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
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
            'title'  => [Rule::requiredIf(is_null($this->sale)),'string','max:128'],
            'type'   => [Rule::requiredIf(is_null($this->sale) || !is_null($this->value)),'in:money,percent'],
            'value'  => [Rule::requiredIf(is_null($this->sale) || !is_null($this->type)),'min:0','numeric'],
            'status' => 'boolean',
            'start_date' => 'date|date_format:Y-m-d H:i:s',
            'end_date'   => 'date|date_format:Y-m-d H:i:s|after_or_equal:start_date',
        ];
    }

}
