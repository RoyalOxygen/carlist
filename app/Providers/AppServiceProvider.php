<?php

namespace App\Providers;

use App\Models\BasicSettings\SocialMedia;
use App\Models\HomePage\Section;
use App\Models\Language;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    //
  }

  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    Paginator::useBootstrap();

    if (!file_exists(storage_path('installed'))) {
      if (!app()->runningInConsole() && !request()->is('install') && !request()->is('install/*')) {
        header('Location: /install');
        exit;
      }

      return;
    }

    if (str_starts_with((string) config('app.url'), 'https://')) {
      URL::forceScheme('https');
    }

    if (app()->runningInConsole()) {
      return;
    }

    $data = DB::table('basic_settings')->select('favicon', 'website_title', 'logo', 'base_currency_text', 'base_currency_text_position', 'google_map_api_key_status', 'google_map_api_key', 'home_page_logo')->first();

    View::composer('backend.*', function ($view) {
      $role = null;
      if (Auth::guard('admin')->check() == true) {
        $authAdmin = Auth::guard('admin')->user();

        if (!is_null($authAdmin->role_id)) {
          $role = $authAdmin->role()->first();
        }
      }

      $language = Language::query()->where('is_default', '=', 1)->first();

      $websiteSettings = DB::table('basic_settings')->select('admin_theme_version', 'base_currency_symbol', 'base_currency_symbol_position', 'base_currency_symbol_position', 'base_currency_text_position', 'base_currency_text', 'base_currency_rate', 'theme_version', 'google_map_api_key_status', 'google_map_api_key', 'has_mobile_app')->first();

      $footerText = $language ? $language->footerContent()->first() : null;

      if (Auth::guard('admin')->check() == true) {
        $view->with('roleInfo', $role);
      }

      $view->with('defaultLang', $language);
      $view->with('settings', $websiteSettings);
      $view->with('footerTextInfo', $footerText);
    });

    View::composer('vendors.*', function ($view) {
      $language = Language::query()->where('is_default', '=', 1)->first();
      $langs = Language::query()->get();

      $footerText = $language ? $language->footerContent()->first() : null;

      $websiteSettings = DB::table('basic_settings')->select('admin_theme_version', 'base_currency_symbol', 'base_currency_symbol_position', 'base_currency_text', 'base_currency_text_position', 'base_currency_rate', 'theme_version', 'google_map_api_key_status', 'google_map_api_key', 'has_mobile_app')->first();

      $selLang = null;
      if (request()->isMethod('GET') && request()->has('language')) {
        $selLang = Language::where('code', request()->input('language'))->first();
      }

      $vendorId = Auth::guard('vendor')->user()->id ?? null;
      $vendorLangKey = $vendorId ? 'vendor_lang_' . $vendorId : null;
      $vendorLang = $vendorLangKey ? session($vendorLangKey) : null;

      if ($vendorLang) {
        $langCode = Str::after($vendorLang, 'admin_');
      } elseif (!empty($selLang)) {
        $langCode = $selLang->code;
      } else {
        $langCode = Language::where('is_default', 1)->value('code');
      }

      $direction = Language::where('code', $langCode)->value('direction');

      $view->with('defaultLang', $language);
      $view->with('langs', $langs);
      $view->with('settings', $websiteSettings);
      $view->with('footerTextInfo', $footerText);

      $view->with('vendorLangCode', $langCode);
      $view->with('vendorDirection', $direction);
    });

    View::composer('frontend.*', function ($view) {
      $basicData = DB::table('basic_settings')
        ->select('theme_version', 'footer_logo', 'footer_background_image', 'email_address', 'contact_number', 'address', 'primary_color', 'whatsapp_status', 'whatsapp_number', 'whatsapp_header_title', 'whatsapp_popup_status', 'whatsapp_popup_message', 'tawkto_status', 'tawkto_direct_chat_link', 'base_currency_symbol', 'base_currency_symbol_position', 'base_currency_text', 'base_currency_text_position', 'hero_section_video_url', 'preloader_status', 'preloader', 'shop_status')
        ->first();

      $allLanguages = Language::all();

      $locale = Session::get('currentLocaleCode');

      if (empty($locale)) {
        $language = Language::query()->where('is_default', '=', 1)->first();
      } else {
        $language = Language::query()->where('code', '=', $locale)->first();
        if (empty($language)) {
          $language = Language::query()->where('is_default', '=', 1)->first();
        }
      }

      $socialMedias = SocialMedia::query()->orderBy('serial_number', 'asc')->get();

      $siteMenuInfo = $language ? $language->menuInfo : null;

      if (is_null($siteMenuInfo)) {
        $menus = json_encode([]);
      } else {
        $menus = $siteMenuInfo->menus;
      }

      $popups = $language ? $language->announcementPopup()->where('status', 1)->orderBy('serial_number', 'asc')->get() : collect();

      $cookieAlert = $language ? $language->cookieAlertInfo()->first() : null;

      $footerSectionStatus = Section::query()->pluck('footer_section_status')->first();

      $footerData = null;
      $quickLinks = [];
      if ($footerSectionStatus == 1 && $language) {
        $footerData = $language->footerContent()->first();
        $quickLinks = $language->footerQuickLink()->orderBy('serial_number', 'asc')->get();
      }

      if (Session::has('productCart')) {
        $cartItems = Session::get('productCart');
      } else {
        $cartItems = [];
      }

      $view->with([
        'basicInfo' => $basicData,
        'allLanguageInfos' => $allLanguages,
        'currentLanguageInfo' => $language,
        'socialMediaInfos' => $socialMedias,
        'menuInfos' => $menus,
        'popupInfos' => $popups,
        'cookieAlertInfo' => $cookieAlert,
        'footerInfo' => ($footerSectionStatus == 1) ? $footerData : NULL,
        'quickLinkInfos' => ($footerSectionStatus == 1) ? $quickLinks : [],
        'cartItemInfo' => $cartItems,
        'footerSectionStatus' => $footerSectionStatus
      ]);
    });

    View::share(['websiteInfo' => $data]);
  }
}
