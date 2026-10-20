<?php
namespace App\Services;
use App\Models\FcmToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FirebaseService{

  public static function pushNotification($title, $message,$buttonName, $buttonURL, $token)
  {
    $app_firebase_json_file= DB::table('basic_settings')
      ->where('uniqid', 12345)
      ->value('app_firebase_json_file');
    //initialize Firebase messaging service with service account
    $factory = (new Factory)
      ->withServiceAccount(public_path('assets/file/') . $app_firebase_json_file);
    $messaging = $factory->createMessaging();
    $subtitle = Str::limit($message, 100, '...');
    $body['message'] = $message;
    $body['button_name'] = $buttonName;
    $body['button_url'] = $buttonURL;

    try {
      $message = CloudMessage::withTarget('token', $token)
        ->withNotification(Notification::create($title, $subtitle))
        ->withData($body);
      $messaging->send($message);
    } catch (\Kreait\Firebase\Exception\Messaging\InvalidArgument $e) {
      FcmToken::where('token', $token)->delete();
      return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    } catch (\Exception $e) {
      return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }

    return response()->json(['status' => 'success', 'message' => 'Notification sent successfully.']);
  }
}
