@extends('backend.layout')

{{-- this style will be applied when the direction of language is right-to-left --}}
@includeIf('admin.partials.rtl-style')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('General Settings') }}</h4>
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
                <a href="{{ route('admin.mobile_interface') }}">{{ __('Mobile App Settings') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('General Settings') }}</a>
            </li>
        </ul>
    </div>



    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-10">
                            <div class="card-title">{{ __('Update General Settings') }}</div>
                        </div>

                        <div class="col-lg-2">
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form id="mobileGeneralForm" action="{{ route('admin.mobile_interface_gsetting.update') }}"
                        method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" value="{{ request()->input('language') }}" name="language">
                        <div class="row px-5">
                            <!-- Information -->
                            <div class="col-lg-12">
                                <fieldset class="form-group border mb-5 border-secondary rounded">
                                    <legend class="w-auto px-2 h3 font-weight-bold text-warning">
                                        {{ __('Information') }}
                                    </legend>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="">{{ __('API Base URL') . '*' }}</label>
                                                <input type="text" value="{{ @$config['PUBLIC_API_BASE'] }}"
                                                    class="form-control" name="api_base_url">
                                                @error('api_base_url')
                                                    <p class="mt-2 mb-0 text-danger">{{ $message }}</p>
                                                @enderror
                                                <span class="text-warning">
                                                    {{ __('Use this URL for API requests') }}:
                                                    <code>{{ asset('/') }}pgw</code>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label>{{ __('Enable Customer Mobile App') }}</label>



                                                <div class="selectgroup w-100">
                                                    <label class="selectgroup-item">
                                                        <input type="radio" name="has_mobile_app" value="1"
                                                            class="selectgroup-input" required
                                                            {{ $data->has_mobile_app == 1 ? 'checked' : '' }}>
                                                        <span class="selectgroup-button">{{ __('Enable') }}</span>
                                                    </label>

                                                    <label class="selectgroup-item">
                                                        <input type="radio" name="has_mobile_app" value="0"
                                                            class="selectgroup-input"
                                                            {{ $data->has_mobile_app == 0 ? 'checked' : '' }}>
                                                        <span class="selectgroup-button">{{ __('Disable') }}</span>
                                                    </label>
                                                </div>
                                                <small class="text-warning">
                                                    {{ __('Enable this option if you have a customer mobile application.') }}
                                                </small>
                                                @error('has_mobile_app')
                                                    <p class="mb-0 text-danger">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>



                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label for="">{{ __('Favicon') . '*' }}</label>
                                                <br>
                                                <div class="thumb-preview">
                                                    @if (!empty($data->mobile_favicon))
                                                        <img src="{{ asset('assets/img/mobile-interface/' . $data->mobile_favicon) }}"
                                                            alt="image" class="uploaded-img">
                                                    @else
                                                        <img src="{{ asset('assets/img/noimage.jpg') }}" alt="..."
                                                            class="uploaded-img">
                                                    @endif
                                                </div>
                                                <div class="mt-3">
                                                    <div role="button" class="btn btn-primary btn-sm upload-btn">
                                                        {{ __('Choose Image') }}
                                                        <input type="file" class="img-input" name="mobile_favicon">
                                                    </div>
                                                </div>
                                                @error('mobile_favicon')
                                                    <p class="mt-2 mb-0 text-danger">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="form-group">
                                                <label for="">{{ __('Logo') . '*' }}</label>
                                                <br>
                                                <div class="thumb-preview">
                                                    @if (!empty($data->mobile_app_logo))
                                                        <img src="{{ asset('assets/img/mobile-interface/' . $data->mobile_app_logo) }}"
                                                            alt="image" class="uploaded-img2">
                                                    @else
                                                        <img src="{{ asset('assets/img/noimage.jpg') }}" alt="..."
                                                            class="uploaded-img2">
                                                    @endif
                                                </div>
                                                <div class="mt-3">
                                                    <div role="button" class="btn btn-primary btn-sm upload-btn">
                                                        {{ __('Choose Image') }}
                                                        <input type="file" class="img-input2" name="mobile_app_logo">
                                                    </div>
                                                </div>
                                                @error('mobile_app_logo')
                                                    <p class="mt-2 mb-0 text-danger">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" form="mobileGeneralForm" class="btn btn-success">
                                {{ __('Update') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
