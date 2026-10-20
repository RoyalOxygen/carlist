<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Car;
use App\Models\Car\CarCondition;
use App\Models\Car\Category;
use App\Models\Language;
use App\Models\MobileSection;
use App\Models\PaymentGateway\OnlineGateway;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
  public function getBasic()
  {
    $basicData = DB::table('basic_settings')
      ->select('primary_color', 'mobile_app_logo', 'mobile_favicon', 'base_currency_text', 'base_currency_rate')
      ->first();

    $basicData->mobile_app_logo = asset('assets/img/mobile-interface/' . $basicData->mobile_app_logo);
    $basicData->mobile_favicon = asset('assets/img/mobile-interface/' . $basicData->mobile_favicon);

    $data['basic_data'] = $basicData;
    $data['languages'] = Language::all();

    // $data['online_gateways'] = DB::table('online_gateways')
    //     ->where('mobile_status', 1)
    //     ->whereIn('keyword', [
    //         'phonepe',
    //         'mercadopago',
    //         'myfatoorah',
    //         'midtrans',
    //         'authorize.net',
    //         'toyyibpay',
    //         'xendit',
    //         'mollie',
    //         'paystack',
    //         'flutterwave',
    //         'stripe',
    //         'paypal',
    //         'razorpay',
    //         'monnify',
    //         'now_payments',
    //         'razorpay'
    //     ])
    //     ->select('id', 'name', 'keyword')
    //     ->get();

    // $data['offline_gateways'] = DB::table('offline_gateways')
    //     ->where('status', 1)
    //     ->orderBy('serial_number', 'asc')
    //     ->select('id', 'name', 'short_description', 'instructions', 'has_attachment')
    //     ->get();

    // $stripe = OnlineGateway::where('keyword', 'stripe')->first();
    // $stripeInfo = $stripe ? json_decode($stripe->mobile_information, true) : null;
    // $data['stripe_public_key'] = $stripeInfo['key'] ?? null;

    // $razorpay = OnlineGateway::where('keyword', 'razorpay')->first();
    // $data['razorpayInfo'] = $razorpay ? json_decode($razorpay->mobile_information, true) : null;

    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }

  public function index(Request $request)
  {
    $language = HelperController::getLanguage($request);

    //home page section content
    $sectionContent = [];
    $sectionContent = MobileSection::where('language_id', $language->id)
      ->first();
    if (!is_null($sectionContent)) {
      $sectionContent->hero_section_background_img = isset($sectionContent->hero_section_background_img) ? asset('assets/img/hero/' . $sectionContent->hero_section_background_img) : null;
    }
    $data['sectionContent'] = $sectionContent;

    $categories = Category::where('language_id', $language->id)
      ->where('status', 1)
      ->orderBy('serial_number', 'asc')
      ->take(10)
      ->get()
      ->transform(function ($category) {
        $category->image = $category->image
          ? asset('assets/admin/img/car-category/' . $category->image)
          : asset('assets/img/noimage.jpg');

        return $category;
      });

    $data['car_categories'] = $categories;

    $data['seoInfo'] = $language->seoInfo()->select('meta_keyword_home', 'meta_description_home')->first();

    $data['featured_cars'] = Car::join('car_contents', 'car_contents.car_id', 'cars.id')
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
      ->where([['vendors.status', 1], ['cars.is_featured', 1], ['cars.status', 1]])
      ->where('car_contents.language_id', $language->id)
      ->inRandomOrder()
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
    $data['latest_cars'] = Car::join('car_contents', 'car_contents.car_id', 'cars.id')
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

    $data['car_conditions'] = CarCondition::where('language_id', $language->id)->where('status', 1)->orderBy('serial_number', 'asc')->get();

    //admin info
    $admin = Admin::whereNull('role_id')->first();

    if (!$admin) {
      return response()->json([
        'success' => false,
        'message' => 'Admin not found'
      ], 404);
    }
    $admin->image = asset('assets/img/admins/' . $admin->image);
    $data['admin'] = $admin;

    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }

  public function getCategories(Request $request)
  {
    $language = HelperController::getLanguage($request);

    if ($request->filled('name')) {
      $name = $request->name;
    } else {
      $name = null;
    }

    $categories = Category::where('language_id', $language->id)
      ->where('status', 1)
      ->when($name, function ($query) use ($name) {
        return $query->where('name', 'like', '%' . $name . '%');
      })
      ->orderBy('serial_number', 'asc')
      ->paginate(10);

    // Transform only the items, not the whole paginator
    $categories->getCollection()->transform(function ($category) {
      $category->image = $category->image
        ? asset('assets/admin/img/car-category/' . $category->image)
        : asset('assets/img/noimage.jpg');
      return $category;
    });

    return response()->json([
      'success' => true,
      'data'    => [
        'categories' => $categories
      ]
    ]);
  }
}
