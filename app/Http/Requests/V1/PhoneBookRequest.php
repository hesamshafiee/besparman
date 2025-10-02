<?php

namespace App\Http\Requests\V1;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PhoneBookRequest extends FormRequest
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
        if ($this->path() === 'api/phone-books/check') {
            return [
                'phone_number' => ['required', 'numeric', 'digits_between:12,12']
            ];
        }

        if ($this->path() === 'api/phone-books/batch') {
            return [
                'phone_numbers' => 'required|json'
            ];
        }

        return [
            'phone_number' => ['required', 'numeric', 'digits_between:12,12',
                Rule::unique('phone_books')
                    ->where(fn (Builder $query) => $query->where('phone_number', $this->phone_number)->where('user_id', Auth::id()))
                    ->ignore(optional($this->phoneBook)->id)
            ],
            'name' => 'required|string|min:3|max:50',
            'last_settings' => 'required|json'
        ];
    }
}
