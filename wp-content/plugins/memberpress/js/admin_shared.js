(function($) {
  $(document).ready(function() {
    // Login form shortcode
    if($('#_mepr_manual_login_form').is(":checked")) {
      $('div#mepr-shortcode-login-page-area').show();
    } else {
      $('div#mepr-shortcode-login-page-area').hide();
    }
    
    $('#_mepr_manual_login_form').click(function() {
      $('div#mepr-shortcode-login-page-area').slideToggle();
    });
    
    // Unauthorized stuff
    var unauth_tgl_ids = {
      excerpt: {
        src: '_mepr_unauth_excerpt_type',
        target: '_mepr_unauth_excerpt_type-size'
      },
      message: {
        src: '_mepr_unauthorized_message_type',
        target: '_mepr_unauthorized_message_type-editor'
      }
    };
                                      
    var unauth_tgl = function(src,target) {
      if($('#'+src).val()=='custom')
        $('#'+target).slideDown();
      else
        $('#'+target).slideUp();
    };

    unauth_tgl(unauth_tgl_ids.excerpt.src,unauth_tgl_ids.excerpt.target);
    $('#'+unauth_tgl_ids.excerpt.src).change( function() {
      unauth_tgl(unauth_tgl_ids.excerpt.src,unauth_tgl_ids.excerpt.target);
    });

    unauth_tgl(unauth_tgl_ids.message.src,unauth_tgl_ids.message.target);
    $('#'+unauth_tgl_ids.message.src).change( function() {
      unauth_tgl(unauth_tgl_ids.message.src,unauth_tgl_ids.message.target);
    });

    $('table.wp-list-table tr').hover(
      function(e) {
        $(this).find('.mepr-row-actions').css('visibility','visible');
      },
      function(e) {
        $(this).find('.mepr-row-actions').css('visibility','hidden');
      }
    ); 

    $( '.mepr-auto-trim' ).blur( function(e) {
      var value = $(this).val();
      $(this).val( value.trim() );
    });

    $('.mepr-slide-toggle').click( function(e) {
      e.preventDefault();
      $($(this).attr('data-target')).slideToggle();
    });
    
    //Change mouse pointer over li items
    $('body').on('mouseenter', '.mepr-sortable li', function() {
      $(this).addClass('mepr-hover');
    });
    $('body').on('mouseleave', '.mepr-sortable li', function() {
      $(this).removeClass('mepr-hover');
    });
  });
})(jQuery);

// Required for Mailchimp integration
function mepr_load_mailchimp_lists_dropdown( id, apikey, wpnonce ) {
  (function($) {
    if( apikey == '' ) { return; }

    var list_id = $(id).data('listid');

    var args = {
      action: 'mepr_get_lists',
      apikey: apikey,
      wpnonce: wpnonce
    };

    $.post( ajaxurl, args, function(res) {
      if( res.total > 0 ) {
        var options = '';
        var selected = '';

        $.each( res.data, function( index, list ) {
          selected = ( ( list_id == list.id ) ? ' selected' : '' );
          options += '<option value="' + list.id + '"' + selected + '>' + list.name + '</option>';
        });

        $(id).html(options);
      }
    }, 'json' );
  })(jQuery);
}

// Required for AWeber integration
function mepr_load_aweber_list_dropdown( id, wpnonce ) {
  (function($) {
    var args = {
      action: 'mepr_get_aweber_lists',
      wpnonce: wpnonce
    };

    var list_id = $(id).data('listid');

    $(id+'-loading').show();

    $.post( ajaxurl, args,
            function(res) {
              $(id+'-loading').hide();

              // Check to see if the action returned an error
              if( 'error' in res ) {
                // Do nothing
              }
              else {
                var options = '';
                var selected = '';

                $.each( res.lists, function( index, value ) {
                  selected = ( ( list_id == index ) ? ' selected' : '' );
                  options += '<option value="' + index + '"' + selected + '>' + value + '</option>';
                });

                $(id).html(options);
              }
            },
            'json' );
  })(jQuery);
}

// Required for GetResponse integration
function mepr_load_getresponse_lists_dropdown( id, apikey, wpnonce ) {

  (function($) {
    if( apikey == '' ) { return; }

    var list_id = $(id).data('listid');

    var args = {
      action: 'mepr_get_campaigns',
      apikey: apikey,
      wpnonce: wpnonce
    };

    $.post( ajaxurl, args, function(res) {
      if( res.total > 0 ) {
        var options = '';
        var selected = '';

        $.each( res.data, function( index, list ) {
          selected = ( ( list_id == list.list_id ) ? ' selected' : '' );
          options += '<option value="' + list.list_id + '"' + selected + '>' + list.list_name + '</option>';
        });

        $(id).html(options);
      }
    }, 'json' );
  })(jQuery);
}

