<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PanelMessageRequest extends FormRequest
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
            'title' => [Rule::requiredIf(is_null($this->panelMessage)),'string','max:255','min:3'],
            'short_content' => [Rule::requiredIf(is_null($this->panelMessage)),'string','max:255','min:3'] ,
            'body' => [Rule::requiredIf(is_null($this->panelMessage)),'string','max:1000','min:3'] ,
            'status' => 'boolean',
            'is_open' => 'boolean',
        ];
    }
}
