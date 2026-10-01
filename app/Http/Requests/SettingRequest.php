<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class SettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {        
        return [
            'site_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,ico',
            'site_dark_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,ico',
            'site_favicon' => 'nullable|image|mimes:jpg,jpeg,png,webp,ico,gif',
            'brand_color' => 'nullable|in:point,amber,delivery_job',
            'brand_font' => 'nullable|in:outfit,z17_strength,rubik',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $data = [
            'status' => true,
            'message' => $validator->errors()->first(),
            'all_message' =>  $validator->errors()
        ];

        if ( request()->is('api*')){
           throw new HttpResponseException( response()->json($data,422) );
        }

        if ($this->ajax()) {
            throw new HttpResponseException(response()->json($data,422));
        } else {
            throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
        }
    }
}
