<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Language;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use stdClass;

class HelperController extends Controller
{
  public static function getLanguage($request)
  {

    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first() : Language::where('is_default', 1)->first();
    return $language;
  }
  public static function formatCarForApi($list, $language)
  {
    $obj = new stdClass();
    $obj->id = $list->id;
    if ($list->vendor_id == null || $list->vendor_id == 0) {
      $admin = Admin::first();
      $obj->vendor_username  = $admin->username;
      $obj->vendor_name =  $admin->first_name . ' ' . $admin->last_name;
      $obj->vendor_image = !empty($admin->image) ? asset('assets/img/admins/' . $admin->image) : asset('assets/img/blank_user.jpg');
      $obj->author_type = 'admin';
    } else {
      $vendor = Vendor::with('vendor_info')->find($list->vendor_id);
      $vendorInfo = $vendor->vendor_info()->where('language_id', $language)->first();
      $obj->vendor_username = !empty($vendor) ? $vendor->username : "";
      $obj->vendor_name =  !empty($vendorInfo) ?  $vendorInfo->name : "";
      $obj->vendor_image = !empty($vendor) ? asset('assets/admin/img/vendor-photo/' . $vendor->photo) : asset('assets/img/blank_user.jpg');
      $obj->author_type = 'vendor';
    }

    $obj->address = implode(',', array_filter([
      $list->city_name ?? null,
      $list->state_name ?? null,
      $list->country_name ?? null,
    ]));

    $obj->previous_price = symbolPrice($list->previous_price);
    $obj->current_price = symbolPrice($list->price);
    $obj->wishlist_status =  Auth::guard('sanctum')->check() ? checkWishList($list->id, Auth::guard('sanctum')->user()->id) : 0;
    $obj->feature_image = asset('assets/admin/img/car/' .  $list->feature_image);
    if ($list->mobile_image) {
      $obj->mobile_image = asset('assets/admin/img/car/mobile-image/' .  $list->mobile_image);
    } else {
      $obj->mobile_image = null;
    }
    $obj->color = $list->color;
    $obj->title = $list->title;
    $obj->slug = $list->slug;
    $obj->category_name = $list->category_name;
    $obj->latitude = $list->latitude;
    $obj->longitude = $list->longitude;
    $obj->car_condition = $list->car_condition;
    $obj->brand = $list->brand_name;
    $obj->car_model = $list->car_model;
    $obj->fuel_type = $list->fuel_type;
    $obj->transmission_type = $list->transmission_type;
    $obj->speed = $list->speed;
    $obj->year = $list->year;
    $obj->mileage = $list->mileage;

    return $obj;
  }

  public static function getImagePath($path, $image)
  {
    if (!empty($image)) {
      return asset("$path$image");
    }
    return null;
  }

  public static function formatVendorForApi($vendor_id, $language, $type)
  {
    $obj = new stdClass();
    if ($type == 'vendor') {
      $vendor = Vendor::with('vendor_info')->find($vendor_id);
      $vendorInfo = $vendor->vendor_info()->where('language_id', $language)->first();
      $obj->vendor_username = !empty($vendor) ? $vendor->username : "";
      $obj->vendor_email  = $vendor->email;
      $obj->vendor_phone  = $vendor->phone;
      $obj->vendor_name =  !empty($vendorInfo) ?  $vendorInfo->name : "";
      $obj->vendor_image = !empty($vendor) ? asset('assets/admin/img/vendor-photo/' . $vendor->photo) : asset('assets/img/blank_user.jpg');
    } else {
      $admin = Admin::first();
      $obj->vendor_username  = $admin->username;
      $obj->vendor_email  = $admin->email;
      $obj->vendor_phone  = $admin->phone;
      $obj->vendor_name =  $admin->first_name . ' ' . $admin->last_name;
      $obj->vendor_image = !empty($admin->image) ? asset('assets/img/admins/' . $admin->image) : asset('assets/img/blank_user.jpg');
    }
    $obj->auhtor_type = $type;
    return $obj;
  }
}
