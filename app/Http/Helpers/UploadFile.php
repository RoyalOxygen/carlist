<?php

namespace App\Http\Helpers;

class UploadFile
{
  public static function store($directory, $file)
  {
    $extension = strtolower($file->getClientOriginalExtension());
    $fileName = uniqid() . '.' . $extension;
    @mkdir($directory, 0775, true);
    $file->move($directory, $fileName);

    return $fileName;
  }

  public static function update($directory, $newFile, $oldFile)
  {
    $oldFile = basename((string) $oldFile);
    $oldPath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $oldFile;

    if ($oldFile !== '' && is_file($oldPath)) {
      @unlink($oldPath);
    }

    $extension = strtolower($newFile->getClientOriginalExtension());
    $fileName = uniqid() . '.' . $extension;
    @mkdir($directory, 0775, true);
    $newFile->move($directory, $fileName);

    return $fileName;
  }
}
