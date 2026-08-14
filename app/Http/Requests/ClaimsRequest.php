<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClaimsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'attachment_file' => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ];
    }

    public function messages()
    {
        return [
            'attachment_file.image' => 'The uploaded file must be an image.',
            'attachment_file.mimes' => 'Only JPG, JPEG, PNG, and GIF formats are allowed.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => false,
            'message' => $validator->errors()->first(),
            'all_message' => $validator->errors(),
        ];

        // API response
        if ($this->is('api/*')) {
            throw new HttpResponseException(response()->json($data, 422));
        }
        if ($this->ajax()) {
            throw new HttpResponseException(response()->json($data, 422));
        }
        throw new HttpResponseException(redirect()->back()->withInput()->withErrors($validator));
    }
}
