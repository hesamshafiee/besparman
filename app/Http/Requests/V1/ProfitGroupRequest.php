<?php

namespace App\Http\Requests\V1;

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
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],

            'designer_profit' => ['required', 'numeric', 'min:0', 'max:100'],
            'site_profit'     => ['required', 'numeric', 'min:0', 'max:100'],
            'referrer_profit' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Custom validation after rules.
     * Check total profit percentage equals 100.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $designer = (float) $this->input('designer_profit', 0);
            $site     = (float) $this->input('site_profit', 0);
            $referrer = (float) $this->input('referrer_profit', 0);

            $total = $designer + $site + $referrer;

            // Use rounding to avoid decimal precision issues
            if (round($total, 2) !== 100.00) {
                $validator->errors()->add(
                    'profit_total',
                    'The sum of designer_profit, site_profit and referrer_profit must be exactly 100.'
                );
            }
        });
    }
}
