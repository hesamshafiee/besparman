<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommentRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if (str_contains($this->path(), 'api/comments/status') && $this->method() === "PATCH") {
            return [];
        } elseif ($this->method() === "PATCH") {
            return [
                'comment' => 'required|string|min:10|max:500',
            ];
        }

        return [
            'comment' => 'required|string|min:10|max:500',
            'comment_id' => 'numeric|exists:comments,id',
            'model' => 'required|string',
            'id' => 'required|numeric',
        ];
    }
}
