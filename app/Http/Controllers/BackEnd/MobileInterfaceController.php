<?php

namespace App\Http\Controllers\BackEnd;

use App\Models\Language;
use Illuminate\Http\Request;
use App\Http\Helpers\UploadFile;
use App\Rules\ImageMimeTypeRule;
use Illuminate\Support\Facades\DB;
use App\Models\MobileSection;
use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Models\HomePage\Hero\AppSlider;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentGateway\OnlineGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class MobileInterfaceController extends Controller
{
  //Mobile App Settings main page
  public function index(Request $request)
  {
    return view('backend.mobile-interface.index');
  }

  //home page content view and update function
  public function content(Request $request)
  {
    $Language = Language::where('code', $request->language)->firstOrFail();
    $data['langs'] = Language::get();

    $data['data'] = MobileSection::where('language_id', $Language->id)->first();
    return view('backend.mobile-interface.content', $data);
  }

  public function update(Request $request)
  {
    $rules = [
      'category_section_title' => 'max:255',
      'featured_car_section_title' => 'max:255',
      'latest_car_section_title' => 'max:255',
    ];
    if ($request->hasFile('hero_section_background_img')) {
      $rules['hero_section_background_img'] = new ImageMimeTypeRule();
    }
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->getMessageBag()->toArray()
      ], 400);
    }

    $language = Language::where('code', $request->language)->firstOrFail();
    $content = MobileSection::where('Language_id', $language->id)->first();

    if ($request->hasFile('hero_section_background_img')) {
      $newHeroImage = $request->file('hero_section_background_img');
      if (!empty($content->hero_section_background_img)) {
        $oldHeroImage = $content->hero_section_background_img;
        $heroImageName = UploadFile::update(public_path('assets/img/hero/'), $newHeroImage, $oldHeroImage);
      } else {
        $heroImageName = UploadFile::store(public_path('assets/img/hero/'), $newHeroImage);
      }
    }


    if (!empty($content)) {
      $content->Language_id = $language->id;
    } else {
      $content = new MobileSection();
      $content->Language_id = $language->id;
    }
    $content->category_section_title = $request->category_section_title;
    $content->featured_car_section_title = $request->featured_car_section_title;
    $content->latest_car_section_title = $request->latest_car_section_title;

    if (isset($heroImageName)) {
      $content->hero_section_background_img = $heroImageName;
    }
    $content->save();

    session()->flash('success', __('Updated successfully'));
    return response()->json(['status' => 'success']);
  }

  //general setting view and update function
  public function setting(Request $request)
  {
    $data['data'] = DB::table('basic_settings')->select('mobile_favicon', 'mobile_app_logo', 'has_mobile_app')
      ->first();
    $data['config'] = include(public_path('config.php'));
    return view('backend.mobile-interface.general-settings', $data);
  }

  public function settingUpdate(Request $request)
  {
    $bs = DB::table('basic_settings')->select('mobile_favicon', 'mobile_app_logo')->first();

    $rules = [
      'api_base_url' => 'required|url',
    ];

    if (is_null($bs->mobile_favicon)) {
      $rules['mobile_favicon'] = 'required|mimes:png,jpg,jpeg,svg';
    }
    if (is_null($bs->mobile_favicon)) {
      $rules['mobile_app_logo'] = 'required|mimes:png,jpg,jpeg,svg';
    }

    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return redirect()->back()->withErrors($validator->errors())->withInput();
    }

    $publicConfig = include base_path('public/config.php');
    $publicConfig['PUBLIC_API_BASE'] = $request->api_base_url;
    $configContent = "<?php\n\nreturn " . var_export($publicConfig, true) . ";\n";
    file_put_contents(base_path('public/config.php'), $configContent);


    if ($request->hasFile('mobile_favicon')) {
      if (isset($bs->mobile_favicon)) {
        $favicon = UploadFile::update(public_path('assets/img/mobile-interface/'), $request->file('mobile_favicon'), $bs->mobile_favicon);
      } else {
        $favicon = UploadFile::store(public_path('assets/img/mobile-interface/'), $request->file('mobile_favicon'));
      }
    }
    if ($request->hasFile('mobile_app_logo')) {
      if (isset($bs->mobile_app_logo)) {
        $logo = UploadFile::update(public_path('assets/img/mobile-interface/'), $request->file('mobile_app_logo'), $bs->mobile_app_logo);
      } else {
        $logo = UploadFile::store(public_path('assets/img/mobile-interface/'), $request->file('mobile_app_logo'));
      }
    }

    DB::table('basic_settings')->updateOrInsert(
      ['uniqid' => 12345],
      [
        'mobile_favicon' => $favicon ?? $bs->mobile_favicon,
        'mobile_app_logo' => $logo ?? $bs->mobile_app_logo,
        'has_mobile_app' =>  $request->has_mobile_app,
      ]
    );

    return redirect()->back()->with('success', __('Updated Successfully'));
  }

  //payment gateways view and update function
  public function paymentGateways()
  {
    $data['data'] = include(public_path('config.php'));

    $gateways = [
      'paypal',
      'paystack',
      'flutterwave',
      'mercadopago',
      'mollie',
      'stripe',
      'authorize.net',
      'phonepe',
      'paytabs',
      'midtrans',
      'toyyibpay',
      'myfatoorah',
      'xendit',
      'monnify',
      'now_payments',
      'razorpay'
    ];

    foreach ($gateways as $gateway) {
      $key = str_replace('.', '_', $gateway);

      $data[$key] = OnlineGateway::where('keyword', $gateway)
        ->select('mobile_status', 'mobile_information')
        ->first();
    }
    return view('backend.mobile-interface.gateway', $data);
  }

  //plugins view function
  public function plugins()
  {
    $data = DB::table('basic_settings')->select('app_firebase_json_file')
      ->first();
    return view('backend.mobile-interface.plugins', compact('data'));
  }

  public function updateFirebase(Request $request)
  {
    $request->validate([
      'app_firebase_json_file' => 'required|mimes:json',
    ], [
      'app_firebase_json_file.required' => __('The admin sdk json file is required.'),
      'app_firebase_json_file.mimes' => __('Only json files are supported.'),
    ]);

    $bs = DB::table('basic_settings')
      ->select('app_firebase_json_file')
      ->where('uniqid', 12345)
      ->first();

    // if json file already exists and user wants to update it
    if ($request->hasFile('app_firebase_json_file') && !is_null($bs->app_firebase_json_file)) {
      $file = UploadFile::update(public_path('assets/file/'), $request->file('app_firebase_json_file'), $bs->app_firebase_json_file);
    }

    //if json file doesn't exist and user wants to upload it
    if ($request->hasFile('app_firebase_json_file') && is_null($bs->app_firebase_json_file)) {
      $file = UploadFile::store(public_path('assets/file/'), $request->file('app_firebase_json_file'));
    }

    DB::table('basic_settings')->updateOrInsert(
      ['uniqid' => 12345],
      [
        'app_firebase_json_file' => $request->hasFile('app_firebase_json_file') ? $file : $bs->app_firebase_json_file,
      ]
    );
    session()->flash('success', __('Updated successfully!'));
    return redirect()->back();
  }
  public function sliders(Request $request)
  {
    $language = Language::query()->where('code', '=', $request->language)->firstOrFail();
    $information['language'] = $language;

    $information['sliders'] = $language->appslider()->orderByDesc('id')->get();

    $information['langs'] = Language::all();

    return view('backend.mobile-interface.slider-version.index', $information);
  }

  public function sliderStore(Request $request)
  {
    $rules = [
      'language_id' => 'required',
      'background_image' => [
        'required',
        new ImageMimeTypeRule()
      ]
    ];

    $message = [
      'language_id.required' => 'The language field is required.'
    ];

    $validator = Validator::make($request->all(), $rules, $message);

    if ($validator->fails()) {
      return Response::json([
        'errors' => $validator->getMessageBag()
      ], 400);
    }

    // store image in storage
    $imgName = UploadFile::store(public_path('assets/img/hero/sliders/'), $request->file('background_image'));

    AppSlider::query()->create($request->except('background_image') + [
      'background_image' => $imgName
    ]);

    $request->session()->flash('success', 'New slider added successfully!');

    return Response::json(['status' => 'success'], 200);
  }

  public function sliderUpdate(Request $request)
  {
    $rule = [
      'background_image' => $request->hasFile('background_image') ? new ImageMimeTypeRule() : ''
    ];

    $validator = Validator::make($request->all(), $rule);

    if ($validator->fails()) {
      return Response::json([
        'errors' => $validator->getMessageBag()
      ], 400);
    }

    $slider = AppSlider::query()->find($request['id']);

    if ($request->hasFile('background_image')) {
      $newImage = $request->file('background_image');
      $oldImage = $slider->background_image;
      $imgName = UploadFile::update(public_path('assets/img/hero/sliders/'), $newImage, $oldImage);
    }

    $slider->update($request->except('background_image') + [
      'background_image' => $request->hasFile('background_image') ? $imgName : $slider->background_image
    ]);

    $request->session()->flash('success', 'Slider updated successfully!');

    return Response::json(['status' => 'success'], 200);
  }

  public function destroySlider($id)
  {
    $slider = AppSlider::query()->find($id);

    @unlink(public_path('assets/img/hero/sliders/') . $slider->background_image);

    $slider->delete();

    return redirect()->back()->with('success', 'Slider deleted successfully!');
  }
}
