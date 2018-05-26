(function ($) {
  var update = function () {
    if ($('input[name=api_type]:checked').val() === 'command_line') {
      $('.js-searchwp-finnish-base-forms-api-url').hide();
    } else {
      $('.js-searchwp-finnish-base-forms-api-url').show();
    }
  };
  $(document).ready(function () {
    update();
    $('input[name=api_type]').change(function () {
      update();
    });
  });
})(jQuery);
