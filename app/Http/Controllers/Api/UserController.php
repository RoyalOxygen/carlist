<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\MiscellaneousController;
use App\Http\Helpers\BasicMailer;
use App\Models\BasicSettings\Basic;
use App\Models\BasicSettings\MailTemplate;
use App\Models\Car;
use App\Models\Car\Wishlist;
use App\Models\Language;
use App\Models\User;
use App\Rules\MatchEmailRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
  public function login(Request $request)
  {
    $misc = new MiscellaneousController();

    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    $data['pageHeading'] = $misc->getPageHeading($language);
    $data['bgImg'] = asset('assets/img/' . @$misc->getBreadcrumb()->breadcrumb);

    // get the status of digital product (exist or not in the cart)
    if (!empty($request->input('digital_item'))) {
      $data['digitalProductStatus'] = $request->input('digital_item');
    }

    $data['bs'] = Basic::query()->select('google_recaptcha_status', 'facebook_login_status', 'google_login_status')->first();

    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }

  //login submit
  public function loginSubmit(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'username' => 'required',
      'password' => 'required'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'validation_error',
        'errors' => $validator->errors()
      ], 422);
    }

    $credentials = $request->only('username', 'password');

    if (!Auth::attempt($credentials)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Invalid credentials'
      ], 401);
    }

    $user = Auth::guard('sanctum')->user();
    if (is_null($user->email_verified_at)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Please verify your email address.'
      ], 403);
    }

    if ($user->status == 0) {
      return response()->json([
        'status' => 'error',
        'message' => 'Sorry, your account has been deactivated.'
      ], 403);
    }

    $user->tokens()->where('name', 'customer-login')->delete();
    $token = $user->createToken('customer-login')->plainTextToken;

    $user->image = asset('assets/img/users/' . $user->image);
    if (is_null($user->image)) {
      $user->image = null;
    }

    return response()->json([
      'status' => 'success',
      'user' => $user,
      'token' => $token
    ], 200);
  }

  /**
   * Sign up method for user registration.
   */
  public function signup(Request $request)
  {
    $misc = new MiscellaneousController();

    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    $data['pageHeading'] = $misc->getPageHeading($language);

    $data['bgImg'] = asset('assets/img/' . @$misc->getBreadcrumb()->breadcrumb);

    $data['bs'] = Basic::query()->select('google_recaptcha_status', 'facebook_login_status', 'google_login_status')->first();

    $data['recaptchaInfo'] = Basic::select('google_recaptcha_status')->first();

    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }


  /**
   * Sign up method for user registration submission.
   */
  public function signupSubmit(Request $request)
  {
    $info = Basic::select('google_recaptcha_status', 'website_title')->first();

    // validation start
    $rules = [
      'username' => 'required|unique:users|max:255',
      'email' => 'required|email:rfc,dns|unique:users|max:255',
      'password' => 'required|confirmed',
      'password_confirmation' => 'required'
    ];

    if ($info->google_recaptcha_status == 1) {
      $rules['g-recaptcha-response'] = 'required|captcha';
    }

    $messages = [];
    if ($info->google_recaptcha_status == 1) {
      $messages['g-recaptcha-response.required'] = __('Please verify that you are not a robot.');
      $messages['g-recaptcha-response.captcha'] = __('Captcha error! try again later or contact site admin.');
    }

    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }
    // validation end

    $user = new User();
    $user->username = $request->username;
    $user->email = $request->email;
    $user->status = 1;
    $user->password = Hash::make($request->password);
    $user->save();

    // get the mail template information from db
    $mailTemplate = MailTemplate::query()->where('mail_type', '=', 'verify_email')->first();
    $mailData['subject'] = $mailTemplate->mail_subject;
    $mailBody = $mailTemplate->mail_body;

    $link = '<a href=' . url("user/signup-verify/" . $user->id) . '>Click Here</a>';

    $mailBody = str_replace('{username}', $user->username, $mailBody);
    $mailBody = str_replace('{verification_link}', $link, $mailBody);
    $mailBody = str_replace('{website_title}', $info->website_title, $mailBody);

    $mailData['body'] = $mailBody;

    $mailData['recipient'] = $user->email;

    $mailData['sessionMessage'] = __('A verification mail has been sent to your email address');

    BasicMailer::sendMail($mailData);

    $queryResult['authUser'] = $user;

    return response()->json([
      'success' => true,
      'message' => __('A verification mail has been sent to your email address'),
      'data' => $queryResult
    ]);
  }

  public function facebookLogin(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'access_token' => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json(['status' => 'error', 'message' => 'Access token is required.'], 422);
    }

    try {
      $facebookUser = Socialite::driver('facebook')->userFromToken($request->access_token);

      $user = User::where('provider', 'facebook')->where('provider_id', $facebookUser->id)->first();

      if (!$user) {
        $user = User::where('email', $facebookUser->getEmail())->first();

        if (!$user) {
          $avatarUrl = $facebookUser->getAvatar();
          $avatarName = $facebookUser->getId() . '.jpg';

          $path = public_path('assets/img/users/');
          file_put_contents($path . $avatarName, file_get_contents($avatarUrl));

          $user = User::create([
            'name' => $facebookUser->getName(),
            'username' => $facebookUser->getId(),
            'email' => $facebookUser->getEmail(),
            'email_verified_at' => now(),
            'image' => $avatarName,
            'status' => 1,
            'provider' => 'facebook',
            'provider_id' => $facebookUser->getId(),
            'password' => bcrypt(\Str::random(12)),
          ]);
        } else {
          $user->update([
            'provider' => 'facebook',
            'provider_id' => $facebookUser->getId(),
          ]);
        }
      }

      if ($user->status != 1) {
        return response()->json(['status' => 'error', 'message' => 'Account is deactivated'], 403);
      }

      $token = $user->createToken('facebook-token')->plainTextToken;

      return response()->json([
        'status' => 'success',
        'user' => $user,
        'token' => $token,
      ]);
    } catch (\Exception $e) {
      return response()->json(['status' => 'error', 'message' => 'Invalid access token'], 401);
    }
  }

  //edit profile
  public function editProfile(Request $request)
  {
    $misc = new MiscellaneousController();
    $data['bgImg'] = asset('assets/img/' . @$misc->getBreadcrumb()->breadcrumb);

    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();
    $data['pageHeading'] = $misc->getPageHeading($language);

    $user = Auth::guard('sanctum')->user();
    $user->image = asset('assets/img/users/' . $user->image);
    $data['authUser'] = $user;

    return response()->json([
      'status' => 'success',
      'data' => $data
    ]);
  }

  //update profile
  public function updateProfile(Request $request)
  {

    $request->validate([
      'name' => 'required',
      'username' => [
        'required',
        'alpha_dash',
        Rule::unique('users', 'username')->ignore(Auth::guard('sanctum')->user()),
      ],
      'email' => [
        'required',
        'email',
        Rule::unique('users', 'email')->ignore(Auth::guard('sanctum')->user())
      ],
    ]);

    $authUser = Auth::guard('sanctum')->user();
    $in = $request->all();
    $file = $request->file('image');
    if ($file) {
      $extension = $file->getClientOriginalExtension();
      $directory = public_path('assets/img/users/');
      $fileName = uniqid() . '.' . $extension;
      @mkdir($directory, 0775, true);
      $file->move($directory, $fileName);
      $in['image'] = $fileName;
    }

    $authUser->update($in);

    return response()->json([
      'success' => true,
      'message' => 'Your profile has been updated successfully'
    ]);
  }

  //change password
  public function changePassword(Request $request)
  {
    $misc = new MiscellaneousController();

    $breadcrumb = null;
    if (!is_null($misc->getBreadcrumb()->breadcrumb)) {
      $breadcrumb = $misc->getBreadcrumb()->breadcrumb;
    }
    $data['bgImg'] = asset('assets/img/' . $breadcrumb);

    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();
    $data['pageHeading'] = $misc->getPageHeading($language);

    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }

  //update password
  public function updatePassword(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'current_password' => 'required',
      'new_password' => 'required|confirmed|min:6',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'errors' => $validator->errors()
      ], 422);
    }

    $user = Auth::guard('sanctum')->user();

    if (!Hash::check($request->current_password, $user->password)) {
      return response()->json([
        'errors' => ['current_password' => ['Current password is incorrect.']]
      ], 422);
    }
    $user->update([
      'password' => Hash::make($request->new_password)
    ]);


    return response()->json([
      'status' => true,
      'message' => 'Password updated successfully'
    ]);
  }


  //logout
  public function logoutSubmit(Request $request)
  {
    $request->user()->currentAccessToken()->delete();

    return response()->json([
      'status' => 'success',
      'message' => 'Logout successfully'
    ], 200);
  }

  //dashboard
  public function redirectToDashboard(Request $request)
  {
    $user = Auth::guard('sanctum')->user();

    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.'
      ], 401);
    }

    $misc = new MiscellaneousController();
    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    $breadcrumb = null;
    if (!is_null($misc->getBreadcrumb()->breadcrumb)) {
      $breadcrumb = $misc->getBreadcrumb()->breadcrumb;
    }
    $user->image = asset('assets/img/users/' . $user->image);
    $data = [
      'language' => $language,
      'bgImg' => asset('assets/img/' . $breadcrumb),
      'pageHeading' => $misc->getPageHeading($language),

      'authUser' => $user,

      // 'wishlistsCount' => Wishlist::where('user_id', $user->id)->count(),
      // 'appointmentsCount' => ServiceBooking::where('user_id', $user->id)->count(),
      // 'ordersCount' => ProductOrder::where('user_id', $user->id)->count(),
    ];

    return response()->json([
      'success' => true,
      'data' => $data,
    ]);
  }

  //wishlist
  public function wishlist(Request $request)
  {
    $misc = new MiscellaneousController();
    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();
    $data['language'] = $language;


    $breadcrumb = null;
    if (!is_null($misc->getBreadcrumb()->breadcrumb)) {
      $breadcrumb = $misc->getBreadcrumb()->breadcrumb;
    }
    $data['bgImg'] = asset('assets/img/' . $breadcrumb);


    $data['pageHeading'] = $misc->getPageHeading($language);

    $user_id = Auth::guard('sanctum')->user()->id;
    $wishlists = Wishlist::join('car_contents', 'wishlists.car_id', 'car_contents.car_id')
      ->join('cars', 'wishlists.car_id', 'cars.id')
      ->join('memberships', 'cars.vendor_id', '=', 'memberships.vendor_id')
      ->join('vendors', 'cars.vendor_id', '=', 'vendors.id')
      ->where([
        ['memberships.status', '=', 1],
        ['memberships.start_date', '<=', Carbon::now()->format('Y-m-d')],
        ['memberships.expire_date', '>=', Carbon::now()->format('Y-m-d')]
      ])
      ->where([['vendors.status', 1], ['cars.status', 1]])
      ->where('car_contents.language_id', $language->id)
      ->where('wishlists.user_id', $user_id)
      ->select('wishlists.id as wishlist_id', 'cars.id as car_id',  'car_contents.title', 'car_contents.slug')
      ->get();

    $data['wishlists'] = $wishlists;
    return response()->json([
      'success' => true,
      'data' => $data
    ]);
  }
  //add to wishlist
  public function add_to_wishlist($id)
  {
    $user_id  = Auth::guard('sanctum')->user()->id;
    $car = Car::where('id', $id)->first();

    if (is_null($car)) {
      return response()->json([
        'success' => false,
        'message' => __('Car not found')
      ]);
    }
    $check = Wishlist::where('car_id', $id)->where('user_id', $user_id)->first();

    if (!empty($check)) {
      return response()->json([
        'success' => false,
        'message' => __('You already added this car into your wishlist')
      ]);
    }

    $add = new Wishlist;
    $add->car_id = $id;
    $add->user_id = $user_id;
    $add->save();

    return response()->json([
      'success' => true,
      'message' => __('Added to your wishlist successfully')
    ]);
  }

  //remove_wishlist
  public function remove_wishlist($id)
  {
    $remove = Wishlist::where('car_id', $id)->first();
    if ($remove) {
      $remove->delete();
      $msg = __('Removed From wishlist successfully');
      $type = true;
    } else {
      $msg = __('Something went wrong!');
      $type = false;
    }

    return response()->json([
      'success' => $type,
      'message' => $msg
    ]);
  }

  /**
   * forgetPasswordMail
   */
  public function forgetPassword(Request $request)
  {
    $rules = [
      'email' => [
        'required',
        'email:rfc,dns',
        new MatchEmailRule('user')
      ]
    ];

    $info = Basic::select('google_recaptcha_status')->first();
    if ($info->google_recaptcha_status == 1) {
      $rules['g-recaptcha-response'] = 'required|captcha';
    }

    $messages = [];

    if ($info->google_recaptcha_status == 1) {
      $messages['g-recaptcha-response.required'] = __('Please verify that you are not a robot.');
      $messages['g-recaptcha-response.captcha'] = __('Captcha error! try again later or contact site admin.');
    }
    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }

    $user = User::query()->where('email', '=', $request->email)->first();


    $token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    DB::table('password_resets')->updateOrInsert(
      ['email' => $user->email],
      ['token' => Hash::make($token), 'created_at' => now()]
    );

    try {
      // get the mail template information from db
      $mailTemplate = MailTemplate::query()->where('mail_type', '=', 'reset_password')->first();
      $mailData['subject'] = $mailTemplate->mail_subject;
      $mailBody = $mailTemplate->mail_body;

      // get the website title info from db
      $info = Basic::select('website_title')->first();

      $name = $user->username;

      $link = __("Your OTP: ") . $token;

      $mailBody = str_replace('{customer_name}', $name, $mailBody);
      $mailBody = str_replace('{password_reset_link}', $link, $mailBody);
      $mailBody = str_replace('{website_title}', $info->website_title, $mailBody);

      $mailData['body'] = $mailBody;

      $mailData['recipient'] = $user->email;

      $mailData['sessionMessage'] = __('A mail has been sent to your email address');

      BasicMailer::sendMail($mailData);
    } catch (\Exception $e) {
      Log::error('Could not log password reset token: ' . $e->getMessage());
    }



    return response()->json([
      'status' => 'success',
      'message' => __('A mail has been sent to your email address')
    ]);
  }

  public function resetPasswordSubmit(Request $request)
  {
    $rules = [
      'email' => 'required|email',
      'code' => 'required',
      'new_password' => 'required|confirmed',
      'new_password_confirmation' => 'required'
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }

    // find the reset record by email
    $record = DB::table('password_resets')
      ->where('email', $request->email)
      ->first();

    if (!$record) {
      return response()->json([
        'status' => 'error',
        'message' => __('Invalid email or token')
      ], 400);
    }

    // check the token
    if (!Hash::check($request->code, $record->token)) {
      return response()->json([
        'status' => 'error',
        'message' => __('Invalid email or expired code')
      ], 400);
    }

    // update password
    User::where('email', $request->email)->update([
      'password' => Hash::make($request->new_password),
    ]);

    // delete reset record
    DB::table('password_resets')->where('email', $request->email)->delete();

    return response()->json([
      'status' => 'success',
      'message' => __('Password updated successfully')
    ]);
  }
}
