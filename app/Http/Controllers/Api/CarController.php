<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\MiscellaneousController;
use App\Models\Admin;
use App\Models\BasicSettings\Basic;
use App\Models\BasicSettings\MailTemplate;
use App\Models\Car;
use App\Models\Car\Brand;
use App\Models\Car\CarCondition;
use App\Models\Car\CarContent;
use App\Models\Car\CarModel;
use App\Models\Car\CarSpecification;
use App\Models\Car\Category;
use App\Models\Car\FuelType;
use App\Models\Car\TransmissionType;
use App\Models\Language;
use App\Models\Location\City;
use App\Models\Location\Country;
use App\Models\Location\State;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Config;
use Exception;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class CarController extends Controller
{
  public function index(Request $request)
  {
    // $view = Basic::query()->pluck('car_view')->first();
    $misc = new MiscellaneousController();
    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    if ($request->filled('type')) {
      Session::put('car_view_type', $request->type);
    }
    // $view_type = Session::get('car_view_type');

    $category = $city = $state = $country = $title = $location = $address = $brands = $models = $fuel_type = $transmission = $condition = $min = $max = $year = $sort =  null;

    $carIds = [];
    if ($request->filled('title')) {
      $title = $request->title;
      $car_contents = CarContent::where('language_id', $language->id)
        ->where('title', 'like', '%' . $title . '%')
        ->get()
        ->pluck('car_id');
      foreach ($car_contents as $car_content) {
        if (!in_array($car_content, $carIds)) {
          array_push($carIds, $car_content);
        }
      }
    }

    $year_carIds = [];
    if ($request->filled('year')) {
      $year = $request->year;
      $contents = Car::where('year', $year)
        ->get()
        ->pluck('id');
      foreach ($contents as $content) {
        if (!in_array($content, $year_carIds)) {
          array_push($year_carIds, $content);
        }
      }
    }
    $city_carIds = [];
    if ($request->filled('city')) {
      $city = $request->city;
      $city_content = City::where([['language_id', $language->id], ['slug', $city]])->first();
      if (!empty($city_content)) {
        $city_id = $city_content->id;
        $contents = CarContent::where('language_id', $language->id)
          ->where('city_id', $city_id)
          ->get()
          ->pluck('car_id');
        foreach ($contents as $content) {
          if (!in_array($content, $city_carIds)) {
            array_push($city_carIds, $content);
          }
        }
      }
    }
    $state_carIds = [];
    if ($request->filled('state')) {
      $state = $request->state;
      $state_content = State::where([['language_id', $language->id], ['slug', $state]])->first();
      if (!empty($state_content)) {
        $state_id = $state_content->id;
        $contents = CarContent::where('language_id', $language->id)
          ->where('state_id', $state_id)
          ->get()
          ->pluck('car_id');
        foreach ($contents as $content) {
          if (!in_array($content, $state_carIds)) {
            array_push($state_carIds, $content);
          }
        }
      }
    }

    $country_carIds = [];
    if ($request->filled('country')) {
      $country = $request->country;
      $country_content = Country::where([['language_id', $language->id], ['slug', $country]])->first();
      if (!empty($country_content)) {
        $country_id = $country_content->id;
        $contents = CarContent::where('language_id', $language->id)
          ->where('country_id', $country_id)
          ->get()
          ->pluck('car_id');
        foreach ($contents as $content) {
          if (!in_array($content, $country_carIds)) {
            array_push($country_carIds, $content);
          }
        }
      }
    }

    Log::info($request->all());


    $category_carIds = [];
    if ($request->filled('category')) {
      $category = $request->category;
      $category_content = Category::where([['language_id', $language->id], ['slug', $category]])->first();
      if (!empty($category_content)) {
        $category_id = $category_content->id;
        $contents = CarContent::where('language_id', $language->id)
          ->where('category_id', $category_id)
          ->get()
          ->pluck('car_id');
        foreach ($contents as $content) {
          if (!in_array($content, $category_carIds)) {
            array_push($category_carIds, $content);
          }
        }
      }
    }



    if ($request->filled('sort')) {
      $sort = $request['sort'];
      if ($request['sort'] == 'new') {
        $order_by_column = 'cars.id';
        $order = 'desc';
      } elseif ($request['sort'] == 'old') {
        $order_by_column = 'cars.id';
        $order = 'asc';
      } elseif ($request['sort'] == 'high-to-low') {
        $order_by_column = 'cars.price';
        $order = 'desc';
      } elseif ($request['sort'] == 'low-to-high') {
        $order_by_column = 'cars.price';
        $order = 'asc';
      } else {
        $order_by_column = 'cars.id';
        $order = 'desc';
      }
    } else {
      $order_by_column = 'cars.id';
      $order = 'desc';
    }

    $today   = Carbon::today();
    $perPage =  9;

    $query = Car::join('car_contents', 'cars.id', '=', 'car_contents.car_id')
      ->join('memberships', 'cars.vendor_id', '=', 'memberships.vendor_id')
      ->join('vendors', 'cars.vendor_id', '=', 'vendors.id')
      ->join('categories', 'car_contents.category_id', '=', 'categories.id')
      ->leftJoin('cities', 'cities.id', '=', 'car_contents.city_id')
      ->leftJoin('states', 'states.id', '=', 'car_contents.state_id')
      ->leftJoin('countries', 'countries.id', '=', 'car_contents.country_id')

      ->leftJoin('car_conditions', 'car_conditions.id', '=', 'car_contents.car_condition_id')
      ->leftJoin('brands', 'brands.id', '=', 'car_contents.brand_id')
      ->leftJoin('car_models', 'car_models.id', '=', 'car_contents.car_model_id')
      ->leftJoin('fuel_types', 'fuel_types.id', '=', 'car_contents.fuel_type_id')
      ->leftJoin('transmission_types', 'transmission_types.id', '=', 'car_contents.transmission_type_id')

      ->where([
        ['memberships.status', 1],
        ['memberships.start_date', '<=', $today],
        ['memberships.expire_date', '>=', $today],
        ['vendors.status', 1],
        ['cars.status', 1],
        ['car_contents.language_id', $language->id],
      ])

      /* ---------------- Filters ---------------- */
      ->when($title, fn($q) => $q->whereIn('cars.id', $carIds))
      ->when($category, fn($q) => $q->whereIn('car_contents.car_id', $category_carIds))
      ->when($year, fn($q) => $q->whereIn('cars.id', $year_carIds))
      ->when($city, fn($q) => $q->whereIn('cars.id', $city_carIds))
      ->when($state, fn($q) => $q->whereIn('cars.id', $state_carIds))
      ->when($country, fn($q) => $q->whereIn('cars.id', $country_carIds));


    /* -------- Normal sorting (no distance) -------- */
    $query->select(
      'cars.*',
      'car_contents.title',
      'car_contents.brand_id',
      'car_contents.car_model_id',
      'car_contents.slug',
      'car_contents.category_id',
      'car_contents.description',
      'cities.name as city_name',
      'states.name as state_name',
      'countries.name as country_name',
      'categories.name as category_name',
      'car_conditions.name as car_condition',
      'brands.name as brand_name',
      'car_models.name as car_model',
      'fuel_types.name as fuel_type',
      'transmission_types.name as transmission_type'
    )
      ->orderBy($order_by_column, $order);


    $car_contents = $query
      // ->groupBy('cars.id')
      ->paginate($perPage)
      ->through(function ($item) use ($language) {
        return HelperController::formatCarForApi($item, $language->id);
      });

    $information['car_contents'] = $car_contents;
    $information['years'] = DB::table('cars')->select('year')->distinct()->orderByDesc('year')->pluck('year');
    $min = Car::min('price');
    $max = Car::max('price');

    $information['min'] = intval($min);
    $information['max'] = intval($max);

    $information['categories'] = Category::where('language_id', $language->id)->where('status', 1)->orderBy('serial_number', 'asc')->get();

    $information['brands'] = Brand::where('language_id', $language->id)->where('status', 1)->orderBy('serial_number', 'asc')->get();

    $information['car_conditions'] = CarCondition::where('language_id', $language->id)->where('status', 1)->orderBy('serial_number', 'asc')->get();

    $information['fuel_types'] = FuelType::where('language_id', $language->id)->where('status', 1)->orderBy('serial_number', 'asc')->get();

    $information['transmission_types'] = TransmissionType::where('language_id', $language->id)->where('status', 1)->orderBy('serial_number', 'asc')->get();

    $information['bgImg'] = asset('assets/img/' . $misc->getBreadcrumb()->breadcrumb);
    $information['pageHeading'] = $misc->getPageHeading($language);

    $information['countries'] = Country::where('language_id', $language->id)
      ->orderBy('name', 'asc')
      ->get();

    $countryContent = null;
    $stateContent = null;
    $countryId = null;

    // Get selected country (if any)
    if ($country) {
      $countryContent = Country::where([
        ['language_id', $language->id],
        ['slug', $country]
      ])->first();

      $countryId = optional($countryContent)->id;

      $information['states'] = State::where('language_id', $language->id)
        ->when($countryId, fn($q) => $q->where('country_id', $countryId))
        ->orderBy('name', 'asc')
        ->get();
    } else {
      $information['states'] = State::where('language_id', $language->id)
        ->orderBy('name', 'asc')
        ->get();
    }

    if ($state) {
      $stateContent = State::where([
        ['language_id', $language->id],
        ['slug', $state]
      ])->first();

      $information['cities'] = City::where('language_id', $language->id)
        ->when(optional($stateContent)->id, fn($q, $stateId) => $q->where('state_id', $stateId))
        ->orderBy('name', 'asc')
        ->get();
    } else {
      $information['cities'] = City::where('language_id', $language->id)
        ->when($countryId, fn($q) => $q->where('country_id', $countryId))
        ->orderBy('name', 'asc')
        ->get();
    }

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }
  public function searchCar(Request $request)
  {
    $misc = new MiscellaneousController();
    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    $category = $city = $state = $country = $title = $location = $address = $brands = $models = $fuel_type = $transmission = $condition = $min = $max = $year = $sort =  null;

    $carIds = [];
    if ($request->filled('title')) {
      $title = $request->title;
      $car_contents = CarContent::where('language_id', $language->id)
        ->where('title', 'like', '%' . $title . '%')
        ->get()
        ->pluck('car_id');
      foreach ($car_contents as $car_content) {
        if (!in_array($car_content, $carIds)) {
          array_push($carIds, $car_content);
        }
      }
    }

    $year_carIds = [];
    if ($request->filled('year')) {
      $year = $request->year;
      $contents = Car::where('year', $year)
        ->get()
        ->pluck('id');
      foreach ($contents as $content) {
        if (!in_array($content, $year_carIds)) {
          array_push($year_carIds, $content);
        }
      }
    }
    $city_carIds = [];
    if ($request->filled('city')) {
      $city = $request->city;
      $city_content = City::where([['language_id', $language->id], ['slug', $city]])->first();
      if (!empty($city_content)) {
        $city_id = $city_content->id;
        $contents = CarContent::where('language_id', $language->id)
          ->where('city_id', $city_id)
          ->get()
          ->pluck('car_id');
        foreach ($contents as $content) {
          if (!in_array($content, $city_carIds)) {
            array_push($city_carIds, $content);
          }
        }
      }
    }
    $state_carIds = [];
    if ($request->filled('state')) {
      $state = $request->state;
      $state_content = State::where([['language_id', $language->id], ['slug', $state]])->first();
      if (!empty($state_content)) {
        $state_id = $state_content->id;
        $contents = CarContent::where('language_id', $language->id)
          ->where('state_id', $state_id)
          ->get()
          ->pluck('car_id');
        foreach ($contents as $content) {
          if (!in_array($content, $state_carIds)) {
            array_push($state_carIds, $content);
          }
        }
      }
    }

    $country_carIds = [];
    if ($request->filled('country')) {
      $country = $request->country;
      $country_content = Country::where([['language_id', $language->id], ['slug', $country]])->first();
      if (!empty($country_content)) {
        $country_id = $country_content->id;
        $contents = CarContent::where('language_id', $language->id)
          ->where('country_id', $country_id)
          ->get()
          ->pluck('car_id');
        foreach ($contents as $content) {
          if (!in_array($content, $country_carIds)) {
            array_push($country_carIds, $content);
          }
        }
      }
    }


    $category_carIds = [];
    if ($request->filled('category')) {
      $category = $request->category;
      $category_content = Category::where([['language_id', $language->id], ['slug', $category]])->first();
      if (!empty($category_content)) {
        $category_id = $category_content->id;
        $contents = CarContent::where('language_id', $language->id)
          ->where('category_id', $category_id)
          ->get()
          ->pluck('car_id');
        foreach ($contents as $content) {
          if (!in_array($content, $category_carIds)) {
            array_push($category_carIds, $content);
          }
        }
      }
    }

    //search by location
    $locationIds = [];
    $addressIds = [];
    $lat_long = [];
    $bs = Basic::select('google_map_api_key_status', 'radius', 'google_map_api_key')->first();
    $radius = $bs->google_map_api_key_status == 1 ? $bs->radius : 5000;


    if ($request->filled('location')) {
      $location = $request->location;
      if ($bs->google_map_api_key_status == 1) {
        $lat_long = $this->getCoordinates($location, $bs->google_map_api_key);
        if (count($lat_long) == 0) {
          $serviceIds = CarContent::where('language_id', $language->id)
            ->where('address', 'like', '%' . $location . '%')
            ->distinct()
            ->pluck('car_id')
            ->toArray();
          $serviceLog = Car::whereIn('id', $serviceIds)->select('latitude', 'longitude')->first();
          $locationIds = $serviceLog;
        }
      } else {
        $address = $request->location;

        $contents = CarContent::where('language_id', $language->id)
          ->where('address', 'like', '%' . $address . '%')
          ->get()
          ->pluck('car_id');
        foreach ($contents as $content) {
          if (!in_array($content, $addressIds)) {
            array_push($addressIds, $content);
          }
        }
      }
    }



    $brandIds = [];
    if ($request->filled('brands')) {
      $brands = $request->brands;
      $b_ids = [];

      if (is_array($brands)) {
        foreach ($brands as $brand) {
          if (!is_null($brand)) {
            $brand_car_contents = Brand::where([['language_id', $language->id], ['slug', $brand]])->first();
            if (!empty($brand_car_contents)) {
              if (!in_array($brand_car_contents->id, $b_ids)) {
                array_push($b_ids, $brand_car_contents->id);
              }
            }
          }
        }
      } else {
        $brand_car_contents = Brand::where([['language_id', $language->id], ['slug', $brands]])->first();
        if (!in_array($brand_car_contents->id, $b_ids)) {
          array_push($b_ids, $brand_car_contents->id);
        }
      }

      $contents = CarContent::where('language_id', $language->id)
        ->whereIn('brand_id', $b_ids)
        ->get()
        ->pluck('car_id');
      foreach ($contents as $content) {
        if (!in_array($content, $brandIds)) {
          array_push($brandIds, $content);
        }
      }
    }

    $modelIds = [];
    if ($request->filled('models')) {
      $models = $request->models;
      $m_ids = [];
      if (is_array($models)) {
        foreach ($models as $model) {
          $model_car_contents = CarModel::where([['language_id', $language->id], ['slug', $model]])->where('status', 1)->first();
          if (!in_array($model_car_contents->id, $m_ids)) {
            array_push($m_ids, $model_car_contents->id);
          }
        }
      } else {
        $model_car_contents = CarModel::where([['language_id', $language->id], ['slug', $models]])->where('status', 1)->first();
        if (!in_array($model_car_contents->id, $m_ids)) {
          array_push($m_ids, $model_car_contents->id);
        }
      }

      $contents = CarContent::where('language_id', $language->id)
        ->whereIn('car_model_id', $m_ids)
        ->get();
      foreach ($contents as $content) {
        if (!in_array($content->car_id, $modelIds)) {
          array_push($modelIds, $content->car_id);
        }
      }
    }

    $fuel_type_id = [];
    if ($request->filled('fuel_type')) {
      $fuel_type = $request->fuel_type;
      $fuel_type_content = FuelType::where([['language_id', $language->id], ['slug', $fuel_type]])->first();
      if (!empty($fuel_type_content)) {
        $f_id = $fuel_type_content->id;
        $contents = CarContent::where('language_id', $language->id)
          ->where('fuel_type_id', $f_id)
          ->get()
          ->pluck('car_id');
        foreach ($contents as $content) {
          if (!in_array($content, $fuel_type_id)) {
            array_push($fuel_type_id, $content);
          }
        }
      }
    }

    $transmission_type_id = [];
    if ($request->filled('transmission')) {
      $transmission = $request->transmission;
      $transmission_content = TransmissionType::where([['language_id', $language->id], ['slug', $transmission]])->first();
      if (!empty($transmission_content)) {
        $t_id = $transmission_content->id;
        $contents = CarContent::where('language_id', $language->id)
          ->where('transmission_type_id', $t_id)
          ->get()
          ->pluck('car_id');
        foreach ($contents as $content) {
          if (!in_array($content, $transmission_type_id)) {
            array_push($transmission_type_id, $content);
          }
        }
      }
    }
    $condition_id = [];
    if ($request->filled('transmission')) {
      $condition = $request->condition;
      $condition_content = CarCondition::where([['language_id', $language->id], ['slug', $condition]])->first();
      if (!empty($condition_content)) {
        $c_id = $condition_content->id;
        $contents = CarContent::where('language_id', $language->id)
          ->where('car_condition_id', $c_id)
          ->get()
          ->pluck('car_id');
        foreach ($contents as $content) {
          if (!in_array($content, $condition_id)) {
            array_push($condition_id, $content);
          }
        }
      }
    }

    $priceIds = [];
    if ($request->filled('min') && $request->filled('max')) {
      $min = intval($request->min);
      $max = intval(($request->max));
      $price_cars = DB::table('cars')
        ->select('*')
        ->where('price', '>=', $min)
        ->where('price', '<=', DB::raw($max))
        ->get();
      foreach ($price_cars as $car) {
        if (!in_array($car->id, $priceIds)) {
          array_push($priceIds, $car->id);
        }
      }
    }

    if ($request->filled('sort')) {
      $sort = $request['sort'];
      if ($request['sort'] == 'new') {
        $order_by_column = 'cars.id';
        $order = 'desc';
      } elseif ($request['sort'] == 'old') {
        $order_by_column = 'cars.id';
        $order = 'asc';
      } elseif ($request['sort'] == 'high-to-low') {
        $order_by_column = 'cars.price';
        $order = 'desc';
      } elseif ($request['sort'] == 'low-to-high') {
        $order_by_column = 'cars.price';
        $order = 'asc';
      } else {
        $order_by_column = 'cars.id';
        $order = 'desc';
      }
    } else {
      $order_by_column = 'cars.id';
      $order = 'desc';
    }

    $today   = Carbon::today();
    $perPage =  9;

    $query = Car::join('car_contents', 'cars.id', '=', 'car_contents.car_id')
      ->join('memberships', 'cars.vendor_id', '=', 'memberships.vendor_id')
      ->join('vendors', 'cars.vendor_id', '=', 'vendors.id')
      ->join('categories', 'car_contents.category_id', '=', 'categories.id')
      ->leftJoin('cities', 'cities.id', '=', 'car_contents.city_id')
      ->leftJoin('states', 'states.id', '=', 'car_contents.state_id')
      ->leftJoin('countries', 'countries.id', '=', 'car_contents.country_id')

      ->leftJoin('car_conditions', 'car_conditions.id', '=', 'car_contents.car_condition_id')
      ->leftJoin('brands', 'brands.id', '=', 'car_contents.brand_id')
      ->leftJoin('car_models', 'car_models.id', '=', 'car_contents.car_model_id')
      ->leftJoin('fuel_types', 'fuel_types.id', '=', 'car_contents.fuel_type_id')
      ->leftJoin('transmission_types', 'transmission_types.id', '=', 'car_contents.transmission_type_id')

      ->where([
        ['memberships.status', 1],
        ['memberships.start_date', '<=', $today],
        ['memberships.expire_date', '>=', $today],
        ['vendors.status', 1],
        ['cars.status', 1],
        ['car_contents.language_id', $language->id],
      ])

      /* ---------------- Filters ---------------- */
      ->when($title, fn($q) => $q->whereIn('cars.id', $carIds))
      ->when($category, fn($q) => $q->whereIn('car_contents.car_id', $category_carIds))
      ->when($year, fn($q) => $q->whereIn('cars.id', $year_carIds))
      ->when($city, fn($q) => $q->whereIn('car_contents.car_id', $city_carIds))
      ->when($state, fn($q) => $q->whereIn('car_contents.car_id', $state_carIds))
      ->when($country, fn($q) => $q->whereIn('car_contents.car_id', $country_carIds))
      ->when($address, fn($q) => $q->whereIn('car_contents.car_id', $addressIds))
      ->when($brands, fn($q) => $q->whereIn('cars.id', $brandIds))
      ->when($models, fn($q) => $q->whereIn('cars.id', $modelIds))
      ->when($fuel_type, fn($q) => $q->whereIn('cars.id', $fuel_type_id))
      ->when($transmission, fn($q) => $q->whereIn('cars.id', $transmission_type_id))
      ->when($condition, fn($q) => $q->whereIn('cars.id', $condition_id))
      ->when(!is_null($min) && !is_null($max), fn($q) => $q->whereIn('cars.id', $priceIds));

    if (
      $bs->google_map_api_key_status == 1 &&
      isset($lat_long['lat'], $lat_long['lng'])
    ) {
      $lat    = $lat_long['lat'];
      $lng    = $lat_long['lng'];
      $radius = $bs->radius / 1000;

      $query->selectRaw("
        'cars.*,
      car_contents.title,
      car_contents.brand_id,
      car_contents.car_model_id,
      car_contents.slug,
      car_contents.category_id,
      car_contents.description,
      cities.name as city_name,
      states.name as state_name,
      countries.name as country_name,
      categories.name as category_name,
      car_conditions.name as car_condition,
      brands.name as brand_name,
      car_models.name as car_model,
      fuel_types.name as fuel_type,
      transmission_types.name as transmission_type
        (
            6371 * acos(
                cos(radians(?)) *
                cos(radians(cars.latitude)) *
                cos(radians(cars.longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(cars.latitude))
            )
        ) AS distance
    ", [$lat, $lng, $lat])
        ->having('distance', '<=', $radius);

      $sort === 'farthest'
        ? $query->orderByDesc('distance')
        : $query->orderBy('distance'); // nearest default

    } else {

      /* -------- Normal sorting (no distance) -------- */
      $query->select(
        'cars.*',
        'car_contents.title',
        'car_contents.brand_id',
        'car_contents.car_model_id',
        'car_contents.slug',
        'car_contents.category_id',
        'car_contents.description',
        'cities.name as city_name',
        'states.name as state_name',
        'countries.name as country_name',
        'categories.name as category_name',
        'car_conditions.name as car_condition',
        'brands.name as brand_name',
        'car_models.name as car_model',
        'fuel_types.name as fuel_type',
        'transmission_types.name as transmission_type'
      )
        ->orderBy($order_by_column, $order);
    }

    $car_contents = $query
      ->distinct('cars.id')
      ->paginate($perPage)
      ->through(function ($item) use ($language) {
        return HelperController::formatCarForApi($item, $language->id);
      });

    $information['car_contents'] = $car_contents;



    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  public function details(Request $request, $id)
  {
    $misc = new MiscellaneousController();

    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();


    $car = Car::join('car_contents', 'car_contents.car_id', 'cars.id')
      ->join('memberships', 'cars.vendor_id', '=', 'memberships.vendor_id')
      ->join('vendors', 'cars.vendor_id', '=', 'vendors.id')

      ->join('categories', 'car_contents.category_id', '=', 'categories.id')
      ->leftJoin('cities', 'cities.id', '=', 'car_contents.city_id')
      ->leftJoin('states', 'states.id', '=', 'car_contents.state_id')
      ->leftJoin('countries', 'countries.id', '=', 'car_contents.country_id')

      ->leftJoin('car_conditions', 'car_conditions.id', '=', 'car_contents.car_condition_id')
      ->leftJoin('brands', 'brands.id', '=', 'car_contents.brand_id')
      ->leftJoin('car_models', 'car_models.id', '=', 'car_contents.car_model_id')
      ->leftJoin('fuel_types', 'fuel_types.id', '=', 'car_contents.fuel_type_id')
      ->leftJoin('transmission_types', 'transmission_types.id', '=', 'car_contents.transmission_type_id')
      ->where([
        ['memberships.status', '=', 1],
        ['memberships.start_date', '<=', Carbon::now()->format('Y-m-d')],
        ['memberships.expire_date', '>=', Carbon::now()->format('Y-m-d')]
      ])
      ->where([['vendors.status', 1], ['cars.status', 1]])
      ->where('car_contents.language_id', $language->id)
      ->orderBy('cars.id', 'desc')
      ->limit(8)
      ->select(
        'cars.*',
        'car_contents.title',
        'car_contents.brand_id',
        'car_contents.address as full_address',
        'car_contents.slug',
        'car_contents.category_id',
        'car_contents.description',
        'cities.name as city_name',
        'states.name as state_name',
        'countries.name as country_name',
        'categories.name as category_name',
        'car_conditions.name as car_condition',
        'brands.name as brand_name',
        'car_models.name as car_model',
        'fuel_types.name as fuel_type',
        'transmission_types.name as transmission_type'
      )
      ->where('cars.id', $id)->firstOrFail();

    if ($car->vendor_id != 0) {
      $vendor = Vendor::where('id', $car->vendor_id)->first();
      $vendor->photo = HelperController::getImagePath('assets/admin/img/vendor-photo/', $vendor->photo);
    } else {
      $vendor = Admin::first();
      $vendor->photo = HelperController::getImagePath('assets/img/admins/', $vendor->image);
      $vendor->image = null;
    }

    $information['vendor'] = $vendor;

    $car->feature_image = HelperController::getImagePath('assets/admin/img/car/', $car->feature_image);
    $car->Apigalleries->transform(function ($gallery) {
      $gallery->image = HelperController::getImagePath(
        'assets/admin/img/car-gallery/',
        $gallery->image
      );
      return $gallery;
    });

    $car->address = implode(',', array_filter([
      $car->city_name ?? null,
      $car->state_name ?? null,
      $car->country_name ?? null,
    ]));

    $information['car'] = $car;

    $information['bgImg'] = asset('assets/img/' . @$misc->getBreadcrumb()->breadcrumb);

    $car_content = CarContent::where('language_id', $language->id)->where('car_id', $id)->first();
    if (is_null($car_content)) {
      Session::flash('message', 'No car information found for ' . $language->name . ' language');
      Session::flash('alert-type', 'warning');
      return redirect()->route('index');
    }

    $category_id = $car_content->category_id;
    $information['language'] = $language;

    $information['specifications'] = CarSpecification::where('car_id', $id)->get();

    $information['related_cars'] = Car::join('car_contents', 'car_contents.car_id', 'cars.id')
      ->join('memberships', 'cars.vendor_id', '=', 'memberships.vendor_id')
      ->join('vendors', 'cars.vendor_id', '=', 'vendors.id')

      ->join('categories', 'car_contents.category_id', '=', 'categories.id')
      ->leftJoin('cities', 'cities.id', '=', 'car_contents.city_id')
      ->leftJoin('states', 'states.id', '=', 'car_contents.state_id')
      ->leftJoin('countries', 'countries.id', '=', 'car_contents.country_id')

      ->leftJoin('car_conditions', 'car_conditions.id', '=', 'car_contents.car_condition_id')
      ->leftJoin('brands', 'brands.id', '=', 'car_contents.brand_id')
      ->leftJoin('car_models', 'car_models.id', '=', 'car_contents.car_model_id')
      ->leftJoin('fuel_types', 'fuel_types.id', '=', 'car_contents.fuel_type_id')
      ->leftJoin('transmission_types', 'transmission_types.id', '=', 'car_contents.transmission_type_id')
      ->where([
        ['memberships.status', '=', 1],
        ['memberships.start_date', '<=', Carbon::now()->format('Y-m-d')],
        ['memberships.expire_date', '>=', Carbon::now()->format('Y-m-d')]
      ])
      ->where([['vendors.status', 1], ['cars.status', 1]])
      ->where('car_contents.language_id', $language->id)
      ->where('car_contents.category_id', $category_id)
      ->orderBy('cars.id', 'desc')
      ->limit(8)
      ->select(
        'cars.*',
        'car_contents.title',
        'car_contents.brand_id',
        'car_contents.car_model_id',
        'car_contents.slug',
        'car_contents.category_id',
        'car_contents.description',
        'cities.name as city_name',
        'states.name as state_name',
        'countries.name as country_name',
        'categories.name as category_name',
        'car_conditions.name as car_condition',
        'brands.name as brand_name',
        'car_models.name as car_model',
        'fuel_types.name as fuel_type',
        'transmission_types.name as transmission_type'
      )
      ->get()
      ->map(function ($list) use ($language) {
        return HelperController::formatCarForApi($list, $language->id);
      });


    $information['info'] = Basic::select('google_recaptcha_status')->first();

    return response()->json([
      'success' => true,
      'data' => $information
    ]);
  }

  //contact
  public function inQuiryMessage(Request $request)
  {
    $mail_template = MailTemplate::where('mail_type', 'inquiry_about_car')->first();

    $rules = [
      'name' => 'required',
      'car_id' => 'required',
      'email' => 'required|email:rfc,dns',
      'phone' => 'required',
      'message' => 'required'
    ];

    $info = Basic::select('google_recaptcha_status')->first();
    if ($info->google_recaptcha_status == 1) {
      $rules['g-recaptcha-response'] = 'required|captcha';
    }

    $messageArray = [];

    if ($info->google_recaptcha_status == 1) {
      $messageArray['g-recaptcha-response.required'] = __('Please verify that you are not a robot') . '.';
      $messageArray['g-recaptcha-response.captcha'] = __('Captcha error! try again later or contact site admin') . '.';
    }
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422);
    }


    $be = Basic::select('smtp_status', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name', 'to_mail', 'website_title')->firstOrFail();


    //get language
    $locale = $request->header('Accept-Language');
    $language = $locale ? Language::where('code', $locale)->first()
      : Language::where('is_default', 1)->first();

    $car = Car::with(['car_content' => function ($query) use ($language) {
      return $query->where('language_id', $language->id);
    }])->where('id', $request->car_id)->first();

    $car_name = $car->car_content->title;
    $slug = $car->car_content->slug;
    $url = route('frontend.car.details', ['slug' => $slug, 'id' => $request->car_id]);


    if ($car->vendor_id != 0) {
      $vendor = Vendor::where('id', $car->vendor_id)->select('email', 'username')->first();
      $send_email_address = $vendor->email;
      $user_name = $vendor->username;
    } else {
      $send_email_address = $be->to_mail;
      $user_name = 'Admin';
    }


    if ($be->smtp_status == 1) {
      $subject = 'Inquiry about ' . $car_name;

      $body = $mail_template->mail_body;
      $body = preg_replace("/{username}/", $user_name, $body);
      $body = preg_replace("/{car_name}/", "<a href=" . $url . ">$car_name</a>", $body);
      $body = preg_replace("/{enquirer_name}/", $request->name, $body);
      $body = preg_replace("/{enquirer_email}/", $request->email, $body);
      $body = preg_replace("/{enquirer_phone}/", $request->phone, $body);
      $body = preg_replace("/{enquirer_message}/", nl2br($request->message), $body);
      $body = preg_replace("/{website_title}/", $be->website_title, $body);

      // if smtp status == 1, then set some value for PHPMailer
      if ($be->smtp_status == 1) {
        try {
          $smtp = [
            'transport' => 'smtp',
            'host' => $be->smtp_host,
            'port' => $be->smtp_port,
            'encryption' => $be->encryption,
            'username' => $be->smtp_username,
            'password' => $be->smtp_password,
            'timeout' => null,
            'auth_mode' => null,
          ];
          Config::set('mail.mailers.smtp', $smtp);
        } catch (\Exception $e) {
          Session::flash('error', $e->getMessage());
          return back();
        }
      }
      try {
        $data = [
          'to' => $send_email_address,
          'subject' => $subject,
          'body' => $body,
        ];
        if ($be->smtp_status == 1) {
          Mail::send([], [], function (Message $message) use ($data, $be) {
            $fromMail = $be->from_mail;
            $fromName = $be->from_name;
            $message->to($data['to'])
              ->subject($data['subject'])
              ->from($fromMail, $fromName)
              ->html($data['body'], 'text/html');
          });
        }

        return response()->json([
          'status' => 'success',
          'message' => __('Message sent successfully')
        ]);
      } catch (Exception $e) {
        return response()->json([
          'status' => 'error',
          'message' => __('Something went wrong!')
        ]);
      }
    }
  }
}
