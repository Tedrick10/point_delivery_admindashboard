<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class UserRequest extends FormRequest
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
        $user_id = $this->route('users')
            ?? $this->route('deliveryman')
            ?? $this->route('sub_admin')
            ?? $this->id;

        $rules = [
            'name' => 'required|string|max:255',
            'username'  => 'sometimes|required|unique:users,username,'.$user_id,
            'email'     => 'sometimes|nullable|email|unique:users,email,'.$user_id,
            'contact_number' => 'required|max:20|unique:users,contact_number,'.$user_id,
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ];

        if ($this->routeIs('deliveryman.*', 'deliveryman.store')) {
            $rules['username'] = 'required|unique:users,username,'.$user_id;
            $rules['email'] = 'required|email|unique:users,email,'.$user_id;
            // Admin sets rider phone (required). No midnight re-entry in Rider App.
            $rules['contact_number'] = 'required|max:20|unique:users,contact_number,'.$user_id;
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        if ($this->requiresOsProfile()) {
            $rules['username'] = [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
                'unique:users,username,'.$user_id,
            ];
            $rules['os_profile.address_unit'] = 'required|string|max:255';
            $rules['os_profile.state_division'] = 'required|string|max:255';
            $rules['os_profile.township'] = 'required|string|max:255';
        }

        if (!$user_id) {
            $rules['password'] = $this->requiresOsProfile()
                ? 'required|string|min:6|confirmed'
                : 'required|string|min:6';

            if ($this->requiresOsProfile()) {
                $rules['os_profile.kpay_name'] = 'required|string|max:255';
                $rules['os_profile.kpay_no'] = 'required|string|max:50';
            }
        }

        if ($this->requiresOsProfile()) {
            $rules['approval_status'] = 'sometimes|in:pending,approved,rejected';
        }

        return $rules;
    }

    protected function requiresOsProfile(): bool
    {
        if ($this->routeIs('users.*', 'client.store')) {
            return true;
        }

        if ($this->input('user_type') === 'client') {
            return true;
        }

        return false;
    }

    protected function prepareForValidation()
    {
        if ($this->has('contact_number')) {
            $contactNumber = preg_replace('/\s+/', '', (string) $this->contact_number);
            // Dial-code-only leftovers from intlTelInput when the field is left empty.
            if ($contactNumber === '' || preg_match('/^\+\d{1,4}$/', $contactNumber)) {
                $contactNumber = null;
            }
            $this->merge(['contact_number' => $contactNumber]);
        }
    }

    public function messages()
    {
        return [
            'userProfile.dob.*'  =>'DOB is required.',
            'email.unique' => 'This email is already registered.',
            'contact_number.unique' => __('message.contact_number_already_taken'),
            'username.unique' => __('message.username_taken'),
            'username.regex' => __('message.username_invalid'),
        ];
    }

     /**
     * @param Validator $validator
     */
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
            throw new HttpResponseException(redirect()->back()->withInput()->withErrors($validator));
        }
    }
}
