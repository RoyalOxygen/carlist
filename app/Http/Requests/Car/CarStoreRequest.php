<?php

namespace App\Http\Requests\Car;

use App\Models\Language;
use App\Models\Location\Country;
use App\Models\Location\State;
use App\Rules\ImageMimeTypeRule;
use Illuminate\Foundation\Http\FormRequest;

class CarStoreRequest extends FormRequest
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
    public function rules()
    {
        $rules = [
            'slider_images' => 'required',
            'feature_image' => [
                'required',
                new ImageMimeTypeRule()
            ],
            'price' => 'required',
            'speed' => 'required',
            'year' => 'required',
            'mileage' => 'required',
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],

        ];

        $languages = Language::all();


        foreach ($languages as $language) {

            $property = $language->code . '_country_id';

            if ($this->$property) {
                $Statess = State::where('country_id', $property)->count();
                if ($Statess != 0) {
                    $State = true;
                } else {
                    $State = false;
                }
            } else {
                $States = State::where('language_id', $language->id)->count();
                if ($States != 0) {
                    $State = true;
                } else {
                    $State = false;
                }
            }

            $countries = Country::where('language_id', $language->id)->count();
            if ($countries != 0) {
                $country = true;
            } else {
                $country = false;
            }

            $rules[$language->code . '_title'] = 'required|max:255';
            $rules[$language->code . '_category_id'] = 'required';
            $rules[$language->code . '_car_condition_id'] = 'required';
            $rules[$language->code . '_brand_id'] = 'required';
            $rules[$language->code . '_car_model_id'] = 'required';
            $rules[$language->code . '_fuel_type_id'] = 'required';
            $rules[$language->code . '_transmission_type_id'] = 'required';
            $rules[$language->code . '_country_id'] = $country ? 'required' : '';
            $rules[$language->code . '_state_id'] = $State ? 'required' : '';
            $rules[$language->code . '_city_id'] = 'required';
            $rules[$language->code . '_address'] = 'required';
            $rules[$language->code . '_description'] = 'required|min:15';
        }

        return $rules;
    }

    public function messages()
    {
        $messageArray = [];

        $languages = Language::all();

        foreach ($languages as $language) {

            $messageArray[$language->code . '_title.required'] = __('The title field is required for') . ' ' . $language->name . ' ' . __('language') . '.';
            $messageArray[$language->code . '_title.max'] = __('The title field cannot contain more than :max characters for') . ' ' . $language->name . ' ' . __('language') . '.';

            $messageArray[$language->code . '_address.required'] = __('The address field is required for') . ' ' . $language->name . ' ' . __('language') . '.';

            $messageArray[$language->code . '_category_id.required'] = __('The category field is required for') . ' ' . $language->name . ' ' . __('language') . '.';

            $messageArray[$language->code . '_car_condition_id.required'] = __('The condition field is required for') . ' ' . $language->name . ' ' . __('language') . '.';
            $messageArray[$language->code . '_brand_id.required'] = __('The brand field is required for') . ' ' . $language->name . ' ' . __('language') . '.';

            $messageArray[$language->code . '_car_model_id.required'] = __('The model field is required for') . ' ' . $language->name . ' ' . __('language') . '.';

            $messageArray[$language->code . '_fuel_type_id.required'] = __('The fuel type field is required for') . ' ' . $language->name . ' ' . __('language') . '.';

            $messageArray[$language->code . '_transmission_type_id.required'] = __('The transmission type field is required for') . ' ' . $language->name . ' ' . __('language') . '.';

            $messageArray[$language->code . '_description.required'] = __('The description field is required for') . ' ' . $language->name . ' ' . __('language') . '.';
            $messageArray[$language->code . '_description.min'] = __('The description field must have at least :min characters for') . ' ' . $language->name . ' ' . __('language') . '.';

            $messageArray[$language->code . '_city_id.required'] = __('The city field is required for') . ' ' . $language->name . ' ' . __('language') . '.';
            $messageArray[$language->code . '_state_id.required'] = __('The state field is required for') . ' ' . $language->name . ' ' . __('language') . '.';
            $messageArray[$language->code . '_country_id.required'] = __('The country field is required for') . ' ' . $language->name . ' ' . __('language') . '.';
        }

        return $messageArray;
    }
}
