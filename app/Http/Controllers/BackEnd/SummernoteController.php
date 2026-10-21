<?php

namespace App\Http\Controllers\BackEnd;

use App\Http\Controllers\Controller;
use App\Http\Helpers\UploadFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SummernoteController extends Controller
{
  public function upload(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
    ]);

    if ($validator->fails()) {
      return response()->json(['error' => 'Invalid image file.'], 422);
    }

    $imageName = UploadFile::store(public_path('assets/img/summernotes/'), $request->file('image'));

    return url('/') . '/assets/img/summernotes/' . $imageName;
  }

  public function remove(Request $request)
  {
    $image = basename((string) $request->image);

    if ($image === '' || $image !== $request->image) {
      return response()->json(['error' => 'Invalid image name.'], 422);
    }

    $path = public_path('assets/img/summernotes/') . $image;

    if (is_file($path)) {
      @unlink($path);
    }

    return response()->json(['data' => 'Image removed successfully!'], 200);
  }
}
