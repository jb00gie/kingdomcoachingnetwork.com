(function ($) {
  // this identifies your website in the createToken call below
  Stripe.setPublishableKey(MeprStripeGateway.public_key);

  $(document).ready(function() {
    $("#payment-form").submit(function(e) {
      e.preventDefault();
      // disable the submit button to prevent repeated clicks
      $('.submit-button').attr("disabled", "disabled");
      $('.stripe-loading-gif').show();

      var tok_args = {
        name: $('.card-name').val(),
        number: $('.card-number').val(),
        cvc: $('.card-cvc').val(),
        exp_month: $('.card-expiry-month').val(),
        exp_year: $('.card-expiry-year').val()
      };

      // Send address if it's there
      if( $('.card-address-1').length != 0 ) { tok_args['address_line1'] = $('.card-address-1').val(); }
      if( $('.card-address-2').length != 0 ) { tok_args['address_line2'] = $('.card-address-2').val(); }
      if( $('.card-city').length != 0 ) { tok_args['address_city'] = $('.card-city').val(); }
      if( $('.card-state').length != 0 ) { tok_args['address_state'] = $('.card-state').val(); }
      if( $('.card-zip').length != 0 ) { tok_args['address_zip'] = $('.card-zip').val(); }
      if( $('.card-country').length != 0 ) { tok_args['address_country'] = $('.card-country').val(); }

      // createToken returns immediately - the supplied callback submits the form if there are no errors
      Stripe.createToken( tok_args, function(status, response) {
        if(response.error) {
          // re-enable the submit button
          $('.submit-button').removeAttr("disabled");
          // show the errors on the form
          $(".errors").html(response.error.message);
          // hide the spinning gif bro
          $('.stripe-loading-gif').hide();
        } else {
          var form$ = $("#payment-form");
          // token contains id, last4, and card type
          var token = response['id'];
          // insert the token into the form so it gets submitted to the server
          form$.append("<input type='hidden' name='stripe_token' value='" + token + "' />");
          // and submit
          form$.get(0).submit();
        }
      });
      return false; // submit from callback
    });
  });
})(jQuery);
