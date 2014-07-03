/* Front-end ONLY JS */
(function ($) {
  $(document).ready(function(){
    //Disable signup button to prevent duplicate submissions
    //This actually works better than disabling the button itself
    var submit = 1;
    $( '#mepr_registerform,' +
       '#mepr_logged_in_purchase,' +
       '#mepr_authorize_net_payment_form,' +
       '#mepr_authorize_net_update_cc_form' ).submit(function() {
      if(submit) {
        submit = 0;
        $('.submit-button').attr("disabled", "disabled");
        $('.mepr-loading-gif').show();
        $(this).trigger('mepr-register-submit');
        return true;
      }
      return false;
    });
  });
})(jQuery);
