<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class ExtendRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'price' => 'required',
            'package_id' => 'required',
            'start_date' => 'required',
            'expire_date' => 'required',
            'payment_method' => $this->price != 0 ? 'required' : '',
            'receipt' => $this->is_receipt == 1 ? 'required | mimes:jpeg,jpg,png' : '',
            'cardNumber' => 'sometimes|required',
            'month' => 'sometimes|required',
            'year' => 'sometimes|required',
            'cardCVC' => 'sometimes|required',
            'identity_number'=> $this->payment_method == 'Iyzico' ? 'required':''
        ];
    }
    public function messages()
    {
        return [
            'receipt.required' => __('Receipt file is required for offline payment') . '.',
            'receipt.mimes' => __('Receipt file must be an image (jpeg, jpg, or png format only)') . '.',
        ];
    }
}
