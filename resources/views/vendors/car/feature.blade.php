<!-- Modal -->
<div class="modal fade" id="featureModal{{ $car->id }}" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Featured') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mt-3 mb-0" role="alert">
                    <strong>{{ __('Note:') }}</strong>
                    {{ __('If you are using our customer mobile application, these fields will be utilized within the app.') }}
                </div>

                <form action="{{ route('vendor.cars_management.update_featured_car') }}"
                    id="templateForm{{ $car->id }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="carId" value="{{ $car->id }}">
                    <input type="hidden" name="is_feature" value="">
                    <div class="form-group">
                        <label>{{ __('Featured Image (for Mobile App)') }}</label>
                        <div class="col-md-12 mb-3">
                            <div class="modal-img-size-v1">
                                <img src="{{ asset('assets/img/noimage.jpg') }}" class="uploaded-img">
                            </div>

                        </div>
                        <input type="file" name="mobile_image" class="img-input form-control image">
                        <p class="text-warning">
                            {{ __('Note: For the best visual appearance, please use a transparent image.') }}</p>
                        <p id="errmobile_image" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Color') }} </label>
                        <input class="jscolor form-control ltr" name="color" value=""
                            placeholder="{{ __('Enter Color Code') }}">
                        <p id="errcolor" class="mb-0 text-danger em"></p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                <button form="templateForm{{ $car->id }}" type="submit"
                    class="btn btn-primary">{{ __('Submit') }}</button>
                <button form="templateForm{{ $car->id }}" type="submit"
                    class="btn btn-primary">{{ __('Skip') }}</button>
            </div>
        </div>
    </div>
</div>
