<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VehicleHistoryRequest extends FormRequest
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
            'vehicle_history_image'   => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ];
    }

    /**
     * Custom validation messages (optional).
     */
    public function messages()
    {
        return [
            'vehicle_history_image.image'   => 'Pickup signature must be an image.',
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
