<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscountCodeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper((string)$this->input('code'))]);
        }
        if ($this->has('is_active')) {
            $this->merge(['is_active' => (bool)$this->input('is_active')]);
        }
    }

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        $id = $this->route('discount_code');
        return [
            'code' => ['required','string','max:50','regex:/^[A-Z0-9_-]+$/','unique:discount_codes,code,'.$id],
            'discount_type' => ['required','in:fixed,percentage'],
            'discount_value' => ['required','numeric','min:1'],
            'minimum_order_amount' => ['nullable','numeric','min:0'],
            'usage_limit' => ['nullable','integer','min:1'],
            'valid_from' => ['required','date'],
            'valid_until' => ['required','date','after:valid_from'],
            'is_active' => ['required','boolean'],
            'category_id' => ['required','integer','exists:categories,id'],
        ];
    }
}


