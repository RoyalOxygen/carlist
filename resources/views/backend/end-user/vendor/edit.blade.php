@extends('backend.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Edit Vendor') }}</h4>
    <ul class="breadcrumbs">
      <li class="nav-home">
        <a href="{{ route('admin.dashboard') }}">
          <i class="flaticon-home"></i>
        </a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Vendor Management') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="{{ route('admin.vendor_management.registered_vendor') }}">{{ __('Registered Vendors') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Edit Vendor') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-12">
              <div class="card-title">{{ __('Edit Vendor') }}</div>
            </div>
          </div>
        </div>

        <div class="card-body">
          <div class="row">
            <div class="col-lg-10 mx-auto">
              <form id="ajaxEditForm"
                action="{{ route('admin.vendor_management.vendor.update_vendor', ['id' => $vendor->id]) }}"
                method="post">
                @csrf
                <h2>Details</h2>
                <hr>
                <div class="row">
                  <div class="col-lg-12">
                    <div class="form-group">
                      <label for="">{{ __('Photo') }}</label>
                      <br>
                      <div class="thumb-preview">
                        @if ($vendor->photo != null)
                          <img src="{{ asset('assets/admin/img/vendor-photo/' . $vendor->photo) }}" alt="..."
                            class="uploaded-img">
                        @else
                          <img src="{{ asset('assets/img/noimage.jpg') }}" alt="..." class="uploaded-img">
                        @endif

                      </div>

                      <div class="mt-3">
                        <div role="button" class="btn btn-primary btn-sm upload-btn">
                          {{ __('Choose Photo') }}
                          <input type="file" class="img-input" name="photo">
                        </div>
                        <p id="editErr_photo" class="mt-1 mb-0 text-danger em"></p>
                        <p class="mt-2 mb-0 text-warning">{{ __('Image Size 80x80') }}</p>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4">
                    <div class="form-group">
                      <label>{{ __('Username*') }}</label>
                      <input type="text" value="{{ $vendor->username }}" class="form-control" name="username">
                      <p id="editErr_username" class="mt-1 mb-0 text-danger em"></p>
                    </div>
                  </div>
                  <div class="col-lg-4">
                    <div class="form-group">
                      <label>{{ __('Email*') }}</label>
                      <input type="text" value="{{ $vendor->email }}" class="form-control" name="email">
                      <p id="editErr_email" class="mt-1 mb-0 text-danger em"></p>
                    </div>
                  </div>
                  <div class="col-lg-4">
                    <div class="form-group">
                      <label>{{ __('Phone') }}</label>
                      <input type="tel" value="{{ $vendor->phone }}" class="form-control" name="phone">
                      <p id="editErr_phone" class="mt-1 mb-0 text-danger em"></p>
                    </div>
                  </div>

                  <div class="col-lg-12">
                    <div class="row">
                      <div class="col-lg-4">
                        <div class="form-group">
                          <div class="custom-control custom-checkbox">
                            <input type="checkbox" {{ $vendor->show_email_addresss == 1 ? 'checked' : '' }}
                              name="show_email_addresss" class="custom-control-input" id="show_email_addresss">
                            <label class="custom-control-label"
                              for="show_email_addresss">{{ __('Show Email Address in Profile Page') }}</label>
                          </div>
                        </div>
                      </div>
                      <div class="col-lg-4">
                        <div class="form-group">
                          <div class="custom-control custom-checkbox">
                            <input type="checkbox" {{ $vendor->show_phone_number == 1 ? 'checked' : '' }}
                              name="show_phone_number" class="custom-control-input" id="show_phone_number">
                            <label class="custom-control-label"
                              for="show_phone_number">{{ __('Show Phone Number in Profile Page') }}</label>
                          </div>
                        </div>
                      </div>
                      <div class="col-lg-4">
                        <div class="form-group">
                          <div class="custom-control custom-checkbox">
                            <input type="checkbox" {{ $vendor->show_contact_form == 1 ? 'checked' : '' }}
                              name="show_contact_form" class="custom-control-input" id="show_contact_form">
                            <label class="custom-control-label"
                              for="show_contact_form">{{ __('Show Contact Form') }}</label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-12">
                    <div id="accordion" class="mt-5">
                      @foreach ($languages as $language)
                        <div class="version">
                          <div class="version-header" id="heading{{ $language->id }}">
                            <h5 class="mb-0">
                              <button type="button"
                                class="btn btn-link {{ $language->direction == 1 ? 'rtl text-right' : '' }}"
                                data-toggle="collapse" data-target="#collapse{{ $language->id }}"
                                aria-expanded="{{ $language->is_default == 1 ? 'true' : 'false' }}"
                                aria-controls="collapse{{ $language->id }}">
                                {{ $language->name . __(' Language') }}
                                {{ $language->is_default == 1 ? '(Default)' : '' }}
                              </button>
                            </h5>
                          </div>

                          @php
                            $vendor_info = App\Models\VendorInfo::where('vendor_id', $vendor->id)
                                ->where('language_id', $language->id)
                                ->first();
                          @endphp

                          <div id="collapse{{ $language->id }}"
                            class="collapse {{ $language->is_default == 1 ? 'show' : '' }}"
                            aria-labelledby="heading{{ $language->id }}" data-parent="#accordion">
                            <div class="version-body">
                              <div class="row">
                                <div class="col-lg-4">
                                  <div class="form-group">
                                    <label>{{ __('Name*') }}</label>
                                    <input type="text" value="{{ !empty($vendor_info) ? $vendor_info->name : '' }}"
                                      class="form-control" name="{{ $language->code }}_name"
                                      placeholder="{{ __('Enter Name') }}">
                                    <p id="editErr_{{ $language->code }}_name" class="mt-1 mb-0 text-danger em"></p>
                                  </div>
                                </div>

                                @php
                                  $Countries = App\Models\Location\Country::where('language_id', $language->id)->get();
                                  $totalCountry = $Countries->count();
                                @endphp

                                @if ($totalCountry > 0)
                                  <div class="col-lg-4">
                                    <div class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">

                                      <label>{{ __('Country') }}</label>
                                      <select name="{{ $language->code }}_country"
                                        class="form-control js-example-basic-single8"
                                        data-code="{{ $language->code }}">
                                        <option selected disabled>{{ __('Select a country') }}</option>

                                        @foreach ($Countries as $Country)
                                          <option @selected(@$vendor_info->country == $Country->id) value="{{ $Country->id }}">
                                            {{ $Country->name }}</option>
                                        @endforeach
                                      </select>
                                    </div>
                                  </div>
                                @endif

                                @php
                                  $States = App\Models\Location\State::where('language_id', $language->id)->get();
                                  $totalState = $States->count();
                                  $Stateshave = App\Models\Location\State::where([
                                      ['language_id', $language->id],
                                      ['country_id', $vendor_info ? $vendor_info->country : 0],
                                  ])->get();
                                  $totalStateshave = $Stateshave->count();
                                @endphp

                                @if ($totalState > 0)
                                  <div
                                    class="col-lg-4 {{ $language->code }}_hide_state @if ($totalStateshave == 0) d-none @endif ">
                                    <div class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">

                                      <label>{{ __('State') }} </label>
                                      <select name="{{ $language->code }}_state"
                                        class="form-control js-example-basic-single9 {{ $language->code }}_country_state_id"data-code="{{ $language->code }}">

                                        <option selected disabled>{{ __('Select a state') }}</option>

                                        @foreach ($States as $State)
                                          <option @selected(@$vendor_info->state == $State->id) value="{{ $State->id }}">
                                            {{ $State->name }}</option>
                                        @endforeach
                                      </select>
                                    </div>
                                  </div>
                                @endif

                                @php
                                  $cities = App\Models\Location\City::where('language_id', $language->id)->get();
                                  $totalCity = $cities->count();
                                @endphp

                                <div class="col-lg-4">
                                  <div class="form-group {{ $language->direction == 1 ? 'rtl text-right' : '' }}">

                                    <label>{{ __('City') }} </label>
                                    <select name="{{ $language->code }}_city"
                                      class="form-control js-example-basic-single5 {{ $language->code }}_state_city_id">
                                      <option selected disabled>{{ __('Select a city') }}</option>

                                      @foreach ($cities as $City)
                                        <option @selected(@$vendor_info->city == $City->id) value="{{ $City->id }}">
                                          {{ $City->name }}</option>
                                      @endforeach
                                    </select>
                                  </div>
                                </div>
                                <div class="col-lg-4">
                                  <div class="form-group">
                                    <label>{{ __('Zip Code') }}</label>
                                    <input type="text"
                                      value="{{ !empty($vendor_info) ? $vendor_info->zip_code : '' }}"
                                      class="form-control" name="{{ $language->code }}_zip_code"
                                      placeholder="{{ __('Enter Zip Code') }}">
                                    <p id="editErr_{{ $language->code }}_zip_code" class="mt-1 mb-0 text-danger em">
                                    </p>
                                  </div>
                                </div>
                                <div class="col-lg-12">
                                  <div class="form-group">
                                    <label>{{ __('Address') }}</label>
                                    <textarea name="{{ $language->code }}_address" class="form-control" placeholder="{{ __('Enter Address') }}">{{ !empty($vendor_info) ? $vendor_info->address : '' }}</textarea>
                                    <p id="editErr_{{ $language->code }}_email" class="mt-1 mb-0 text-danger em"></p>
                                  </div>
                                </div>
                                <div class="col-lg-12">
                                  <div class="form-group">
                                    <label>{{ __('Details') }}</label>
                                    <textarea name="{{ $language->code }}_details" class="form-control" rows="5"
                                      placeholder="{{ __('Enter Details') }}">{{ !empty($vendor_info) ? $vendor_info->details : '' }}</textarea>
                                    <p id="editErr_{{ $language->code }}_details" class="mt-1 mb-0 text-danger em"></p>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  </div>

                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="card-footer">
          <div class="row">
            <div class="col-12 text-center">
              <button type="submit" id="updateBtn" class="btn btn-success">
                {{ __('Update') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endsection
  @section('script')
    <script>
      var getStateUrl = "{{ route('admin.car_management.get-state') }}";
      var getCityUrl = "{{ route('admin.car_management.get-city') }}";
    </script>
    <script src="{{ asset('assets/js/admin-car.js') }}"></script>
  @endsection
