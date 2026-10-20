@extends('backend.layout')

{{-- this style will be applied when the direction of language is right-to-left --}}
@includeIf('admin.partials.rtl-style')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Mobile App Settings') }}</h4>
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
                <a href="#">{{ __('Plugins') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <!-- store firebase service file to send notifications -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.basic_settings.updateFirebase') }}" method="post"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Firebase') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Upload Firebase Admin JSON') . '*' }}</label>
                                    <input type="file" class="form-control" name="app_firebase_json_file"
                                        value="{{ !empty($data) ? $data->app_firebase_json_file : '' }}">
                                    <small
                                        class="text-warning">{{ __('Upload the Firebase Admin SDK JSON file from your Firebase project.') }}</small>

                                    @if ($errors->has('app_firebase_json_file'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('app_firebase_json_file') }}</p>
                                    @endif
                                    @if ($data && $data->app_firebase_json_file)
                                        <br>
                                        <span
                                            class="text-warning">{{ __('You have a file, and you can change it by re-uploading it.') }}</span>
                                    @endif
                                    <br>
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
