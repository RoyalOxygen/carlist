<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\BasicSettings\Basic;
use App\Models\Car;
use App\Models\Car\CarCondition;
use App\Models\Car\Category;
use App\Models\HomePage\Section;
use App\Models\Location\City;
use App\Models\Location\Country;
use App\Models\Location\State;
use App\Models\Vendor;
use App\Models\VendorInfo;
use Carbon\Carbon;
use Config;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Validator;

class VendorController extends Controller
{
    //index
    public function index(Request $request)
    {
        $misc = new MiscellaneousController();

        $language = $misc->getLanguage();
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

        $secInfo = Section::query()->select('subscribe_section_status')->first();
        $information['secInfo'] = $secInfo;

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

        $information['bgImg'] = $misc->getBreadcrumb();
        $information['vendors'] = Vendor::join('memberships', 'memberships.vendor_id', 'vendors.id')
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
            ->select('vendors.*', 'vendors.id as vendorId', 'memberships.*')
            ->paginate(10);

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
        return view('frontend.vendor.index', $information);
    }
    //details 
    public function details(Request $request)
    {

        $misc = new MiscellaneousController();

        $language = $misc->getLanguage();
        $information['language'] = $language;

        $information['bgImg'] = $misc->getBreadcrumb();
        $information['pageHeading'] = $misc->getPageHeading($language);

        if ($request->admin == true) {
            $vendor = Admin::first();
            $vendor_id = 0;
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
                ->firstOrFail();
            $vendorInfo = VendorInfo::where([['vendor_id', $vendor->id], ['language_id', $language->id]])->first();
            $information['vendorInfo'] = $vendorInfo;
            $vendor_id = $vendor->id;
        }
        $information['vendor'] = $vendor;

        $information['categories'] = Category::where([['language_id', $language->id], ['status', 1]])->get();

        $information['all_cars'] = Car::where([['cars.vendor_id', $vendor_id], ['cars.status', 1]])
            ->orderBy('id', 'desc')
            ->get();

        $secInfo = Section::query()->select('subscribe_section_status')->first();
        $information['secInfo'] = $secInfo;
        $information['currencyInfo'] = $this->getCurrencyInfo();
        $information['info'] = Basic::select('google_recaptcha_status')->first();
        return view('frontend.vendor.details', $information);
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
            return response()->json(['errors' => $validator->getMessageBag()->toArray()], 400);
        }


        $be = Basic::select('smtp_status', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name')->firstOrFail();

        $c_message = nl2br($request->message);
        $msg = "<h4>Name : $request->name</h4>
            <h4>Email : $request->email</h4>
            <p>Message : </p> 
            <p>$c_message</p>
            ";

        $data = [
            'to' => $request->vendor_email,
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
            Session::flash('message', 'Message sent successfully');
            Session::flash('alert-type', 'success');
            return 'success';
        } catch (\Exception $e) {
            Session::flash('message', 'Something went wrong.');
            Session::flash('alert-type', 'error');
            return 'success';
        }
    }
}
