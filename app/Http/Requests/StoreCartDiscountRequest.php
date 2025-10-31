<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartDiscountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
public function authorize()
{
    return auth()->check(); // O true si no necesitás autenticación
}

public function rules()
{
    return [
        'name' => 'required|string|max:100',
        'type' => 'required|in:percentage,fixed',
        'condition_type' => 'required|in:amount,quantity',
        'discount_value' => 'required|numeric|min:0',
        'min_value' => 'required|numeric|min:0',
        'max_discount' => 'nullable|numeric|min:0',
        'usage_limit' => 'nullable|integer|min:0',
        'is_active' => 'boolean',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
    ];
}


}
