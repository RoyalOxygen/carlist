<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
  public function getLang(Request $request)
  {
    $code = $request->input('code');
    if(!$code){
      return response()->json([
        'status' => 'error',
        'message' => 'Language code is required',
      ], 422);
    }

    $path = resource_path('lang/' . $code . '.json');
    $langData = json_decode(file_get_contents($path), true);    
    return $langData;
  }
}
