<?php

namespace App\Http\Requests\V1\Financial;

use Illuminate\Foundation\Http\FormRequest;

class WalletRequest extends FormRequest
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
        if ($this->path() === 'api/wallet/transfer') {
            return [
                'value' => 'required|integer|min:10000|max:100000000',
                'mobile' => 'required|numeric|exists:users,mobile',
            ];
        } elseif ($this->path() === 'api/wallet/confirm-transfer') {
            return [
                'transactionId' => 'required|numeric',
            ];
        } elseif ($this->path() === 'api/wallet/reject-transfer') {
            return [
                'transactionId' => 'required|numeric',
                'message' => 'required|string|min:10|max:255'
            ];
        } elseif ($this->path() === 'api/wallet/increase-by-admin' || $this->path() === 'api/wallet/decrease-by-admin') {
            return [
                'value' => 'required|integer|min:10000|max:100000000',
                'userId' => 'required|numeric',
                'message' => 'required|string|min:10|max:255'
            ];
        }
        return [];
    }
}
