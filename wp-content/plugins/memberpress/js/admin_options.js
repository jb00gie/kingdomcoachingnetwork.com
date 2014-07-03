(function($) {
  $(document).ready(function() {
    //Set the correct tab to display
    var hash = location.hash.replace('#','');

    if(hash == '') {
      hash = 'pages';
    }
    else {
      hash = hash.replace('mepr-','');
    }

    show_chosen_tab(hash);

    function show_chosen_tab(chosen)
    {
      var hash = '#mepr-' + chosen;

      //Adjust tab's style
      $('a.nav-tab-active').removeClass('nav-tab-active');
      $('a#' + chosen).addClass('nav-tab-active');

      //Adjust pane's style
      $('div.mepr-options-hidden-pane').hide();
      $('div#' + chosen).show();

      //Set action to the proper tab
      $('#mepr_options_form').attr('action', hash);
      window.location.hash = hash;
    }
    
    $('a.nav-tab').click(function() {
      if($(this).hasClass('nav-tab-active'))
        return false;

      var chosen = $(this).attr('id');

      show_chosen_tab(chosen);

      return false;
    });

    // Payment configuration options
    $('div#integration').on('click', '#mepr-add-integration', function() {
      show_integration_form();
      return false;
    });
    
    function show_integration_form() {
      var data = {
        action: 'mepr_gateway_form'
      };
      $.post(ajaxurl, data, function(response) {
        $(response).hide().appendTo('#integrations-list').slideDown('fast');
        $('.clippy').clippy({clippy_path: MeprOptions.jsUrl + '/clippy.swf', width: '14px'});
      });
    }
    
    $('div#integration').on('click', '.mepr-integration-delete a', function() {
      if(confirm(MeprOptions.confirmPMDelete)) {
        $(this).parent().parent().slideUp('fast', function() {
          $(this).remove();
        });
      }
      return false;
    });
    
    $('div#integration').on('change', 'select.mepr-gateways-dropdown', function() {
      var data_id = $(this).attr('data-id');
      var data = {
        action: 'mepr_gateway_form',
        g: $(this).val()
      };
      $.post(ajaxurl, data, function(response) {
        $('#mepr-integration-'+data_id).replaceWith(response);
        $('.clippy').clippy({clippy_path: MeprOptions.jsUrl + '/clippy.swf', width: '14px'});
      });
      return false;
    });
    
    //Custom Fields JS
    function get_new_line()
    {
      var random_id = Math.floor(Math.random() * 100000001); //easiest way to do this
      return  '<li class="mepr-custom-field postbox"> \
                <label>' + MeprOptions.nameLabel + '</label> \
                <input type="text" name="mepr-custom-fields[' + random_id + '][name]" /> \
                 \
                <label>' + MeprOptions.typeLabel + '</label> \
                <select name="mepr-custom-fields[' + random_id + '][type]" class="mepr-custom-fields-select" data-value="' + random_id + '"> \
                  <option value="text">' + MeprOptions.textOption + '</option> \
                  <option value="textarea">' + MeprOptions.textareaOption + '</option> \
                  <option value="checkbox">' + MeprOptions.checkboxOption + '</option> \
                  <option value="dropdown">' + MeprOptions.dropdownOption + '</option> \
                  <option value="date">' + MeprOptions.dateOption + '</option> \
                </select> \
                 \
                <label>' + MeprOptions.defaultLabel + '</label> \
                <input type="text" name="mepr-custom-fields[' + random_id + '][default]" /> \
                 \
                <input type="checkbox" name="mepr-custom-fields[' + random_id + '][signup]" id="mepr-custom-fields-signup-' + random_id + '" /> \
                <label for="mepr-custom-fields-signup-' + random_id + '">' + MeprOptions.signupLabel + '</label> \
                 \
                &nbsp;&nbsp;&nbsp;<input type="checkbox" name="mepr-custom-fields[' + random_id + '][required]" id="mepr-custom-fields-required-' + random_id + '" /> \
                <label for="mepr-custom-fields-required-' + random_id + '">' + MeprOptions.requiredLabel + '</label> \
                <input type="hidden" name="mepr-custom-fields-index[]" value="' + random_id + '" /> \
                 \
                <a href="" class="mepr-custom-field-remove"><i class="mp-icon mp-icon-cancel-circled mp-16"></i></a> \
                <div id="dropdown-hidden-options-' + random_id + '" style="display:none;"></div> \
                \
                <input type="hidden" name="mepr-custom-fields[' + random_id + '][slug]" value="mepr_none" />\
              </li>';
    }
    
    function get_initial_dropdown_options(my_id)
    {
      return '<ul class="custom_options_list"> \
                <li> \
                  <label>' + MeprOptions.optionNameLabel + '</label> \
                  <input type="text" name="mepr-custom-fields[' + my_id + '][option][]" /> \
                   \
                  <label>' + MeprOptions.optionValueLabel + '</label> \
                  <input type="text" name="mepr-custom-fields[' + my_id + '][value][]" /> \
                   \
                  <a href="" class="mepr-option-remove"><i class="mp-icon mp-icon-cancel-circled mp-16"></i></a> \
                </li> \
                <a href="" id="mepr-add-new-option" title="' + MeprOptions.addOptionLabel + '" data-value="' + my_id + '"><i class="mp-icon mp-icon-plus-circled mp-16"></i></a> \
              </ul>';
    }
    
    function get_new_option_line(my_id)
    {
      return '<li> \
                <label>' + MeprOptions.optionNameLabel + '</label> \
                <input type="text" name="mepr-custom-fields[' + my_id + '][option][]" /> \
                 \
                <label>' + MeprOptions.optionValueLabel + '</label> \
                <input type="text" name="mepr-custom-fields[' + my_id + '][value][]" /> \
                 \
                <a href="" class="mepr-option-remove"><i class="mp-icon mp-icon-cancel-circled mp-16"></i></a> \
              </li>';
    }

    // Mailchimp API stuff
    var mepr_check_mailchimp_apikey = function( apikey, wpnonce ) {
      if( apikey == '' ) { return; }

      var args = {
        action: 'mepr_ping_apikey',
        apikey: apikey,
        wpnonce: wpnonce
      };

      $.post( ajaxurl, args, function(res) {
        if( 'error' in res ) {
          $('#mepr-mailchimp-valid').hide();
          $('#mepr-mailchimp-invalid').html( res.error );
          $('#mepr-mailchimp-invalid').fadeIn();
          $('select#meprmailchimp_list_id').html('');
        }
        else {
          $('#mepr-mailchimp-invalid').hide();
          $('#mepr-mailchimp-valid').html( res.msg );
          $('#mepr-mailchimp-valid').fadeIn();
          mepr_load_mailchimp_lists_dropdown( 'select#meprmailchimp_list_id', apikey, wpnonce );
        }
      }, 'json' );
    }
    
    // GetResponse API stuff
    var mepr_check_getresponse_apikey = function( apikey, wpnonce ) {
      if( apikey == '' ) { return; }

      var args = {
        action: 'mepr_gr_ping_apikey',
        apikey: apikey,
        wpnonce: wpnonce
      }; 

      $.post( ajaxurl, args, function(res) {

        if( res == 'error' ) {
   
          $('#mepr-getresponse-valid').hide();
          $('#mepr-getresponse-invalid').html( 'Could not validate key.' );
          $('#mepr-getresponse-invalid').fadeIn();
          $('select#meprgetresponse_list_id').html('');
        }
        else {
          $('#mepr-getresponse-invalid').hide();
          $('#mepr-getresponse-valid').html( 'Ready!' );
          $('#mepr-getresponse-valid').fadeIn();
          mepr_load_getresponse_lists_dropdown( 'select#meprgetresponse_list_id', apikey, wpnonce );
        }
      });
    }

    $('a#mepr-add-new-custom-field').click(function() {
      $(this).before(get_new_line());
      return false;
    });

    $('body').on('click', 'a#mepr-add-new-option', function() {
      var my_id = $(this).attr('data-value');
      $(this).before(get_new_option_line(my_id));
      return false;
    });
    
    $('body').on('click', 'a.mepr-custom-field-remove', function() {
      $(this).parent().remove();
      return false;
    });
    $('body').on('click', 'a.mepr-option-remove', function() {
      $(this).parent().remove();
      return false;
    });
    
    $('body').on('change', 'select.mepr-custom-fields-select', function() {
      var my_id = $(this).attr('data-value');
      var type = $(this).val();
      
      if(type == 'dropdown') {
        $('div#dropdown-hidden-options-' + my_id).html(get_initial_dropdown_options(my_id));
        $('div#dropdown-hidden-options-' + my_id).show();
      } else {
        $('div#dropdown-hidden-options-' + my_id).html('');
        $('div#dropdown-hidden-options-' + my_id).hide();
      }
      
      return false;
    });
    
    //Terms of Service JS stuff
    if($('#mepr-require-tos').is(":checked")) {
      $('div#mepr_tos_hidden').show();
    } else {
      $('div#mepr_tos_hidden').hide();
    }
    $('#mepr-require-tos').click(function() {
      $('div#mepr_tos_hidden').slideToggle('fast');
    });
    
    //MailChimp enabled/disable checkbox
    if($('#meprmailchimp_enabled').is(":checked")) {
      mepr_check_mailchimp_apikey( $('#meprmailchimp_api_key').val(), MeprAweber.wpnonce );
      $('div#mailchimp_hidden_area').show();
    } else {
      $('div#mailchimp_hidden_area').hide();
    }
    $('#meprmailchimp_enabled').click(function() {
      if($('#meprmailchimp_enabled').is(":checked")) {
        mepr_check_mailchimp_apikey( $('#meprmailchimp_api_key').val(), MeprAweber.wpnonce );
      }
      $('div#mailchimp_hidden_area').slideToggle('fast');
    });

    var action = ($('#meprmailchimp_optin').is(":checked")?'show':'hide');
    $('#meprmailchimp-optin-text')[action]();
    $('#meprmailchimp_optin').click(function() {
      $('#meprmailchimp-optin-text')['slideToggle']('fast');
    });
    
    //GetResponse enabled/disable checkbox
    if($('#meprgetresponse_enabled').is(":checked")) {
      mepr_check_getresponse_apikey( $('#meprgetresponse_api_key').val(), MeprAweber.wpnonce );
      $('div#getresponse_hidden_area').show();
    } else {
      $('div#getresponse_hidden_area').hide();
    }
    $('#meprgetresponse_enabled').click(function() {
      if($('#meprgetresponse_enabled').is(":checked")) {
        mepr_check_getresponse_apikey( $('#meprgetresponse_api_key').val(), MeprAweber.wpnonce );
      }
      $('div#getresponse_hidden_area').slideToggle('fast');
    });

    var action = ($('#meprgetresponse_optin').is(":checked")?'show':'hide');
    $('#meprgetresponse-optin-text')[action]();
    $('#meprgetresponse_optin').click(function() {
      $('#meprgetresponse-optin-text')['slideToggle']('fast');
    });
    
    //AWeber enabled/disable checkbox
    if($('#mepraweber_enabled').is(":checked")) {
      $('div#aweber_hidden_area').show();
    } else {
      $('div#aweber_hidden_area').hide();
    }
    $('#mepraweber_enabled').click(function() {
      $('div#aweber_hidden_area').slideToggle('fast');
    });
    
    //Advanced AWeber enabled/disable checkbox
    action = ($('#mepr-adv-aweber-enabled').is(":checked")?'show':'hide');
    $('#mepr-adv-aweber-hidden-area')[action]();
    $('#mepr-adv-aweber-enabled').click(function() {
      $('#mepr-adv-aweber-hidden-area')['slideToggle']('fast');
    });

    action = ($('#mepr-adv-aweber-optin').is(":checked")?'show':'hide');
    $('#mepr-adv-aweber-optin-options')[action]();
    $('#mepr-adv-aweber-optin').click(function() {
      $('#mepr-adv-aweber-optin-options')['slideToggle']('fast');
    });

    //Unauthorized stuff
    if($('#mepr-redirect-on-unauthorized').is(':checked')) {
      $('#mepr-unauthorized-redirect').slideDown();
    } else {
      $('#mepr-unauthorized-redirect').slideUp();
    }
    
    $('#mepr-redirect-on-unauthorized').click(function() {
      if($('#mepr-redirect-on-unauthorized').is(':checked')) {
        $('#mepr-unauthorized-redirect').slideDown();
      } else {
        $('#mepr-unauthorized-redirect').slideUp();
      }
    });

    //Unauthorized excerpts type
    var toggle_excerpt_type = function() {
      if($('#mepr-unauth-show-excerpts').is(':checked')) {
        $('#mepr-unauthorized-show-excerpts-type').slideDown();
      } else {
        $('#mepr-unauthorized-show-excerpts-type').slideUp();
      }
    };
    toggle_excerpt_type();
    $('#mepr-unauth-show-excerpts').click(toggle_excerpt_type);

    //Unauthorized excerpt size
    var toggle_excerpt_size = function() {
      if($('#mepr-unauth-excerpt-type').val()=='custom') {
        $('#mepr-unauth-excerpt-type-size').slideDown();
      } else {
        $('#mepr-unauth-excerpt-type-size').slideUp();
      }
    };

    toggle_excerpt_size();
    $('#mepr-unauth-excerpt-type').change(toggle_excerpt_size);

    //Unauthorized message toggle
    $('.mp-toggle-unauthorized-message').click( function(e) {
      e.preventDefault();
      $('.mp-unauthorized-message').slideToggle();
    });

    // Button used to remove an item from the list
    $('#mepr-aweber-auth').click( function(e) {
      e.preventDefault();

      // Setup the arguments to be sent to our endpoint handler in AjexAdmin
      var args = {
        action: 'mepr_auth_aweber',
        auth_code: $('#mepr-aweber-api-code').val(),
        wpnonce: MeprAweber.wpnonce
      };

      $('#mepr-aweber-auth-loading').show();

      $.post( ajaxurl, args,
              function(res) {
                $('#mepr-aweber-auth-loading').hide();

                // Check to see if the action returned an error
                if( 'error' in res ) {
                  $('#mepr-aweber-message').hide();

                  // Display an error message
                  $('#mepr-aweber-error').html( res.error );
                  $('#mepr-aweber-error').fadeIn();

                  $('#aweber-auth-panel').show();
                  $('#aweber-deauth-panel').hide();
                }
                else {
                  $('#mepr-aweber-error').hide();

                  // Display a success message
                  $('#mepr-aweber-message').html( res.message );
                  $('#mepr-aweber-message').fadeIn();

                  mepr_load_aweber_list_dropdown('#mepr-adv-aweber-list', MeprAweber.wpnonce);

                  $('#aweber-auth-panel').hide();
                  $('#aweber-deauth-panel').show();
                }
              },
              'json' );
    });

    // Button used to remove an item from the list
    $('#mepr-aweber-deauth').click( function(e) {
      e.preventDefault();

      if( confirm( MeprAweber.deauth_aweber_message ) ) {
        // Setup the arguments to be sent to our endpoint handler in AjexAdmin
        var args = {
          action: 'mepr_deauth_aweber',
          wpnonce: MeprAweber.wpnonce
        };

        $('#mepr-aweber-deauth-loading').show();

        $.post( ajaxurl, args,
                function(res) {
                  $('#mepr-aweber-deauth-loading').hide();

                  // Check to see if the action returned an error
                  if( res !== null && 'error' in res ) {
                    $('#mepr-aweber-message').hide();

                    // Display an error message
                    $('#mepr-aweber-error').html( res.error );
                    $('#mepr-aweber-error').fadeIn();

                    $('#aweber-auth-panel').hide();
                    $('#aweber-deauth-panel').show();
                  }
                  else {
                    $('#mepr-aweber-error').hide();

                    // Display a success message
                    $('#mepr-aweber-message').html( res.message );
                    $('#mepr-aweber-message').fadeIn();

                    $('#aweber-auth-panel').show();
                    $('#aweber-deauth-panel').hide();
                  }
                },
                'json' );
      }
    });

    if( MeprAweber.authorized==1 ) {
      mepr_load_aweber_list_dropdown('#mepr-adv-aweber-list', MeprAweber.wpnonce);
    }

    // GetResponse Actions
    $('#meprgetresponse_api_key').blur( function(e) {
      mepr_check_getresponse_apikey( $(this).val(), MeprAweber.wpnonce );
    });

    if($('#meprmailchimp_enabled').is(':checked')) {
      mepr_check_mailchimp_apikey( $('#meprmailchimp_api_key').val(), MeprAweber.wpnonce );
    }

    // Mailchimp Actions
    $('#meprmailchimp_api_key').blur( function(e) {
      mepr_check_mailchimp_apikey( $(this).val(), MeprAweber.wpnonce );
    });

    //Clippy
    $('.clippy').clippy({clippy_path: MeprOptions.jsUrl + '/clippy.swf', width: '14px'});
    
    //Make who can purchase list sortable
    $(function() {
      $('ol#custom_profile_fields').sortable();
    });
  });
})(jQuery);
