<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            'pickup_time_signature'   => 'nullable|image|mimes:jpg,jpeg,png,gif',
            'delivery_time_signature' => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ];
    }

    /**
     * Custom validation messages (optional).
     */
    public function messages()
    {
        return [
            'pickup_time_signature.image'   => 'Pickup signature must be an image.',
            'delivery_time_signature.image' => 'Delivery signature must be an image.',
        ];
    }

    /**
     * Handle failed validation.
     */
    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status'      => false,
            'message'     => $validator->errors()->first(),
            'all_message' => $validator->errors(),
        ];

        if ($this->is('api/*')) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        if ($this->ajax()) {
            throw new HttpResponseException(response()->json($data, 422));
        }
            throw new HttpResponseException(redirect()->back()->withInput()->withErrors($validator)
        );
    }
}
