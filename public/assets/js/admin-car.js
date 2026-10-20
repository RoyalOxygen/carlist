"use strict";
$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  }
});

$(document).ready(function () {
  $('.js-example-basic-single1').select2();
  $('.js-example-basic-single2').select2();
  $('.js-example-basic-single3').select2();
  $('.js-example-basic-single4').select2();
  $('.js-example-basic-single5').select2();
  $('.js-example-basic-single6').select2();
  $('.js-example-basic-single7').select2();
  $('.js-example-basic-single8').select2();
  $('.js-example-basic-single9').select2();
});

$('body').on('change', '.js-example-basic-single3', function () {
  var id = $(this).val();
  var lang = $(this).attr('data-code');
  var added = lang + "_car_brand_model_id";

  $('.' + added + ' option').remove();
  $.ajax({
    type: 'POST',
    url: getModelUrl,
    data: {
      id: id,
      lang: lang
    },
    success: function (data) {
      $.each(data, function (key, value) {
        $('.' + added).append($('<option></option>').val(value.id).html(value
          .name));
      });
    }
  });
});

$('body').on('change', '.js-example-basic-single8', function () {
  $('.request-loader').addClass('show');
  var id = $(this).val();
  var lang = $(this).attr('data-code');
  var added = lang + "_country_state_id";
  var hh = lang + "_hide_state";
  var added2 = lang + "_state_city_id";

  $('.' + added + ' option').remove();
  $('.' + added2 + ' option').remove();
  $.ajax({
    type: 'POST',
    url: getStateUrl,
    data: {
      id: id,
      lang: lang
    },
    success: function (data) {
      $('.request-loader').removeClass('show');
      if (data) {
        if (data.states && data.states.length > 0) {

          $('.' + hh).removeClass('d-none');

          $('.' + added).append($('<option>', {
            value: '',
            text: 'Select a state',
            disabled: true,
            selected: true
          }));

          $.each(data.states, function (key, value) {
            $('.' + added).append($('<option></option>').val(value.id).html(value
              .name));
          });
          $('.' + added2).append($('<option>', {
            value: '',
            text: 'Select a country',
            disabled: true,
            selected: true
          }));
        } else {
          $('.' + hh).addClass('d-none');

          $('.' + added2).append($('<option>', {
            value: '',
            text: 'Select a country',
            disabled: true,
            selected: true
          }));
          $.each(data.cities, function (key, value) {
            $('.' + added2).append($('<option></option>').val(value.id).html(value
              .name));
          });
        }
      } else {

      }
    }
  });
});

$('body').on('change', '.js-example-basic-single9', function () {
  $('.request-loader').addClass('show');
  var id = $(this).val();
  var lang = $(this).attr('data-code');
  var added = lang + "_state_city_id";

  $('.' + added + ' option').remove();
  $.ajax({
    type: 'POST',
    url: getCityUrl,
    data: {
      id: id,
      lang: lang
    },
    success: function (data) {
      $('.request-loader').removeClass('show');

      if (data && data.length > 0) {

        $('.' + added).append($('<option>', {
          value: '',
          text: 'Select a country',
          disabled: true,
          selected: true
        }));

        $.each(data, function (key, value) {
          $('.' + added).append($('<option></option>').val(value.id).html(value
            .name));
        });
      } else {
        $('.' + added).append($('<option>', {
          value: '',
          text: 'No cities available',
          disabled: true,
          selected: true
        }));
      }
    }
  });
});
