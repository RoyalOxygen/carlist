<?php

namespace App\Http\Controllers\BackEnd\Location;

use App\Http\Controllers\Controller;
use App\Models\Car\CarContent;
use App\Models\Language;
use App\Models\Location\City;
use App\Models\Location\Country;
use App\Models\Location\State;
use App\Models\VendorInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class StateController extends Controller
{
    public function index(Request $request)
    {

        $language = Language::query()->where('code', '=', $request->language)->firstOrFail();
        $information['countries'] = $language->countryInfo()->orderByDesc('id')->get();
        $information['states'] = $language->stateInfo()->orderByDesc('id')->get();
        $information['langs'] = Language::all();
        $information['language'] = $language;

        return view('backend.location.state.index', $information);
    }
    public function getCountry($language_id)
    {
        $countries = Country::where('language_id', $language_id)->get();
        $states = State::where('language_id', $language_id)->get();

        return response()->json([
            'status' => 'success',
            'countries' => $countries,
            'states' => $states
        ], 200);
    }

    public function store(Request $request)
    {
        $totalCountry = Country::Where('language_id', $request->m_language_id)->count();
        if ($totalCountry > 0) {
            $country = true;
        } else {
            $country = false;
        }

        $rules = [
            'm_language_id' => 'required',
            'name' => 'required',
            'country_id' => $country ? 'required' : '',
        ];

        $messages = [
            'm_language_id.required' => __('The language field is required.'),
            'name.required' => __('The name field is required.')
        ];


        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }

        $state = new State();

        // Use the correct field names here
        $state->language_id = $request->m_language_id;
        $state->name = $request->name;
        $state->slug = createSlug($request->name);
        $state->country_id = $request->country_id;
        $state->save();

        Session::flash('success', __('State stored successfully') . '!');

        return response()->json(['status' => 'success'], 200);
    }


    public function update(Request $request)
    {
        $rules = [
            'name' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }

        $stateInfo = State::find($request->id);

        $data = $request->except('language');
        $data['slug'] = createSlug($request->name);

        $stateInfo->update($data);

        Session::flash('success', __('Country updated successfully') . '!');

        return Response::json(['status' => 'success'], 200);
    }

    public function destroy($id)
    {
        $State = State::query()->find($id);

        $cities = City::where('state_id', $id)->exists();
        $car_content = CarContent::where('state_id', $id)->exists();
        $vendors = VendorInfo::where('state', $id)->exists();

        if ($cities) {
            return redirect()->back()->with('warning', __('First delete all the cities of this State') . '!');
        }

        if ($vendors) {
            return redirect()->back()->with('warning', __('First delete all the vendors of this State') . '!');
        }

        if ($car_content) {
            return redirect()->back()->with('warning', __('First delete all the cars of this State') . '!');
        }

        $State->delete();
        return redirect()->back()->with('success', __('State deleted successfully') . '!');
    }


    public function bulkDestroy(Request $request)
    {
        $ids = $request['ids'];

        $errorMessage = null;

        foreach ($ids as $id) {
            $State = State::query()->find($id);

            $hasCities = City::where('state_id', $id)->exists();
            $hasVendors = VendorInfo::where('state', $id)->exists();
            $hasCars = CarContent::where('state_id', $id)->exists();

            if ($hasCities) {
                $errorMessage = __('First delete all the cities of these State') . '!';
                break;
            }

            if ($hasVendors) {
                $errorMessage = __('First delete all the vendors of these State') . '!';
                break;
            }

            if ($hasCars) {
                $errorMessage = __('First delete all the cars of these State') . '!';
                break;
            }

            $State?->delete();
        }

        if ($errorMessage) {
            Session::flash('warning', $errorMessage);
        } else {
            Session::flash('success', __('Selected Informations deleted successfully') . '!');
        }

        return Response::json(['status' => 'success'], 200);
    }
}
