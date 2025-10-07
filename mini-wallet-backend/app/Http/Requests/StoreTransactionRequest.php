<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
        ];
    }
}


