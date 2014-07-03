(function ($) {
  $(document).ready(function() {
    function mepr_get_post_args() {
      var listname = $('#mepraweber_opt_in').attr('data-listname');
      var args = { listname: listname,
                   redirect: 'http://www.aweber.com/thankyou-coi.htm?m=text',
                   meta_adtracking: 'memberpress',
                   meta_message: 1,
                   meta_forward_vars: 1,
                   email: $('#user_email').val()
                 };
      var name = '';
      if( $('#user_first_name').val() != '' ) {
        name = $('#user_first_name').val();
        
        if( $('#user_last_name').val() != '' ) {
          name = name + ' ' + $('#user_last_name').val();
        }
        
        args['name'] = name;
      }
      
      return args;
    }
    
    $('#mepr_registerform input').keypress( function(e) {
      if(e.which==13) {
        e.preventDefault();
        
        // We're bypassing the aweber api due to its complexity and
        // opting for a straight js post from the client side now
        if( $('#mepraweber_opt_in').is(':checked') && $('#user_email').val()!='' ) {
          args = mepr_get_post_args();
          $.post( "http://www.aweber.com/scripts/addlead.pl", args ).complete( function() { 
            $('#mepr_registerform').submit();
          });
        }
        else if( !$('#mepraweber_opt_in').is(':checked') ) { // Just submit if not checked
          $('#mepr_registerform').submit();
        }
      }
    });
    
    $('#mepr_registerform input[type=submit],#mepr_registerform input[type=image]').click( function(e) {
      e.preventDefault();
      
      // We're bypassing the aweber api due to its complexity and
      // opting for a straight js post from the client side now
      if( $('#mepraweber_opt_in').is(':checked') && $('#user_email').val()!='' ) {
        args = mepr_get_post_args();
        $.post( "http://www.aweber.com/scripts/addlead.pl", args ).complete( function() { 
          $('#mepr_registerform').submit();
        });
      }
      else if( !$('#mepraweber_opt_in').is(':checked') ) { // Just submit if not checked
        $('#mepr_registerform').submit();
      }
    });
  });
})(jQuery);
