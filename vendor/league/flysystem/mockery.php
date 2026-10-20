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
    URL::forceScheme('https');

    if (!app()->runningInConsole()) {
      # code...
      $data = DB::table('basic_settings')->select('favicon', 'website_title', 'logo', 'base_currency_text', 'base_currency_text_position', 'google_map_api_key_status', 'google_map_api_key', 'home_page_logo')->first();


      // send this information to only back-end view files
      View::composer('backend.*', function ($view) {
        if (Auth::guard('admin')->check() == true) {
          $authAdmin = Auth::guard('admin')->user();
          $role = null;

          if (!is_null($authAdmin->role_id)) {
            $role = $authAdmin->role()->first();
          }
        }

        $language = Language::query()->where('is_default', '=', 1)->first();

        $websiteSettings = DB::table('basic_settings')->select('admin_theme_version', 'base_currency_symbol', 'base_currency_symbol_position', 'base_currency_symbol_position', 'base_currency_text_position', 'base_currency_text', 'base_currency_rate', 'theme_version', 'google_map_api_key_status', 'google_map_api_key','has_mobile_app')->first();

        $footerText = $language->footerContent()->first();

        if (Auth::guard('admin')->check() == true) {
          $view->with('roleInfo', $role);
        }

        $view->with('defaultLang', $language);
        $view->with('settings', $websiteSettings);
        $view->with('footerTextInfo', $footerText);
      });

      // send this information to only back-end view files
      View::composer('vendors.*', function ($view) {


        $language = Language::query()->where('is_default', '=', 1)->first();
        $langs = Language::query()->get();

        $footerText = $language->footerContent()->first();

        $websiteSettings = DB::table('basic_settings')->select('admin_theme_version', 'base_currency_symbol', 'base_currency_symbol_position', 'base_currency_text', 'base_currency_text_position', 'base_currency_rate', 'theme_version', 'google_map_api_key_status', 'google_map_api_key', 'has_mobile_app')->first();


        $selLang = null;
        if (request()->isMethod('GET') && request()->has('language')) {
          $selLang = Language::where('code', request()->input('language'))->first();
        }

        // Vendor ID based session key
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

      // send this information to only front-end view files
      View::composer('frontend.*', function ($view) {
        // get basic info
        $basicData = DB::table('basic_settings')
          ->select('theme_version', 'footer_logo', 'footer_background_image', 'email_address', 'contact_number', 'address', 'primary_color', 'whatsapp_status', 'whatsapp_number', 'whatsapp_header_title', 'whatsapp_popup_status', 'whatsapp_popup_message', 'tawkto_status', 'tawkto_direct_chat_link', 'base_currency_symbol', 'base_currency_symbol_position', 'base_currency_text', 'base_currency_text_position', 'hero_section_video_url', 'preloader_status', 'preloader', 'shop_status')
          ->first();

        // get all the languages of this system
        $allLanguages = Language::all();

        // get the current locale of this website
        if (Session::has('currentLocaleCode')) {
          $locale = Session::get('currentLocaleCode');
        }

        if (empty($locale)) {
          $language = Language::query()->where('is_default', '=', 1)->first();
        } else {
          $language = Language::query()->where('code', '=', $locale)->first();
          if (empty($language)) {
            $language = Language::query()->where('is_default', '=', 1)->first();
          }
        }

        // get all the social medias
        $socialMedias = SocialMedia::query()->orderBy('serial_number', 'asc')->get();

        // get the menus of this website
        $siteMenuInfo = $language->menuInfo;

        if (is_null($siteMenuInfo)) {
          $menus = json_encode([]);
        } else {
          $menus = $siteMenuInfo->menus;
        }

        // get the announcement popups
        $popups = $language->announcementPopup()->where('status', 1)->orderBy('serial_number', 'asc')->get();

        // get the cookie alert info
        $cookieAlert = $language->cookieAlertInfo()->first();

        $footerSectionStatus = Section::query()->pluck('footer_section_status')->first();

        if ($footerSectionStatus == 1) {
          // get the footer info
          $footerData = $language->footerContent()->first();

          // get the quick links of footer
          $quickLinks = $language->footerQuickLink()->orderBy('serial_number', 'asc')->get();
        }

        // get shopping cart information from session
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


      // send this information to both front-end & back-end view files
      View::share(['websiteInfo' => $data]);
    }
  }
}
