<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CustomerSupportRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'support_image'  => 'nullable|image|mimes:jpg,jpeg,png,gif',
            'support_videos' => 'nullable|mimes:mp4,mov,avi,mkv',
        ];
    }

    public function messages()
    {
        return [
            'support_image.image' => 'The support image must be a valid image file.',
            'support_image.mimes' => 'Only JPG, JPEG, PNG, and GIF image formats are allowed.',
            'support_videos.mimes' => 'Only MP4, MOV, AVI, and MKV video formats are allowed.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status'       => false,
            'message'      => $validator->errors()->first(),
            'all_message'  => $validator->errors(),
        ];

        // API request
        if ($this->is('api/*')) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        // AJAX request
        if ($this->ajax()) {
            throw new HttpResponseException(
                response()->json([
                    'status'            => false,
                    'validation_status' => 'jquery_validation',
                    'all_message'       => $validator->errors(),
                    'event'             => 'validation',
                    'message'           => $validator->errors()->first(),
                ])
            );
        }

        // Normal web request
        throw new HttpResponseException(
            redirect()->back()->withInput()->withErrors($validator)
        );
    }
}
