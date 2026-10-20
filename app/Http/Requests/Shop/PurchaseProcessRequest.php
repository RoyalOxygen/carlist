<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseProcessRequest extends FormRequest
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
      'billing_name' => 'required',
      'billing_email' => 'required|email:rfc,dns',
      'billing_phone' => 'required',
      'billing_city' => 'required',
      'billing_country' => 'required',
      'billing_address' => 'required',

      'shipping_name' => [Rule::requiredIf(fn() => $this->checkbox == 1)],
      'shipping_phone' => [Rule::requiredIf(fn() => $this->checkbox == 1)],
      'shipping_email' => [
        Rule::requiredIf(fn() => $this->checkbox == 1),
        'nullable',
        'email:rfc,dns'
      ],
      'shipping_city' => [Rule::requiredIf(fn() => $this->checkbox == 1)],
      'shipping_country' => [Rule::requiredIf(fn() => $this->checkbox == 1)],
      'shipping_address' => [Rule::requiredIf(fn() => $this->checkbox == 1)],

      'identity_number' => [Rule::requiredIf(fn() => $this->gateway == 'iyzico')],
      'zip_code' => [Rule::requiredIf(fn() => $this->gateway == 'iyzico')],
    ];
  }


}
