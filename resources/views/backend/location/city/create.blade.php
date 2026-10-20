<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add City') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form id="ajaxForm" class="modal-form create"
          action="{{ route('admin.car_specification.location.store_city') }}" method="post"
          enctype="multipart/form-data">
          @csrf
          <div class="form-group">
            <label for="">{{ __('Language') . '*' }}</label>
            <select name="m_language_id" class="form-control">
              <option selected disabled>{{ __('Select a Language') }}</option>
              @foreach ($langs as $lang)
                <option value="{{ $lang->id }}">{{ $lang->name }}</option>
              @endforeach
            </select>
            <p id="err_m_language_id" class="mt-2 mb-0 text-danger em"></p>
          </div>

          <div class="form-group d-none"id="hide_country">
            <label for="">{{ __('Country') . '*' }}</label>
            <select name="country_id" class="form-control" id="country_id">
              <option selected disabled>{{ __('Select a country') }}</option>
            </select>
            <p id="err_country_id" class="mt-2 mb-0 text-danger em"></p>
          </div>
          <div class="form-group d-none" id="hide_state">
            <label for="">{{ __('State') . '*' }}</label>
            <select name="state_id" class="form-control">
              <option selected disabled>{{ __('Select a state') }}</option>
            </select>
            <p id="err_state_id" class="mt-2 mb-0 text-danger em"></p>
          </div>

          <div class="form-group">
            <label for="">{{ __('Name') . '*' }}</label>
            <input type="text" class="form-control" name="name" placeholder="{{ __('Enter City Name') }}">
            <p id="err_name" class="mt-2 mb-0 text-danger em"></p>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          {{ __('Close') }}
        </button>
        <button id="submitBtn" type="button" class="btn btn-primary btn-sm">
          {{ __('Save') }}
        </button>
      </div>
    </div>
  </div>
</div>
