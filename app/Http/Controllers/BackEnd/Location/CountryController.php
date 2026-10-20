<?php

namespace App\Http\Controllers\BackEnd\Location;

use App\Http\Controllers\Controller;
use App\Models\Car\CarContent;
use Illuminate\Http\Request;
use App\Models\Language;
use App\Models\Location\City;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Location\Country;
use App\Models\Location\State;
use App\Models\VendorInfo;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class CountryController extends Controller
{
    public function index(Request $request)
    {

        $language = Language::query()->where('code', '=', $request->language)->firstOrFail();
        $information['countries'] = $language->countryInfo()->orderByDesc('id')->get();
        $information['langs'] = Language::all();
        $information['language'] = $language;

        return view('backend.location.country.index', $information);
    }

    public function store(Request $request)
    {
        $rules = [
            'language_id' => 'required',
            'name' => [
                'required',
                Rule::unique('countries')->where(function ($query) use ($request) {
                    return $query->where('language_id', $request->input('language_id'));
                }),
                'max:255',
            ],
        ];

        $message = [
            'language_id.required' => __('The language field is required.'),
            'name.required' => __('The name field is required.')
        ];

        $validator = Validator::make($request->all(), $rules, $message);

        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }
        $data = $request->except('language');
        $data['slug'] = createSlug($request->name);

        Country::create($data);

        Session::flash('success', __('Country stored successfully') . '!');

        return Response::json(['status' => 'success'], 200);
    }

    public function update(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('countries')->where(function ($query) use ($request) {
                    return $query->where('language_id', $request->input('language_id'));
                })->ignore($request->id, 'id'),
                'max:255',
            ],
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }

        $countryInfo = Country::find($request->id);

        $data = $request->except('language');
        $data['slug'] = createSlug($request->name);

        $countryInfo->update($data);

        Session::flash('success', __('Country updated successfully') . '!');

        return Response::json(['status' => 'success'], 200);
    }

    public function destroy($id)
    {
        $country = Country::query()->find($id);

        $hasCities = City::where('country_id', $id)->exists();
        $hasStates = State::where('country_id', $id)->exists();
        $hasCars = CarContent::where('country_id', $id)->exists();
        $hasVendors = VendorInfo::where('country', $id)->exists();

        if ($hasCities) {
            return redirect()->back()->with('warning', __('First delete all the cities of this Country') . '!');
        }

        if ($hasStates) {
            return redirect()->back()->with('warning', __('First delete all the states of this Country') . '!');
        }

        if ($hasVendors) {
            return redirect()->back()->with('warning', __('First delete all the vendors of this Country') . '!');
        }

        if ($hasCars) {
            return redirect()->back()->with('warning', __('First delete all the cars of this Country') . '!');
        }

        $country?->delete();
        return redirect()->back()->with('success', __('Country deleted successfully') . '!');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request['ids'];
        $errorMessage = null;

        foreach ($ids as $id) {
            $country = Country::query()->find($id);

            $hasCities = City::where('country_id', $id)->exists();
            $hasStates = State::where('country_id', $id)->exists();
            $hasCars = CarContent::where('country_id', $id)->exists();
            $hasVendors = VendorInfo::where('country', $id)->exists();

            if ($hasCities) {
                $errorMessage = __('First delete all the cities of these Country') . '!';
                break;
            }

            if ($hasStates) {
                $errorMessage = __('First delete all the states of these Country') . '!';
                break;
            }

            if ($hasVendors) {
                $errorMessage = __('First delete all the vendors of these Country') . '!';
                break;
            }

            if ($hasCars) {
                $errorMessage = __('First delete all the cars of these Country') . '!';
                break;
            }

            $country?->delete();
        }

        if ($errorMessage) {
            Session::flash('warning', $errorMessage);
        } else {
            Session::flash('success', __('Selected Informations deleted successfully') . '!');
        }

        return Response::json(['status' => 'success'], 200);
    }
}
