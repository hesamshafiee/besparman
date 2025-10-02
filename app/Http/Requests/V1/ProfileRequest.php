<?php

namespace App\Http\Requests\V1;

use App\Traits\Province;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ProfileRequest extends FormRequest
{
    use Province;

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
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('birth_date')) {
            try {
                $this->merge([
                    'birth_date' => Carbon::parse($this->birth_date)->format('Y-m-d'),
                ]);
            } catch (\Exception $e) {
                // Leave it as-is if parsing fails; validation will catch it
            }
        }
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $cities = $this->Provinces();
        $provinces = array_keys($cities);

        return [
            'birth_date' => 'date',
            'address' => 'required|string|max:500|regex:/^[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}0-9\s,._-]+$/u',
            'postal_code' => 'required|string|max:50',
            'profession' => 'string|max:255',
            'education' => 'string|max:255',
            'ips.*' => 'required|string|min:7|max:15',
            'store_name' => 'required|string|max:70',
            'national_code' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255', Rule::in($provinces)],
            'city' => ['required', 'string', 'max:255', Rule::in($cities[$this->province] ?? [])],
            'gender' => 'required|string|max:255|in:male,female',
            'phone' => ['string', 'max:50', Rule::unique('profiles', 'phone')->ignore($this->id)],
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'name' => 'required|string|max:255|regex:/^[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}0-9\s,._-]+$/u',
            'legal_info' => 'nullable|json'
        ];
    }
}
