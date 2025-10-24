<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        foreach (['valid_from','valid_until'] as $key) {
            if ($this->filled($key)) {
                $raw = (string)$this->input($key);
                try {
                    $dt = \Carbon\Carbon::parse(str_replace('T', ' ', $raw), config('app.timezone'));
                    $this->merge([$key => $dt->format('Y-m-d H:i:s')]);
                } catch (\Throwable $e) {
                }
            }
        }
    }

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        $routeParam = $this->route('discount_code');
        $id = is_object($routeParam) ? ($routeParam->id ?? null) : $routeParam;
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('discount_codes', 'code')->ignore($id)
            ],
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
