<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\MiscellaneousController;
use App\Models\Admin;
use App\Models\BasicSettings\Basic;
use App\Models\Car;
use App\Models\Car\Category;
use App\Models\HomePage\Section;
use App\Models\Language;
use App\Models\Location\City;
use App\Models\Location\Country;
use App\Models\Location\State;
use App\Models\Vendor;
use App\Models\VendorInfo;
use Carbon\Carbon;
use Config;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Validator;

class VendorController extends Controller
{
  //index
  public function index(Request $request)
  {
    $misc = new MiscellaneousController();

    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();
    $information['language'] = $language;

    $information['pageHeading'] = $misc->getPageHeading($language);

    $information['seoInfo'] = $language->seoInfo()->select('meta_keywords_vendor_page', 'meta_description_vendor_page')->first();
    $name = $location = $city = $state = $country = null;

    $cityIds = [];
    if ($request->filled('city')) {
      $city = $request->city;
      $city_content = City::where([['language_id', $language->id], ['slug', $city]])->first();
      if (!empty($city_content)) {
        $city_id = $city_content->id;
        $contents = VendorInfo::where('language_id', $language->id)
          ->where('city', $city_id)
          ->get()
          ->pluck('vendor_id');
        foreach ($contents as $content) {
          if (!in_array($content, $cityIds)) {
            array_push($cityIds, $content);
          }
        }
      }
    }
    $stateIds = [];
    if ($request->filled('state')) {
      $state = $request->state;
      $state_content = State::where([['language_id', $language->id], ['slug', $state]])->first();
      if (!empty($state_content)) {
        $state_id = $state_content->id;
        $contents = VendorInfo::where('language_id', $language->id)
          ->where('state', $state_id)
          ->get()
          ->pluck('vendor_id');
        foreach ($contents as $content) {
          if (!in_array($content, $stateIds)) {
            array_push($stateIds, $content);
          }
        }
      }
    }

    $countryIds = [];
    if ($request->filled('country')) {
      $country = $request->country;
      $country_content = Country::where([['language_id', $language->id], ['slug', $country]])->first();
      if (!empty($country_content)) {
        $country_id = $country_content->id;
        $contents = VendorInfo::where('language_id', $language->id)
          ->where('country', $country_id)
          ->get()
          ->pluck('vendor_id');
        foreach ($contents as $content) {
          if (!in_array($content, $countryIds)) {
            array_push($countryIds, $content);
          }
        }
      }
    }


    $vendorIds = [];
    if ($request->filled('name')) {
      $name = $request->name;
      $u_infos = Vendor::where('vendors.username', 'like', '%' . $name . '%')->get();
      $v_infos = VendorInfo::where([['vendor_infos.name', 'like', '%' . $name . '%'], ['language_id', $language->id]])->get();

      foreach ($u_infos as $info) {
        if (!in_array($info->id, $vendorIds)) {
          array_push($vendorIds, $info->id);
        }
      }
      foreach ($v_infos as $v_info) {
        if (!in_array($v_info->vendor_id, $vendorIds)) {
          array_push($vendorIds, $v_info->vendor_id);
        }
      }
    }

    if ($request->filled('location')) {
      $location = $request->location;
    }


    if ($request->filled('location')) {
      $vendor_contents = VendorInfo::where('country', 'like', '%' . $location . '%')
        ->orWhere('city', 'like', '%' . $location . '%')
        ->orWhere('state', 'like', '%' . $location . '%')
        ->orWhere('zip_code', 'like', '%' . $location . '%')
        ->orWhere('address', 'like', '%' . $location . '%')
        ->get();
      foreach ($vendor_contents as $vendor_content) {
        if (!in_array($vendor_content->vendor_id, $vendorIds)) {
          array_push($vendorIds, $vendor_content->vendor_id);
        }
      }
    }

    $information['bgImg'] = asset('assets/img/' . @$misc->getBreadcrumb()->breadcrumb);
    $vendors = Vendor::join('memberships', 'memberships.vendor_id', 'vendors.id')
      ->where([
        ['memberships.status', 1],
        ['memberships.start_date', '<=', Carbon::now()->format('Y-m-d')],
        ['memberships.expire_date', '>=', Carbon::now()->format('Y-m-d')],
      ])
      ->where('vendors.status', 1)
      ->when($name, function ($query) use ($vendorIds) {
        return $query->whereIn('vendors.id', $vendorIds);
      })
      ->when($location, function ($query) use ($vendorIds) {
        return $query->whereIn('vendors.id', $vendorIds);
      })
      ->when($city, function ($query) use ($cityIds) {
        return $query->whereIn('vendors.id', $cityIds);
      })
      ->when($state, function ($query) use ($stateIds) {
        return $query->whereIn('vendors.id', $stateIds);
      })
      ->when($country, function ($query) use ($countryIds) {
        return $query->whereIn('vendors.id', $countryIds);
      })
      ->where('vendors.id', '!=', 0)
      ->select('vendors.*', 'vendors.id as vendorId')
      ->paginate(10);

    $vendors->transform(function ($vendor) {
      $vendor->photo = HelperController::getImagePath(
        'assets/admin/img/vendor-photo/',
        $vendor->photo
      );
      return $vendor;
    });

    $information['vendors'] = $vendors;

    $information['countries'] = Country::where('language_id', $language->id)
      ->orderBy('name', 'asc')
      ->get();

    $countryContent = null;
    $stateContent = null;
    $countryId = null;

    // Get selected country (if any)
    if ($country) {
      $countryContent = Country::where([
        ['language_id', $language->id],
        ['slug', $country]
      ])->first();

      $countryId = optional($countryContent)->id;

      $information['states'] = State::where('language_id', $language->id)
        ->when($countryId, fn($q) => $q->where('country_id', $countryId))
        ->orderBy('name', 'asc')
        ->get();
    } else {
      $information['states'] = State::where('language_id', $language->id)
        ->orderBy('name', 'asc')
        ->get();
    }

    if ($state) {
      $stateContent = State::where([
        ['language_id', $language->id],
        ['slug', $state]
      ])->first();

      $information['cities'] = City::where('language_id', $language->id)
        ->when(optional($stateContent)->id, fn($q, $stateId) => $q->where('state_id', $stateId))
        ->orderBy('name', 'asc')
        ->get();
    } else {
      $information['cities'] = City::where('language_id', $language->id)
        ->when($countryId, fn($q) => $q->where('country_id', $countryId))
        ->orderBy('name', 'asc')
        ->get();
    }
    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }
  //details 
  public function details(Request $request)
  {

    $misc = new MiscellaneousController();

    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();
    $information['language'] = $language;

    $information['bgImg'] = asset('assets/img/' . @$misc->getBreadcrumb()->breadcrumb);
    $information['pageHeading'] = $misc->getPageHeading($language);

    if ($request->username == 'admin') {
      $vendor = Admin::first();
      $vendor_id = 0;
      $vendor->photo = HelperController::getImagePath('assets/img/admins/', $vendor->image);
      $information['total_cars'] = Car::where('vendor_id', 0)->get()->count();
    } else {
      $vendor = Vendor::join('memberships', 'memberships.vendor_id', 'vendors.id')
        ->where([
          ['memberships.status', 1],
          ['memberships.start_date', '<=', Carbon::now()->format('Y-m-d')],
          ['memberships.expire_date', '>=', Carbon::now()->format('Y-m-d')],
        ])
        ->where('vendors.username', $request->username)
        ->select('vendors.*')
        ->first();

      if (!$vendor) {
        return response()->json([
          'success' => false,
          'message' => 'No Vendor Found'
        ]);
      }

      $vendor->photo = HelperController::getImagePath('assets/admin/img/vendor-photo/', $vendor->photo);
      $vendorInfo = VendorInfo::where([['vendor_id', $vendor->id], ['language_id', $language->id]])->first();
      $information['vendorInfo'] = $vendorInfo;
      $vendor_id = $vendor->id;
    }
    $information['vendor'] = $vendor;

    $information['categories'] = Category::where([['language_id', $language->id], ['status', 1]])->get();

    $information['all_cars'] = Car::join('car_contents', 'car_contents.car_id', 'cars.id')
      ->join('memberships', 'cars.vendor_id', '=', 'memberships.vendor_id')
      ->join('vendors', 'cars.vendor_id', '=', 'vendors.id')

      ->join('categories', 'car_contents.category_id', '=', 'categories.id')
      ->leftJoin('cities', 'cities.id', '=', 'car_contents.city_id')
      ->leftJoin('states', 'states.id', '=', 'car_contents.state_id')
      ->leftJoin('countries', 'countries.id', '=', 'car_contents.country_id')

      ->leftJoin('car_conditions', 'car_conditions.id', '=', 'car_contents.car_condition_id')
      ->leftJoin('brands', 'brands.id', '=', 'car_contents.brand_id')
      ->leftJoin('car_models', 'car_models.id', '=', 'car_contents.car_model_id')
      ->leftJoin('fuel_types', 'fuel_types.id', '=', 'car_contents.fuel_type_id')
      ->leftJoin('transmission_types', 'transmission_types.id', '=', 'car_contents.transmission_type_id')
      ->where([
        ['memberships.status', '=', 1],
        ['memberships.start_date', '<=', Carbon::now()->format('Y-m-d')],
        ['memberships.expire_date', '>=', Carbon::now()->format('Y-m-d')]
      ])
      ->where([['vendors.status', 1], ['cars.status', 1]])
      ->where('car_contents.language_id', $language->id)
      ->where('cars.vendor_id', $vendor_id)
      ->orderBy('cars.id', 'desc')
      ->limit(8)
      ->select(
        'cars.*',
        'car_contents.title',
        'car_contents.brand_id',
        'car_contents.car_model_id',
        'car_contents.slug',
        'car_contents.category_id',
        'car_contents.description',
        'cities.name as city_name',
        'states.name as state_name',
        'countries.name as country_name',
        'categories.name as category_name',
        'car_conditions.name as car_condition',
        'brands.name as brand_name',
        'car_models.name as car_model',
        'fuel_types.name as fuel_type',
        'transmission_types.name as transmission_type'
      )
      ->get()
      ->map(function ($list) use ($language) {
        return HelperController::formatCarForApi($list, $language->id);
      });



    Car::where([['cars.vendor_id', $vendor_id], ['cars.status', 1]])
      ->orderBy('id', 'desc')
      ->get();

    $secInfo = Section::query()->select('subscribe_section_status')->first();
    $information['secInfo'] = $secInfo;
    $information['currencyInfo'] = $this->getCurrencyInfo();
    $information['info'] = Basic::select('google_recaptcha_status')->first();
    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  //contact
  public function contact(Request $request)
  {
    $rules = [
      'name' => 'required',
      'email' => 'required|email:rfc,dns',
      'subject' => 'required',
      'message' => 'required'
    ];
    $info = Basic::select('google_recaptcha_status')->first();
    if ($info->google_recaptcha_status == 1) {
      $rules['g-recaptcha-response'] = 'required|captcha';
    }
    $messageArray = [];

    if ($info->google_recaptcha_status == 1) {
      $messageArray['g-recaptcha-response.required'] = 'Please verify that you are not a robot.';
      $messageArray['g-recaptcha-response.captcha'] = 'Captcha error! try again later or contact site admin.';
    }

    $validator = Validator::make($request->all(), $rules, $messageArray);
    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }


    $be = Basic::select('smtp_status', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name')->firstOrFail();

    $c_message = nl2br($request->message);
    $msg = "<h4>Name : $request->name</h4>
            <h4>Email : $request->email</h4>
            <p>Message : </p> 
            <p>$c_message</p>
            ";

    $data = [
      'to' => $request->email,
      'subject' => $request->subject,
      'message' => $msg,
    ];

    if ($be->smtp_status == 1) {
      try {
        $smtp = [
          'transport' => 'smtp',
          'host' => $be->smtp_host,
          'port' => $be->smtp_port,
          'encryption' => $be->encryption,
          'username' => $be->smtp_username,
          'password' => $be->smtp_password,
          'timeout' => null,
          'auth_mode' => null,
        ];
        Config::set('mail.mailers.smtp', $smtp);
      } catch (\Exception $e) {
        Session::flash('error', $e->getMessage());
        return back();
      }
    }
    try {
      if ($be->smtp_status == 1) {
        Mail::send([], [], function (Message $message) use ($data, $be) {
          $fromMail = $be->from_mail;
          $fromName = $be->from_name;
          $message->to($data['to'])
            ->subject($data['subject'])
            ->from($fromMail, $fromName)
            ->html($data['message'], 'text/html');
        });
      }
      return response()->json([
        'status' => 'success',
        'message' => __('Message sent successfully')
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => __("Something went wrong!")
      ]);
    }
  }
}
