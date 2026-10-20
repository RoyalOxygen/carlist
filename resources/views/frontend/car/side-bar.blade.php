<div class="col-lg-4 col-xl-3">
  <div class="widget-offcanvas offcanvas-lg offcanvas-start" tabindex="-1" id="widgetOffcanvas"
    aria-labelledby="widgetOffcanvas">
    <div class="offcanvas-header px-20">
      <h4 class="offcanvas-title">{{ __('Filter') }}</h4>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#widgetOffcanvas"
        aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-3 p-lg-0">
      <form action="{{ route('frontend.cars') }}" method="get" id="searchForm" class="w-100">
        <aside class="widget-area" data-aos="fade-up">

          <div class="widget widget-select p-0 mb-15">
            <h5 class="title">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#select"
                aria-expanded="true" aria-controls="select">
                {{ __('Category') }}
              </button>
            </h5>
            <div id="select" class="collapse show">
              <div class="accordion-body mt-10">
                <div class="row gx-sm-3">
                  <div class="col-12">
                    <div class="form-group">
                      <select name="category" id="" class="form-control select2" onchange="updateUrl()">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($categories as $category)
                          <option @selected(request()->input('category') == $category->slug) value="{{ $category->slug }}">{{ $category->name }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          @if ($countries->count() > 0)
            <div class="widget widget-select p-0 mb-15">
              <h5 class="title">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#country"
                  aria-expanded="true" aria-controls="country">
                  {{ __('Country') }}
                </button>
              </h5>
              <div id="country" class="collapse show">
                <div class="accordion-body mt-10">
                  <div class="row gx-sm-3">
                    <div class="col-12">
                      <div class="form-group">
                        <select name="country" id="" class="form-control select2" onchange="updateUrl()">
                          <option value="">{{ __('All') }}</option>
                          @foreach ($countries as $country)
                            <option @selected(request()->input('country') == $country->slug) value="{{ $country->slug }}">{{ $country->name }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endif

          @if ($states->count() > 0)
            <div class="widget widget-select p-0 mb-15">
              <h5 class="title">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#state"
                  aria-expanded="true" aria-controls="state">
                  {{ __('State') }}
                </button>
              </h5>
              <div id="state" class="collapse show">
                <div class="accordion-body mt-10">
                  <div class="row gx-sm-3">
                    <div class="col-12">
                      <div class="form-group">
                        <select name="state" id="" class="form-control select2" onchange="updateUrl()">
                          <option value="">{{ __('All') }}</option>
                          @foreach ($states as $state)
                            <option @selected(request()->input('state') == $state->slug) value="{{ $state->slug }}">{{ $state->name }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endif
          @if ($cities->count() > 0)
            <div class="widget widget-select p-0 mb-15">
              <h5 class="title">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#city"
                  aria-expanded="true" aria-controls="city">
                  {{ __('City') }}
                </button>
              </h5>
              <div id="city" class="collapse show">
                <div class="accordion-body mt-10">
                  <div class="row gx-sm-3">
                    <div class="col-12">
                      <div class="form-group">
                        <select name="city" id="" class="form-control select2" onchange="updateUrl()">
                          <option value="">{{ __('All') }}</option>
                          @foreach ($cities as $city)
                            <option @selected(request()->input('city') == $city->slug) value="{{ $city->slug }}">{{ $city->name }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endif

          <div class="widget widget-select p-0 mb-15">
              <h5 class="title">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#year"
                  aria-expanded="true" aria-controls="year">
                  {{ __('Year') }}
                </button>
              </h5>
              <div id="year" class="collapse show">
                <div class="accordion-body mt-10">
                  <div class="row gx-sm-3">
                    <div class="col-12">
                      <div class="form-group">
                        <select name="year" id="" class="form-control select2" onchange="updateUrl()">
                          <option value="">{{ __('All') }}</option>
                          @foreach($years as $year)
                            <option @selected(request()->input('year') == $year) value="{{ $year}}">{{ $year }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          <div class="widget widget-select p-0 mb-15">
            <h5 class="title">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#title"
                aria-expanded="true" aria-controls="title">
                {{ __('Car Title') }}
              </button>
            </h5>
            <div id="title" class="collapse show">
              <div class="accordion-body scroll-y mt-10">
                <div class="row gx-sm-3">
                  <div class="col-12">
                    <div class="form-group">
                      <input type="text" class="form-control" id="searchByTitle" name="title"
                        value="{{ request()->input('title') }}" placeholder="{{ __('Search By Car Title') }}">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="widget widget-select p-0 mb-15">
            <h5 class="title">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#location"
                aria-expanded="true" aria-controls="location">
                {{ __('Location') }}
              </button>
            </h5>
            <div id="location" class="collapse show">
              <div class="accordion-body scroll-y mt-10">
                <div class="row gx-sm-3">
                  <div class="col-12">
                    <div class="form-group">
                      <input type="text" name="location" placeholder="{{ __('Search By Location') }}"
                        class="form-control" id="searchByLocation" value="{{ request()->input('location') }}">
                      @if ($websiteInfo->google_map_api_key_status == 1)
                        <button type="button" class="btn btn-sm current-location" onclick="getCurrentLocation()">
                          <i class="fas fa-crosshairs"></i>
                        </button>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="widget widget-ratings p-0 mb-15">
            <h5 class="title">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#ratings"
                aria-expanded="true" aria-controls="ratings">
                {{ __('Brands') }}
              </button>
            </h5>
            <div id="ratings" class="collapse show">
              <div class="accordion-body scroll-y mt-10">
                <ul class="list-group custom-checkbox">
                  @php
                    if (!empty(request()->input('brands'))) {
                        $selected_brands = [];
                        if (is_array(request()->input('brands'))) {
                            $selected_brands = request()->input('brands');
                        } else {
                            array_push($selected_brands, request()->input('brands'));
                        }
                    } else {
                        $selected_brands = [];
                    }
                  @endphp

                  @foreach ($brands as $brand)
                    <li>
                      <input class="input-checkbox" type="checkbox" name="brands[]"
                        id="checkbox{{ $brand->id }}" value="{{ $brand->slug }}"
                        {{ in_array($brand->slug, $selected_brands) ? 'checked' : '' }} onchange="updateUrl()">

                      <label class="form-check-label"
                        for="checkbox{{ $brand->id }}"><span>{{ $brand->name }}</span></label>
                    </li>
                  @endforeach
                </ul>
              </div>
            </div>
          </div>

          @if (request()->filled('brands'))
            <div class="widget widget-ratings p-0 mb-15">
              <h5 class="title">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#models"
                  aria-expanded="true" aria-controls="models">
                  {{ __('Models') }}
                </button>
              </h5>
              @php
                $selected_brands = request()->input('brands');
                if (is_array($selected_brands)) {
                    $selected_brands = $selected_brands;
                } else {
                    $selected_brands = [$selected_brands];
                }
              @endphp
              <div id="models" class="collapse show">
                <div class="accordion-body scroll-y mt-10">
                  <ul class="list-group custom-checkbox">
                    @php
                      if (!empty(request()->input('models'))) {
                          $selected_models = [];
                          if (is_array(request()->input('models'))) {
                              $selected_models = request()->input('models');
                          } else {
                              array_push($selected_models, request()->input('models'));
                          }
                      } else {
                          $selected_models = [];
                      }
                    @endphp
                    @foreach ($selected_brands as $selected_brand)
                      @php
                        $s_brand = App\Models\Car\Brand::where('slug', $selected_brand)->first();
                        if ($s_brand) {
                            $models = App\Models\Car\CarModel::where([
                                ['brand_id', $s_brand->id],
                                ['status', 1],
                            ])->get();
                        } else {
                            $models = [];
                        }
                      @endphp
                      @foreach ($models as $model)
                        <li>
                          <input class="input-checkbox" type="checkbox" name="models[]"
                            id="checkbox{{ $model->id }}"
                            {{ in_array($model->slug, $selected_models) ? 'checked' : '' }}
                            value="{{ $model->slug }}" onchange="updateUrl()">

                          <label class="form-check-label"
                            for="checkbox{{ $model->id }}"><span>{{ $model->name }}</span></label>
                        </li>
                      @endforeach
                    @endforeach
                  </ul>
                </div>
              </div>
            </div>
          @endif

          <div class="widget widget-select p-0 mb-15">
            <h5 class="title">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#select3"
                aria-expanded="true" aria-controls="select">
                {{ __('Fuel Types') }}
              </button>
            </h5>
            <div id="select3" class="collapse show">
              <div class="accordion-body mt-10">
                <div class="row gx-sm-3">
                  <div class="col-xl-12">
                    <div class="form-group">
                      <select class=" form-control select2" onchange="updateUrl()" name="fuel_type">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($fuel_types as $fuel_type)
                          <option {{ request()->input('fuel_type') == $fuel_type->slug ? 'selected' : '' }}
                            value="{{ $fuel_type->slug }}">{{ $fuel_type->name }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="widget widget-select p-0 mb-15">
            <h5 class="title">
              <button class="accordion-button" type="button" data-bs-toggle="collapse"
                data-bs-target="#transmission" aria-expanded="true" aria-controls="transmission">
                {{ __('Transmission Types') }}
              </button>
            </h5>
            <div id="transmission" class="collapse show">
              <div class="accordion-body mt-10">
                <div class="row gx-sm-3">
                  <div class="col-xl-12">
                    <div class="form-group">
                      <select class="form-control select2" name="transmission" onchange="updateUrl()">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($transmission_types as $transmission_type)
                          <option {{ request()->input('transmission') == $transmission_type->slug ? 'selected' : '' }}
                            value="{{ $transmission_type->slug }}">{{ $transmission_type->name }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="widget widget-select p-0 mb-15">
            <h5 class="title">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#select2"
                aria-expanded="true" aria-controls="select">
                {{ __('Conditions') }}
              </button>
            </h5>
            <div id="select2" class="collapse show">
              <div class="accordion-body mt-10">
                <div class="row gx-sm-3">
                  <div class="col-12">
                    <div class="form-group">
                      <select class="form-control select2" name="condition" onchange="updateUrl()">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($car_conditions as $car_condition)
                          <option {{ request()->input('condition') == $car_condition->slug ? 'selected' : '' }}
                            value="{{ $car_condition->slug }}">{{ $car_condition->name }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="widget widget-price p-0 mb-15">
            <h5 class="title">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#price"
                aria-expanded="true" aria-controls="price">
                {{ __('Pricing') }}
              </button>
            </h5>
            <div id="price" class="collapse show">
              <div class="accordion-body scroll-y mt-10">
                <div class="row gx-sm-3 d-none">
                  <div class="col-md-6">
                    <div class="form-group mb-15">
                      <input class="form-control" type="hidden"
                        value="{{ request()->filled('min') ? request()->input('min') : $min }}" name="min"
                        id="min">

                      <input class="form-control" type="hidden" value="{{ $min }}" id="o_min">
                      <input class="form-control" type="hidden" value="{{ $max }}" id="o_max">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-15">
                      <input class="form-control"
                        value="{{ request()->filled('max') ? request()->input('max') : $max }}" type="hidden"
                        name="max" id="max">
                    </div>
                  </div>
                </div>
                <input type="hidden" id="currency_symbol" value="{{ $basicInfo->base_currency_symbol }}">
                <div class="price-item mt-10">
                  <div class="price-slider" data-range-slider='filterPriceSlider'></div>
                  <div class="price-value">
                    <span class="color-dark">{{ __('Price') . ':' }}
                      <span class="filter-price-range" data-range-value='filterPriceSliderValue'></span>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="cta">
            <a href="{{ route('frontend.cars') }}" class="btn btn-lg btn-primary icon-start w-100"><i
                class="fal fa-sync-alt"></i>{{ __('Reset All') }}</a>
          </div>
          <!-- Spacer -->
          <div class="pb-40"></div>
        </aside>
      </form>
    </div>
  </div>

</div>
