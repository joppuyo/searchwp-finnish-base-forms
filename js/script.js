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
    $('.js-searchwp-finnish-base-forms-form').submit(function (event) {
      var self = this;
      event.preventDefault();
      $('.js-searchwp-finnish-base-forms-submit-button').attr('disabled', true);
      var data = {
        action: 'searchwp_finnish_base_forms_lemmatize',
      };
      if ($('input[name=api_type]:checked').val() === 'command_line') {
        data.api_type = 'command_line';
      } else {
        data.api_type = 'web_api';
        data.api_root = $('input[name=api_url]').val();
      }
      $.post(ajaxurl, data)
        .done(function() {
          $('.js-searchwp-finnish-base-forms-submit-button').attr('disabled', false);
          self.submit();
        })
        .fail(function() {
          window.scrollTo(0, 0);
          $('.js-searchwp-finnish-base-forms-submit-button').attr('disabled', false);
          $('.notice.notice-success').remove();
          $('.js-searchwp-finnish-base-forms-admin-notices').html(
            '<div class="notice notice-error">' +
              '<p>Failed to connect the Voikko API. Make sure Voikko has been correctly installed.</p>' +
            '</div>'
          );
        });
    });
  });
})(jQuery);
