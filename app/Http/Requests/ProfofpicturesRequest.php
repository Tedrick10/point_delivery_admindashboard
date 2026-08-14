<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProfofpicturesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'prof_file'   => 'nullable|array',
            'prof_file.*' => 'required|file|max:51200|mimes:jpg,jpeg,png,gif,webp,bmp,tiff,mp4,mpeg,mov,avi,wmv,flv,ogg,webm',
        ];
    }

    public function messages()
    {
        return [
            'prof_file.*.required' => 'Each media file is required.',
            'prof_file.*.file'     => 'Each item must be a valid file.',
            'prof_file.*.mimes'    => 'Only images and videos of valid formats are allowed.',
            'prof_file.*.max'      => 'Each file may not be greater than 50MB.',
        ];
    }

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

        throw new HttpResponseException(
            redirect()->back()->withInput()->withErrors($validator)
        );
    }
}
