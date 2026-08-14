<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class WebSiteSettingRequest  extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];

        if ($this->type == 'client_testimonial') {           
            $rules = [
                'playstore_review' => 'numeric|min:1|max:5',
                'appstore_review' => 'numeric|min:1|max:5',
                'trustpilot_review' => 'numeric|min:1|max:5',
            ];
        }

        if ($this->type == 'app_content') { 
            $rules = [
                'delivery_man_image' => 'image|mimes:jpg,jpeg,png',
                'app_logo_image' => 'image|mimes:jpg,jpeg,png',
                'playstore_image' => 'image|mimes:jpg,jpeg,png',
                'appstore_image' => 'image|mimes:jpg,jpeg,png',
            ];
        }

        if ($this->type == 'courier_recruitment_section') {           
            $rules = [
                'courier_image' => 'image|mimes:jpg,jpeg,png',
            ];
        }

        if ($this->type == 'download_app') {           
            $rules = [
                'download_app_logo' => 'image|mimes:jpg,jpeg,png',
            ];
        }

        if ($this->type == 'delivery_job') {           
            $rules = [
                'delivery_job_image' => 'image|mimes:jpg,jpeg,png',
            ];
        }

        if ($this->type == 'contact_us') {   
            $rules = [
                'contact_us_app_ss' => 'image|mimes:jpg,jpeg,png',
            ];
        } 
        
        if ($this->type == 'about_us') {            
            $rules = [
                'about_us_app_ss' => 'image|mimes:jpg,jpeg,png',
            ];
        } 
        
        if ($this->type == 'order_invoice') {
            $rules = [
                'company_logo' => 'image|mimes:jpg,jpeg,png',
            ];
        } 

        return $rules;
    }

    /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => 422,
            'message' => $validator->errors()->first(),
        ];
        
        $fields = ['title','subtitle'];
        $required_field = array_combine($fields, $fields);

        if ( request()->is('api*')){
           throw new HttpResponseException( response()->json($data,422) );
        }

        if ($this->ajax()) {
            throw new HttpResponseException(response()->json(['status' => false, 'validation_status' => 'jquery_validation', 'all_message' => $validator->errors(), 'event' => 'validation', 'required_field' => $required_field, 'message' => $validator->errors()->first()]));
        } else {
            throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
        }
    }
}
