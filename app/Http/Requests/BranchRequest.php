<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $branchId = $this->route('branch');

        return [
            'name' => 'required|string|max:255|unique:branches,name,'.($branchId ?? 'NULL').',id,deleted_at,NULL',
            'code' => 'nullable|string|max:32',
            'city_name' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:40',
            'status' => 'required|in:0,1',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => 422,
            'message' => $validator->errors()->first(),
        ];

        if ($this->ajax()) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
