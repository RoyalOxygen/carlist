<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Edit State') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <form id="ajaxEditForm" class="modal-form {{ $language->direction == 1 ? 'rtl text-right' : '' }}"
          action="{{ route('admin.car_specification.location.update_state') }}" method="post">
          @csrf
          <input type="hidden" name="id" id="in_id">

          <div class="form-group">
            <label for="">{{ __('Country') }}</label>
            <select name="country_id" class="form-control"id="in_country_id">
              <option value="" >{{ __('Select a Country') }}</option>
              @foreach ($countries as $country)
                <option value="{{ $country->id }}">{{ $country->name }}</option>
              @endforeach
            </select>
            <p id="err_country_id" class="mt-2 mb-0 text-danger em"></p>
          </div>
          
          <div class="form-group">
            <label for="">{{ __('Name') . '*' }}</label>
            <input type="text" class="form-control" name="name" placeholder="{{ __('Enter State Name') }}" id="in_name">
            <p id="editErr_name" class="mt-2 mb-0 text-danger em"></p>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
          {{ __('Close') }}
        </button>
        <button id="updateBtn" type="button" class="btn btn-primary btn-sm">
          {{ __('Update') }}
        </button>
      </div>
    </div>
  </div>
</div>
