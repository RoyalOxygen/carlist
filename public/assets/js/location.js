"use strict";
$(document).ready(function () {
  $('select[name="m_language_id"]').on('change', function () {
    var selectedLanguageId = $(this).val();

    var countrySelect = $('select[name="country_id"]');
    countrySelect.empty();
    var StateSelect = $('select[name="state_id"]');
    StateSelect.empty();


    if (selectedLanguageId) {
      $.ajax({
        url: baseUrl + 'admin/car-specification/location/get-country/' + selectedLanguageId,
        type: 'GET',
        data: { language_id: selectedLanguageId },
        success: function (response) {


          if (response.countries && response.countries.length > 0) {
            $('#hide_country').removeClass('d-none');
            $('#hide_state').addClass('d-none');
            countrySelect.append($('<option>', {
              value: '',
              text: 'Select a country',
              disabled: false,
              selected: true
            }));

            $.each(response.countries, function (index, country) {
              countrySelect.append($('<option>', {
                value: country.id,
                text: country.name
              }));
            });
          } else {
            $('#hide_country').addClass('d-none');
            if (response.states && response.states.length > 0) {
              $('#hide_state').removeClass('d-none');
              StateSelect.append($('<option>', {
                value: '',
                text: 'Select a state',
                disabled: true,
                selected: true
              }));

              // Add other options from the response
              $.each(response.states, function (index, state) {
                StateSelect.append($('<option>', {
                  value: state.id,
                  text: state.name
                }));
              });
            } else {
              $('#hide_state').addClass('d-none');
            }
          }

        },
        error: function () {
          console.error('Error fetching Countries');
        }
      });
    }
  });

  $('#country_id').on('change', function () {

    var country = $(this).val();

    var StateSelect = $('select[name="state_id"]');
    StateSelect.empty();

    if (country) {
      // Make an AJAX request to fetch states for the selected country
      $.ajax({
        url: baseUrl + 'admin/car-specification/location/get-state/' + country,
        type: 'GET',
        success: function (response) {

          if (response.states) {

            if (response.states && response.states.length > 0) {
              $('#hide_state').removeClass('d-none');
              StateSelect.append($('<option>', {
                value: '',
                text: 'Select a state',
                disabled: true,
                selected: true
              }));

              // Add other options from the response
              $.each(response.states, function (index, state) {
                StateSelect.append($('<option>', {
                  value: state.id,
                  text: state.name
                }));
              });
            } else {
              $('#hide_state').addClass('d-none');
            }
          } else {
            // If no states are available, add a default disabled option
            StateSelect.append($('<option>', {
              disabled: true,
              selected: true,
              text: 'No State available for this Country'
            }));
          }
        },
        error: function () {
          console.error('Error fetching States');
        }
      });
    }
  });

  $('#in_country_id').on('change', function () {

    var country = $(this).val();
    var StateSelect = $('select[name="state_id"]');
    StateSelect.empty();

    if (country) {
      // Make an AJAX request to fetch states for the selected country
      $.ajax({
        url: baseUrl + 'admin/listing-specification/location/get-state/' + country,
        type: 'GET',
        success: function (response) {

          if (response.states) {

            if (response.states && response.states.length > 0) {
              $('#e_hide_state').removeClass('d-none');
              StateSelect.append($('<option>', {
                value: '',
                text: Selectastate,
                disabled: true,
                selected: true
              }));

              // Add other options from the response
              $.each(response.states, function (index, state) {
                StateSelect.append($('<option>', {
                  value: state.id,
                  text: state.name
                }));
              });
            } else {
              $('#e_hide_state').addClass('d-none');
            }
          } else {
            // If no states are available, add a default disabled option
            StateSelect.append($('<option>', {
              disabled: true,
              selected: true,
              text: 'No State available for this Country'
            }));
          }
        },
        error: function () {
          console.error('Error fetching States');
        }
      });
    }
  });
});

