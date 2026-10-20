@extends('backend.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Views') }}</h4>
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
        <a href="#">{{ __('Cars Management') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Views') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <form action="{{ route('admin.listing_management.update_settings') }}" method="post">
          @csrf
          <div class="card-header">
            <div class="row">
              <div class="col-lg-10">
                <div class="card-title">{{ __('Views') }}</div>
              </div>
            </div>
          </div>

          <div class="card-body">
            <div class="row">
              <div class="col-lg-6 offset-lg-3">
                <div class="form-group">
                  <label>{{ __('Car View') }}</label>
                  <div class="selectgroup w-100">
                    <label class="selectgroup-item">
                      <input type="radio" name="car_view" value="gird/list" class="selectgroup-input"
                        {{ $info->car_view == 'gird/list' ? 'checked' : '' }}>
                      <span class="selectgroup-button">{{ __('Gird&List') }}</span>
                    </label>
                    <label class="selectgroup-item">
                      <input type="radio" name="car_view" value="map" class="selectgroup-input"
                        {{ $info->car_view == 'map' ? 'checked' : '' }}>
                      <span class="selectgroup-button">{{ __('Map') }}</span>
                    </label>
                  </div>
                  @error('car_view')
                    <p class="mb-0 text-danger">{{ $message }}</p>
                  @enderror
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer">
            <div class="row">
              <div class="col-12 text-center">
                <button type="submit" class="btn btn-success">
                  {{ __('Update') }}
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
