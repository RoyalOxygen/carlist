<?php

namespace App\Http\Controllers\BackEnd;

use App\Http\Controllers\Controller;
use App\Http\Requests\Language\StoreRequest;
use App\Http\Requests\Language\UpdateRequest;
use App\Models\Car\Brand;
use App\Models\CustomPage\Page;
use App\Models\CustomPage\PageContent;
use App\Models\Journal\Blog;
use App\Models\Journal\BlogInformation;
use App\Models\Language;
use App\Models\MenuBuilder;
use App\Models\Shop\Product;
use App\Models\Shop\ProductContent;
use App\Models\Shop\ProductOrder;
use App\Models\Shop\ProductPurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class LanguageController extends Controller
{
  // Constants
  const LANG_PATH = 'lang';
  const ADMIN_PREFIX = 'admin_';
  const DEFAULT_LANG = 'default.json';
  const ADMIN_DEFAULT_LANG = 'admin_default.json';

  // Define menu items as constant
  const DEFAULT_MENUS = [
    ['text' => 'Home', 'href' => '', 'icon' => 'empty', 'target' => '_self', 'title' => '', 'type' => 'home'],
    ['text' => 'Cars', 'href' => '', 'icon' => 'empty', 'target' => '_self', 'title' => '', 'type' => 'cars'],
    ['text' => 'Vendors', 'href' => '', 'icon' => 'empty', 'target' => '_self', 'title' => '', 'type' => 'vendors'],
    ['text' => 'Shop', 'href' => '', 'icon' => 'empty', 'target' => '_self', 'title' => '', 'type' => 'shop'],
    ['text' => 'Blog', 'href' => '', 'icon' => 'empty', 'target' => '_self', 'title' => '', 'type' => 'blog'],
    ['text' => 'FAQ', 'href' => '', 'icon' => 'empty', 'target' => '_self', 'title' => '', 'type' => 'faq'],
    ['text' => 'About Us', 'href' => '', 'icon' => 'empty', 'target' => '_self', 'title' => '', 'type' => 'about-us'],
    ['text' => 'Contact', 'href' => '', 'icon' => 'empty', 'target' => '_self', 'title' => '', 'type' => 'contact'],
  ];

  // Helper method to get lang path
  protected function langPath($file = '')
  {
    return resource_path(self::LANG_PATH . '/' . $file);
  }

  // Read JSON file
  protected function readJsonFile($filePath)
  {
    try {
      if (!file_exists($filePath)) {
        return [];
      }
      return json_decode(file_get_contents($filePath), true) ?? [];
    } catch (\Exception $e) {
      \Log::error('Error reading file: ' . $filePath, ['error' => $e->getMessage()]);
      return [];
    }
  }

  // Write JSON file
  protected function writeJsonFile($filePath, $data)
  {
    try {
      $directory = dirname($filePath);
      if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
      }
      file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
      return true;
    } catch (\Exception $e) {
      \Log::error('Error writing file: ' . $filePath, ['error' => $e->getMessage()]);
      return false;
    }
  }

  // Delete directory recursively
  protected function deleteDirectory($dir)
  {
    if (!is_dir($dir)) {
      return true;
    }

    $files = scandir($dir);
    foreach ($files as $file) {
      if ($file !== '.' && $file !== '..') {
        $path = $dir . '/' . $file;
        is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
      }
    }
    return @rmdir($dir);
  }

  // Copy directory recursively
  protected function copyDirectory($source, $destination)
  {
    if (!is_dir($destination)) {
      mkdir($destination, 0755, true);
    }

    $files = scandir($source);
    foreach ($files as $file) {
      if ($file !== '.' && $file !== '..') {
        $sourcePath = $source . '/' . $file;
        $destPath = $destination . '/' . $file;

        if (is_dir($sourcePath)) {
          $this->copyDirectory($sourcePath, $destPath);
        } else {
          copy($sourcePath, $destPath);
        }
      }
    }
  }

  // Create language files from default
  protected function createLanguageFiles($code)
  {
    $code = strtolower($code);
    $defaultData = $this->readJsonFile($this->langPath(self::DEFAULT_LANG));
    $adminDefaultData = $this->readJsonFile($this->langPath(self::ADMIN_DEFAULT_LANG));

    $this->writeJsonFile($this->langPath($code . '.json'), $defaultData);
    $this->writeJsonFile($this->langPath(self::ADMIN_PREFIX . $code . '.json'), $adminDefaultData);

    return true;
  }

  // Create language folder and copy admin files
  protected function createLanguageFolder($code)
  {
    $code = strtolower($code);
    $langFolderPath = $this->langPath($code);

    if (!is_dir($langFolderPath)) {
      mkdir($langFolderPath, 0755, true);
    }

    $adminSourcePath = $this->langPath(self::ADMIN_PREFIX . $code);
    if (is_dir($adminSourcePath)) {
      $this->copyDirectory($adminSourcePath, $langFolderPath);
    }
  }


  // Update validation attributes
  protected function updateValidationAttribute($newKeys, $content, $validationFilePath)
  {
    try {
      if (!file_exists($validationFilePath)) {
        \Log::warning('Validation file not found: ' . $validationFilePath);
        return;
      }

      $validation = include($validationFilePath);

      if (!isset($validation['attributes']) || !is_array($validation['attributes'])) {
        $validation['attributes'] = [];
      }

      // Update existing keys
      foreach ($newKeys as $key => $value) {
        if (!array_key_exists($key, $validation['attributes'])) {
          $validation['attributes'][$key] = $value;
        }
      }

      // Update matching keys from content
      $decodedContent = json_decode($content, true);
      if (is_array($decodedContent)) {
        foreach ($decodedContent as $key => $value) {
          if (array_key_exists($key, $validation['attributes'])) {
            $validation['attributes'][$key] = $value;
          }
        }
      }

      $validationContent = "<?php\n\nreturn " . var_export($validation, true) . ";\n";
      file_put_contents($validationFilePath, $validationContent);
    } catch (\Exception $e) {
      \Log::error('Error updating validation attributes', ['error' => $e->getMessage()]);
    }
  }


  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    $languages = Language::all();

    return view('backend.language.index', compact('languages'));
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */

  public function store(StoreRequest $request)
  {
    try {

      $languageCode = strtolower($request->code);

      // Read default language file
      $defaultLangData = $this->readJsonFile($this->langPath(self::DEFAULT_LANG));
      if (empty($defaultLangData)) {
        return response()->json([
          'status' => 'error',
          'message' => 'Default language file not found'
        ], 422);
      }

      // Create language files (frontend & admin JSON)
      if (!$this->createLanguageFiles($languageCode)) {
        return response()->json([
          'status' => 'error',
          'message' => 'Failed to create language files'
        ], 500);
      }

      // Create language folder
      $this->createLanguageFolder($languageCode);

      // Prepare language data for database
      $languageData = $request->all();
      $languageData['code'] = $languageCode;

      // Create language record in database
      $language = Language::create($languageData);

      // Create menu builder with default menus
      MenuBuilder::create([
        'language_id' => $language->id,
        'menus' => json_encode(self::DEFAULT_MENUS, JSON_UNESCAPED_UNICODE)
      ]);

      // Update validation attributes if needed
      $adminData = $this->readJsonFile($this->langPath(self::ADMIN_PREFIX . $languageCode . '.json'));
      if (!empty($adminData)) {
        $validationFilePath = $this->langPath(self::ADMIN_PREFIX . $languageCode . '/validation.php');
        $dashboardAttributes = Language::dashboardAttribute() ?? [];
        $this->updateValidationAttribute($dashboardAttributes, json_encode($adminData), $validationFilePath);
      }

      Session::flash('success', __('Language added successfully'). '!');

      return response()->json(['status' => 'success'], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => 'Failed to add language: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Make a default language for this system.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function makeDefault($id)
  {
    // first, make other languages to non-default language
    $prevDefLang = Language::query()->where('is_default', '=', 1);

    $prevDefLang->update(['is_default' => 0]);

    // second, make the selected language to default language
    $language = Language::query()->find($id);

    $language->update(['is_default' => 1]);

    return back()->with('success', $language->name . ' ' . 'is set as default language.');
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */

  public function update(UpdateRequest $request)
  {
    try {

      $language = Language::findOrFail($request->id);
      $oldCode = strtolower($language->code);
      $newCode = strtolower($request->code);

      // If language code is changed, we need to rename all files and folders
      if ($oldCode !== $newCode) {

        // ========== FRONTEND FILES ==========

        // Read old frontend JSON file
        $oldFrontendJsonPath = $this->langPath($oldCode . '.json');
        $frontendData = $this->readJsonFile($oldFrontendJsonPath);

        // Write to new frontend JSON file
        $newFrontendJsonPath = $this->langPath($newCode . '.json');
        if (!$this->writeJsonFile($newFrontendJsonPath, $frontendData)) {
          throw new \Exception('Failed to create new frontend language file');
        }

        // ========== ADMIN FILES ==========

        // Read old admin JSON file
        $oldAdminJsonPath = $this->langPath(self::ADMIN_PREFIX . $oldCode . '.json');
        $adminData = $this->readJsonFile($oldAdminJsonPath);

        // Write to new admin JSON file
        $newAdminJsonPath = $this->langPath(self::ADMIN_PREFIX . $newCode . '.json');
        if (!$this->writeJsonFile($newAdminJsonPath, $adminData)) {
          throw new \Exception('Failed to create new admin language file');
        }

        // ========== LANGUAGE FOLDERS ==========

        // Copy old language folder to new language folder
        $oldLangFolderPath = $this->langPath($oldCode);
        $newLangFolderPath = $this->langPath($newCode);

        if (is_dir($oldLangFolderPath)) {
          $this->copyDirectory($oldLangFolderPath, $newLangFolderPath);
        }

        // ========== ADMIN FOLDERS ==========

        // Copy old admin folder to new admin folder
        $oldAdminFolderPath = $this->langPath(self::ADMIN_PREFIX . $oldCode);
        $newAdminFolderPath = $this->langPath(self::ADMIN_PREFIX . $newCode);

        if (is_dir($oldAdminFolderPath)) {
          $this->copyDirectory($oldAdminFolderPath, $newAdminFolderPath);
        }

        // ========== DELETE OLD FILES ==========

        // Delete old frontend JSON
        if (file_exists($oldFrontendJsonPath)) {
          @unlink($oldFrontendJsonPath);
        }

        // Delete old admin JSON
        if (file_exists($oldAdminJsonPath)) {
          @unlink($oldAdminJsonPath);
        }

        // ========== DELETE OLD FOLDERS ==========

        // Delete old language folder
        if (is_dir($oldLangFolderPath)) {
          $this->deleteDirectory($oldLangFolderPath);
        }

        // Delete old admin folder
        if (is_dir($oldAdminFolderPath)) {
          $this->deleteDirectory($oldAdminFolderPath);
        }

        // ========== UPDATE VALIDATION ATTRIBUTES ==========

        // Update validation attributes for the new admin code
        $newValidationFilePath = $this->langPath(self::ADMIN_PREFIX . $newCode . '/validation.php');
        if (file_exists($newValidationFilePath)) {
          $dashboardAttributes = Language::dashboardAttribute() ?? [];
          $this->updateValidationAttribute($dashboardAttributes, json_encode($adminData), $newValidationFilePath);
        }
      }

      // ========== Update language table ==========

      $updateData = [
        'code' => $newCode,  
        'name' => $request->name,
        'direction' => $request->direction,
      ];

      $language->update($updateData);

      Session::flash('success', __('Language updated successfully') . '!');

      return response()->json(['status' => 'success'], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      return response()->json([
        'status' => 'error',
        'message' => 'Language not found'
      ], 404);
    } catch (\Exception $e) {

      return response()->json([
        'status' => 'error',
        'message' => 'Failed to update language: ' . $e->getMessage()
      ], 500);
    }
  }

  // add new keyword for frontend
  public function addKeyword(Request $request)
  {

    $validator = \Validator::make($request->all(), ['keyword' => 'required']);
    if ($validator->fails()) {
      return response()->json(['errors' => $validator->getMessageBag()->toArray()], 400);
    }

    try {
      $keyword = $request->keyword;
      $newKeyData = [$keyword => $keyword];

      // Add to all existing language files
      $languages = Language::all();
      foreach ($languages as $language) {
        $langCode = strtolower($language->code);
        $filePath = $this->langPath($langCode . '.json');
        $data = $this->readJsonFile($filePath);
        $data = array_merge($newKeyData, $data);
        $this->writeJsonFile($filePath, $data);
      }

      // Add to default.json
      $defaultData = $this->readJsonFile($this->langPath(self::DEFAULT_LANG));
      $defaultData = array_merge($newKeyData, $defaultData);
      $this->writeJsonFile($this->langPath(self::DEFAULT_LANG), $defaultData);

      Session::flash('success', __('A new keyword added successfully') . '.');
      return response()->json(['status' => 'success'], 200);
    } catch (\Exception $e) {
      return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
  }

  // add new keyword for admin panel
  public function addAdminKeyword(Request $request)
  {
    $validator = \Validator::make($request->all(), ['keyword' => 'required']);
    if ($validator->fails()) {
      return response()->json(['errors' => $validator->getMessageBag()->toArray()], 400);
    }

    try {
      $keyword = $request->keyword;
      $newKeyData = [$keyword => $keyword];

      // Add to all existing admin language files
      $languages = Language::all();
      foreach ($languages as $language) {
        $langCode = strtolower($language->code);
        $filePath = $this->langPath(self::ADMIN_PREFIX . $langCode . '.json');
        $data = $this->readJsonFile($filePath);
        $data = array_merge($newKeyData, $data);
        $this->writeJsonFile($filePath, $data);
      }

      // Add to admin_default.json
      $defaultData = $this->readJsonFile($this->langPath(self::ADMIN_DEFAULT_LANG));
      $defaultData = array_merge($newKeyData, $defaultData);
      $this->writeJsonFile($this->langPath(self::ADMIN_DEFAULT_LANG), $defaultData);

      Session::flash('success', __('A new keyword added successfully for admin panel') . '.');
      return response()->json(['status' => 'success'], 200);
    } catch (\Exception $e) {
      return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
  }

  /**
   * Display all the keywords of specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function editKeyword($id)
  {
    $language = Language::query()->findOrFail($id);

    // get all the keywords of the selected language
    $jsonData = file_get_contents(resource_path('lang/') . $language->code . '.json');

    // convert json encoded string into a php associative array
    $keywords = json_decode($jsonData);

    return view('backend.language.edit-keyword', compact('language', 'keywords'));
  }

  /**
   * Update the keywords of specified resource in respective json file.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */

  // Update frontend keywords
  public function updateKeyword(Request $request, $id)
  {
    try {
      $arrData = $request['keyValues'];

      // Validate all keys have values
      foreach ($arrData as $key => $value) {
        if (empty($value)) {
          return redirect()->back()->with('warning', __('Value is required for') . ' ' . $key . ' ' . __('key') . '.');
        }
      }

      $language = Language::findOrFail($id);
      $langCode = strtolower($language->code);

      // Update JSON file
      $this->writeJsonFile($this->langPath($langCode . '.json'), $arrData);

      // Update validation attributes
      $validationFilePath = $this->langPath($langCode . '/validation.php');
      $this->updateValidationAttribute(Language::frontAttribute(), json_encode($arrData), $validationFilePath);

      return redirect()->back()->with('success', $language->name . ' ' . __("language's keywords updated successfully") . '.');
    } catch (\Exception $e) {
      \Log::error('Error updating keywords', ['error' => $e->getMessage()]);
      return back()->withErrors('Failed to update keywords');
    }
  }

  public function editAdminKeyword($id)
  {
    $language = Language::query()->findOrFail($id);

    // get all the keywords of the selected language
    $jsonData = file_get_contents(resource_path('lang/') . 'admin_' . $language->code . '.json');

    // convert json encoded string into a php associative array
    $keywords = json_decode($jsonData);

    return view('backend.language.edit-admin-keyword', compact('language', 'keywords'));
  }

  // Update admin keywords
  public function updateAdminKeyword(Request $request, $id)
  {
    try {
      $arrData = $request['keyValues'];

      // Validate all keys have values
      foreach ($arrData as $key => $value) {
        if (empty($value)) {
          return redirect()->back()->with('warning', __('Value is required for') . ' ' . $key . ' ' . __('key') . '.');
        }
      }

      $language = Language::findOrFail($id);
      $langCode = strtolower($language->code);

      // Read existing data and merge
      $adminFilePath = $this->langPath(self::ADMIN_PREFIX . $langCode . '.json');
      $existingData = $this->readJsonFile($adminFilePath);
      $mergedData = array_merge($existingData, $arrData);

      // Update JSON file
      $this->writeJsonFile($adminFilePath, $mergedData);

      // Update validation attributes
      $validationFilePath = $this->langPath(self::ADMIN_PREFIX . $langCode . '/validation.php');
      $this->updateValidationAttribute(Language::dashboardAttribute(), json_encode($arrData), $validationFilePath);

      return redirect()->back()->with('success', $language->name . ' ' . __("language's keywords updated successfully") . '!');
    } catch (\Exception $e) {
      \Log::error('Error updating admin keywords', ['error' => $e->getMessage()]);
      return back()->withErrors('Failed to update keywords');
    }
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    $language = Language::query()->find($id);

    if ($language->is_default == 1) {
      return redirect()->back()->with('warning', 'Default language cannot be delete.');
    } else {
      /**
       * delete website-menu info
       */
      $websiteMenuInfo = $language->menuInfo()->first();

      if (!empty($websiteMenuInfo)) {
        $websiteMenuInfo->delete();
      }

      $carCategories = $language->carCategory()->get();
      foreach ($carCategories as $carCategory) {
        @unlink(public_path('assets/admin/img/car-category/') . $carCategory->image);
        $car_contents = $carCategory->car_contents()->get();
        foreach ($car_contents as $car_content) {
          $car_content->delete();
        }
        $carCategory->delete();
      }

      $carConditions = $language->carCondition()->get();
      foreach ($carConditions as $carCondition) {
        $carCondition->delete();
      }

      $carBrands = $language->carBrand()->get();
      foreach ($carBrands as $carBrand) {
        $carBrand->delete();
      }

      $carModels = $language->carModel()->get();
      foreach ($carModels as $carModel) {
        $carModel->delete();
      }


      $fuelTypes = $language->fuelType()->get();
      foreach ($fuelTypes as $fuelType) {
        $fuelType->delete();
      }
      $TransmissionTypes = $language->TransmissionType()->get();
      foreach ($TransmissionTypes as $TransmissionType) {
        $TransmissionType->delete();
      }
      $CarSpecificationContents = $language->CarSpecificationContents()->get();
      foreach ($CarSpecificationContents as $CarSpecificationContent) {
        $CarSpecificationContent->delete();
      }

      $carContents = $language->carContents()->get();
      foreach ($carContents as $carContent) {
        $carContent->delete();
      }

      /**
       * delete product-categories info
       */
      $productCategories = $language->productCategory()->get();

      if (count($productCategories) > 0) {
        foreach ($productCategories as $productCategory) {
          $productCategory->delete();
        }
      }

      $shippingCharges = $language->shippingCharge()->get();
      foreach ($shippingCharges as $shippingCharge) {
        $shippingCharge->delete();
      }

      /**
       * delete product infos
       */
      $productInfos = $language->productContent()->get();

      if (count($productInfos) > 0) {
        foreach ($productInfos as $productData) {
          $productInfo = $productData;
          $productData->delete();

          // delete the product if, this product does not contain any other product-contents in any other language
          $otherProductInfos = ProductContent::query()->where('language_id', '<>', $language->id)
            ->where('product_id', '=', $productInfo->product_id)
            ->get();

          if (count($otherProductInfos) == 0) {
            $product = Product::query()->find($productInfo->product_id);

            // delete purchase item records of this product
            $purchaseInfos = $product->purchase()->get();

            if (count($purchaseInfos) > 0) {
              foreach ($purchaseInfos as $purchaseData) {
                $purchaseInfo = $purchaseData;
                $purchaseData->delete();

                // delete the order if, this order does not contain any other items
                $otherPurchaseItems = ProductPurchaseItem::query()->where('product_id', '<>', $product->id)
                  ->where('product_order_id', '=', $purchaseInfo->product_order_id)
                  ->get();

                if (count($otherPurchaseItems) == 0) {
                  $order = ProductOrder::query()->find($purchaseInfo->product_order_id);

                  // delete order receipt
                  @unlink(public_path('assets/file/attachments/product/') . $order->receipt);

                  // delete order invoice
                  @unlink(public_path('assets/file/invoices/product/') . $order->invoice);

                  $order->delete();
                }
              }
            }

            // delete all the reviews of this product
            $reviews = $product->review()->get();

            if (count($reviews) > 0) {
              foreach ($reviews as $review) {
                $review->delete();
              }
            }

            // delete product featured image
            $featImg = $product->featured_image;
            @unlink(public_path('assets/img/products/featured-images/') . $featImg);

            // delete product slider images
            $sldImgs = json_decode($product->slider_images);

            foreach ($sldImgs as $sldImg) {
              @unlink(public_path('assets/img/products/slider-images/') . $sldImg);
            }

            // delete product zip file
            $zipFile = $product->file;
            @unlink(public_path('assets/file/products/') . $zipFile);

            $product->delete();
          }
        }
      }

      $vendorInfos = $language->vendorInfo()->get();
      foreach ($vendorInfos as $vendorInfo) {
        $vendorInfo->delete();
      }

      /**
       * delete hero-slider infos
       */
      $sliders = $language->sliderInfo()->get();

      if (count($sliders) > 0) {
        foreach ($sliders as $slider) {
          $bgImg = $slider->background_image;

          @unlink(public_path('assets/img/hero/sliders/') . $bgImg);
          $slider->delete();
        }
      }

      /**
       * delete call-to-action-section info
       */
      $callToActionSecInfo = $language->callToActionSection()->first();

      if (!empty($callToActionSecInfo)) {
        $callToActionSecInfo->delete();
      }

      $banners = $language->banner()->get();
      foreach ($banners as $banner) {
        @unlink(public_path('assets/img/banners/') . $banner->image);
        $banner->delete();
      }

      $workProcessSections = $language->workProcessSection()->get();
      foreach ($workProcessSections as $workProcessSection) {
        $workProcessSection->delete();
      }
      $workProcess = $language->workProcess()->get();
      foreach ($workProcess as $workProces) {
        $workProces->delete();
      }

      $counterInfos = $language->counterInfo()->get();
      foreach ($counterInfos as $counterInfo) {
        $counterInfo->delete();
      }

      $counterSections = $language->counterSection()->get();
      foreach ($counterSections as $counterSection) {
        $counterSection->delete();
      }
      $testimonialSections = $language->testimonialSection()->get();
      foreach ($testimonialSections as $testimonialSection) {
        $testimonialSection->delete();
      }

      /**
       * delete testimonial infos
       */
      $testimonials = $language->testimonial()->get();

      if (count($testimonials) > 0) {
        foreach ($testimonials as $testimonial) {
          $clientImg = $testimonial->image;

          @unlink(public_path('assets/img/clients/') . $clientImg);
          $testimonial->delete();
        }
      }
      /**
       * delete blog-section info
       */
      $blogSecInfo = $language->blogSection()->first();

      if (!empty($blogSecInfo)) {
        $blogSecInfo->delete();
      }
      /**
       * delete footer-content info
       */
      $footerContentInfo = $language->footerContent()->first();

      if (!empty($footerContentInfo)) {
        $footerContentInfo->delete();
      }
      /**
       * delete footer-quick-links
       */
      $quickLinks = $language->footerQuickLink()->get();

      if (count($quickLinks) > 0) {
        foreach ($quickLinks as $quickLink) {
          $quickLink->delete();
        }
      }
      /**
       * delete custom-page infos
       */
      $customPageInfos = $language->customPageInfo()->get();

      if (count($customPageInfos) > 0) {
        foreach ($customPageInfos as $customPageData) {
          $customPageInfo = $customPageData;
          $customPageData->delete();

          // delete the custom-page if, this page does not contain any other page-content in any other language
          $otherPageContents = PageContent::query()->where('language_id', '<>', $language->id)
            ->where('page_id', '=', $customPageInfo->page_id)
            ->get();

          if (count($otherPageContents) == 0) {
            $page = Page::query()->find($customPageInfo->page_id);
            $page->delete();
          }
        }
      }
      /**
       * delete blog-categories info
       */
      $blogCategories = $language->blogCategory()->get();

      if (count($blogCategories) > 0) {
        foreach ($blogCategories as $blogCategory) {
          $blogCategory->delete();
        }
      }
      /**
       * delete blog infos
       */
      $blogInfos = $language->blogInformation()->get();

      if (count($blogInfos) > 0) {
        foreach ($blogInfos as $blogData) {
          $blogInfo = $blogData;
          $blogData->delete();

          // delete the blog if, this blog does not contain any other blog-information in any other language
          $otherBlogInfos = BlogInformation::query()->where('language_id', '<>', $language->id)
            ->where('blog_id', '=', $blogInfo->blog_id)
            ->get();

          if (count($otherBlogInfos) == 0) {
            $blog = Blog::query()->find($blogInfo->blog_id);
            @unlink(public_path('assets/img/blogs/') . $blog->image);
            $blog->delete();
          }
        }
      }
      /**
       * delete faq infos
       */
      $faqs = $language->faq()->get();

      if (count($faqs) > 0) {
        foreach ($faqs as $faq) {
          $faq->delete();
        }
      }
      /**
       * delete popup infos
       */
      $popups = $language->announcementPopup()->get();

      if (count($popups) > 0) {
        foreach ($popups as $popup) {
          @unlink(public_path('assets/img/popups/') . $popup->image);
          $popup->delete();
        }
      }

      $pageNames = $language->pageName()->get();
      foreach ($pageNames as $pageName) {
        $pageName->delete();
      }
      $seoInfos = $language->seoInfo()->get();
      foreach ($seoInfos as $seoInfo) {
        $seoInfo->delete();
      }
      /**
       * delete cookie-alert info
       */
      $cookieAlertInfo = $language->cookieAlertInfo()->first();

      if (!empty($cookieAlertInfo)) {
        $cookieAlertInfo->delete();
      }
      /**
       * delete the language json file
       */
 
      $languageCode = strtolower($language->code);

      // Delete frontend language JSON file
      $frontendJsonPath = $this->langPath($languageCode . '.json');
      if (file_exists($frontendJsonPath)) {
        @unlink($frontendJsonPath);
      }

      // Delete admin language JSON file
      $adminJsonPath = $this->langPath(self::ADMIN_PREFIX . $languageCode . '.json');
      if (file_exists($adminJsonPath)) {
        @unlink($adminJsonPath);
      }
      // Delete language folder (contains validation.php, passwords.php, etc)
      $langFolderPath = $this->langPath($languageCode);
      if (is_dir($langFolderPath)) {
        $this->deleteDirectory($langFolderPath);
      }

      Session::forget('currentLocaleCode');

      // Get all vendors and clear their language sessions
      $vendors = \App\Models\Vendor::all(); 
      foreach ($vendors as $vendor) {
        $sessionKey = 'vendor_lang_' . $vendor->id;
        Session::forget($sessionKey);
      }

      /**
       * finally, delete the language info from db
       */
      $language->delete();

      return redirect()->back()->with('success', __('Language deleted successfully') . '!');
    }
  }

  /**
   * Check the specified language is RTL or not.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function checkRTL($id)
  {
    if (!is_null($id)) {
      $direction = Language::query()->where('id', '=', $id)
        ->pluck('direction')
        ->first();

      return response()->json(['successData' => $direction], 200);
    } else {
      return response()->json(['errorData' => 'Sorry, an error has occured!'], 400);
    }
  }
  public function checkRTL2($id)
  {
    if (!is_null($id)) {
      $direction = Language::query()->where('id', '=', $id)
        ->pluck('direction')
        ->first();

      $brands = Brand::where('language_id', $id)->get();

      return response()->json([['successData' => $direction], ['brands' => $brands]], 200);
    } else {
      return response()->json(['errorData' => 'Sorry, an error has occured!'], 400);
    }
  }
}
