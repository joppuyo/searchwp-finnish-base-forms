(function ($) {
  var update = function () {
    if ($('input[name=api_type]:checked').val() === 'command_line') {
      $('.js-searchwp-finnish-base-forms-api-url').hide();
    } else {
      $('.js-searchwp-finnish-base-forms-api-url').show();
    }
    $('.js-searchwp-finnish-base-forms-test-output').html('');
  };
  $(document).ready(function () {
    update();
    $('input[name=api_type]').change(function () {
      update();
    });
    $('.js-searchwp-finnish-base-forms-test').click(function () {
      var data = {
        action: 'searchwp_finnish_base_forms_lemmatize',
      };
      if ($('input[name=api_type]:checked').val() === 'command_line') {
        data.api_type = 'command_line';
      } else {
        data.api_type = 'web_api';
        data.api_root = $('input[name=api_url]').val();
      }
      $.post(ajaxurl, data, function (response) {
        $('.js-searchwp-finnish-base-forms-test-output').html(response);
      });
    });
  });
})(jQuery);
