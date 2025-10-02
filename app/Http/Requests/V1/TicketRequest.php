<?php

namespace App\Http\Requests\V1;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'ticket_id' => 'numeric|exists:tickets,id',
            'title' => 'required_without:ticket_id|string|min:3|max:50',
            'message' => 'required|string',
            'category' => ['sometimes', 'required', Rule::in(Ticket::categories())],
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
