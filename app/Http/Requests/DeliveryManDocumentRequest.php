<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder; 

class DeliveryManDocumentRequest extends FormRequest
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
        return [
            'id' => 'sometimes|nullable|exists:delivery_man_documents,id',
            'delivery_man_id' => 'sometimes|required|exists:users,id',
            'document_id' => [
                'sometimes',
                'required',
                Rule::unique('delivery_man_documents', 'document_id')
                    ->where(function (Builder $query) {
                        $query->where('delivery_man_id', $this->delivery_man_id)
                            ->whereNull('deleted_at'); 
                    })
                    ->ignore($this->id),
            ],
            'driver_document' => 'file|mimes:jpeg,jpg,png,pdf,doc,docx',
        ];
    }

    public function messages()
    {
        return [
            'delivery_man_id.required' => 'You must select at least one delivery man.',
            'document_id.required'     => 'You must select at least one document.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status'  => 422,
            'message' => $validator->errors()->first(),
        ];

        if (request()->is('api*')) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        if ($this->ajax()) {
            throw new HttpResponseException(
                response()->json(['status' => false, 'event' => 'validation', 'message' => $validator->errors()->first()])
            );
        } else {
            throw new HttpResponseException(
                redirect()->back()->withInput()->with('errors', $validator->errors())
            );
        }
    }
}
