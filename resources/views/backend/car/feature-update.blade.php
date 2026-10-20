<!-- Modal -->
<div class="modal fade" id="templateImgModal{{ $car->id }}" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Edit Mobile Image') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-left">
                <form action="{{ route('admin.cars_management.update_mobile_image') }}"
                    id="editTemplateForm{{ $car->id }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="carId" value="{{ $car->id }}">
                    <div class="form-group">
                        <label for="">{{ __('Featured Image (for Mobile App)') }} *</label>
                        <div class="col-md-12 mb-3">
                            <div class="modal-img-size-v1">
                                <img
                                    src="{{ $car->mobile_image
                                        ? asset('assets/admin/img/car/mobile-image/' . $car->mobile_image)
                                        : asset('assets/img/noimage.jpg') }}"
                                    alt="Car Image" class="uploaded-img">
                            </div>
                        </div>
                        <input type="file" name="mobile_image" class="img-input form-control image">
                        <p class="eerrmobile_image mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('Color') }} **</label>
                        <input class="jscolor form-control" name="color" value="{{ $car->color }}"
                            placeholder="{{ __('Color') }}">
                        <p class="eerrcolor mb-0 text-danger em"></p>
                        <p id="err_color" class=" mb-0 text-danger em"></p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary update-btn"
                    data-form_id="editTemplateForm{{ $car->id }}">{{ __('Update') }}</button>
            </div>
        </div>
    </div>
</div>
