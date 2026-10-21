<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class ImageMimeTypeRule implements Rule
{
  /**
   * Create a new rule instance.
   *
   * @return void
   */
  public function __construct()
  {
    //
  }

  /**
   * Determine if the validation rule passes.
   *
   * @param  string  $attribute
   * @param  mixed  $value
   * @return bool
   */
  public function passes($attribute, $value)
  {
    if (!$value || !is_object($value) || !method_exists($value, 'getClientOriginalExtension')) {
      return false;
    }

    $image = $value;

    if (
      URL::current() == Route::is('admin.advertise.store_advertisement') || 
      URL::current() == Route::is('admin.advertise.update_advertisement')
    ) {
      $allowedExtensions = array('jpg', 'jpeg', 'png', 'svg', 'gif');
    } else {
      $allowedExtensions = array('jpg', 'jpeg', 'png');
    }

    $fileExtension = strtolower($image->getClientOriginalExtension());

    if (!in_array($fileExtension, $allowedExtensions, true)) {
      return false;
    }

    $mime = strtolower((string) $image->getMimeType());
    $allowedMimes = [
      'jpg' => ['image/jpeg'],
      'jpeg' => ['image/jpeg'],
      'png' => ['image/png'],
      'gif' => ['image/gif'],
      'svg' => ['image/svg+xml', 'text/svg+xml'],
      'webp' => ['image/webp'],
    ];

    return isset($allowedMimes[$fileExtension]) && in_array($mime, $allowedMimes[$fileExtension], true);
  }

  /**
   * Get the validation error message.
   *
   * @return string
   */
  public function message()
  {
    if (
      URL::current() == Route::is('admin.advertise.store_advertisement') ||
      URL::current() == Route::is('admin.advertise.update_advertisement')
    ) {
      return 'Only .jpg, .jpeg, .png, .svg and .gif file is allowed.';
    } else {
      return 'Only .jpg, .jpeg and .png file is allowed.';
    }
  }
}
