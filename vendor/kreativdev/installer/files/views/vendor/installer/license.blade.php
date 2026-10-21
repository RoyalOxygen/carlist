@extends('vendor.installer.layouts.master')

@section('template_title')
    Configuration
@endsection

@section('title')
    <i class="fa fa-cog fa-fw" aria-hidden="true"></i>
    Configuration
@endsection

@section('container')
    <p class="text-center">
        No license verification is required. Continue to environment setup.
    </p>
    <p class="text-center">
        <a href="{{ route('LaravelInstaller::environmentWizard') }}" class="button">
            Continue
            <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
        </a>
    </p>
@endsection
