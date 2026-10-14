<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

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
     * Active-user id being edited (route param), if any.
     */
    protected function editingUserId(): ?int
    {
        $userId = $this->route('users')
            ?? $this->route('deliveryman')
            ?? $this->route('sub_admin')
            ?? $this->id;

        return $userId !== null && $userId !== '' ? (int) $userId : null;
    }

    /**
     * Account type for this request (OS / Rider / staff).
     * Phone uniqueness is scoped per user_type so OS and Rider may share a number.
     */
    protected function contactUserType(): string
    {
        if ($this->routeIs('deliveryman.*', 'deliveryman.store')) {
            return 'delivery_man';
        }

        if ($this->requiresOsProfile()) {
            return 'client';
        }

        if ($this->routeIs('sub-admin.*', 'sub-admin.store')) {
            return (string) ($this->input('user_type') ?: 'staff');
        }

        return (string) ($this->input('user_type') ?: 'client');
    }

    /**
     * Phone unique among non-deleted users of the same user_type only.
     */
    protected function uniqueContactNumberRule(?int $ignoreUserId = null, ?string $userType = null)
    {
        $userType = $userType ?: $this->contactUserType();

        return Rule::unique('users', 'contact_number')
            ->ignore($ignoreUserId)
            ->whereNull('deleted_at')
            ->where('user_type', $userType);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $user_id = $this->editingUserId();

        $rules = [
            'name' => 'required|string|max:255',
            'username'  => 'sometimes|required|unique:users,username,'.$user_id,
            'email'     => 'sometimes|nullable|email|unique:users,email,'.$user_id,
            'contact_number' => ['required', 'max:30', $this->uniqueContactNumberRule($user_id)],
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ];

        if ($this->routeIs('deliveryman.*', 'deliveryman.store')) {
            $rules['username'] = 'required|unique:users,username,'.$user_id;
            $rules['email'] = 'sometimes|nullable|email|unique:users,email,'.$user_id;
            // Admin sets rider phone (required). No midnight re-entry in Rider App.
            $rules['contact_number'] = ['required', 'max:30', $this->uniqueContactNumberRule($user_id)];
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        if ($this->routeIs('sub-admin.*', 'sub-admin.store')) {
            $rules['username'] = 'required|unique:users,username,'.$user_id;
            $rules['email'] = 'required|email|unique:users,email,'.$user_id;
            $rules['contact_number'] = ['required', 'max:30', $this->uniqueContactNumberRule($user_id)];
            $rules['user_type'] = 'required|string|max:100';
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
        $raw = $this->input('contact_number');
        // Create form posts national digits on `phone` when JS sync did not fill contact_number.
        if ((!$this->filled('contact_number') || $raw === '' || $raw === null) && $this->filled('phone')) {
            $raw = $this->input('phone');
        }
        // intlTelInput can submit duplicate fields as an array.
        if (is_array($raw)) {
            $raw = collect($raw)->filter()->last();
        }

        if ($raw !== null && $raw !== '') {
            $contactNumber = normalizeContactNumber((string) $raw);
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
            'contact_number.required' => __('message.please_enter_contact_number'),
            'contact_number.unique' => __('message.contact_number_already_taken'),
            'username.unique' => __('message.username_taken'),
            'username.regex' => __('message.username_invalid'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $validator->errors()->has('contact_number')) {
                return;
            }

            $contact = (string) ($this->input('contact_number') ?? '');
            if ($contact === '') {
                return;
            }

            $existing = User::query()
                ->where('contact_number', $contact)
                ->where('user_type', $this->contactUserType())
                ->whereNull('deleted_at')
                ->when($this->editingUserId(), fn ($q, $id) => $q->where('id', '!=', $id))
                ->first(['id', 'name', 'user_type', 'contact_number']);

            if (! $existing) {
                return;
            }

            $typeKey = match ((string) $existing->user_type) {
                'delivery_man' => 'message.delivery_man',
                'client' => 'message.online_shop',
                'admin' => 'message.admin',
                'manager' => 'message.manager',
                'staff' => 'message.staff',
                default => null,
            };
            $typeLabel = $typeKey ? __($typeKey) : (string) $existing->user_type;

            $validator->errors()->forget('contact_number');
            $validator->errors()->add(
                'contact_number',
                __('message.contact_number_already_taken_by', [
                    'name' => $existing->name,
                    'type' => $typeLabel,
                    'phone' => $existing->contact_number,
                ])
            );
        });
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
