<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<div id="mepr-account-welcome-message"><?php echo apply_filters('mepr-account-welcome-message', do_shortcode($welcome_message), $mepr_current_user); ?></div>

<?php if( !empty($mepr_current_user->user_message) ): ?>
  <div id="mepr-account-user-message"><?php echo apply_filters('mepr-user-message', wpautop(do_shortcode($mepr_current_user->user_message)), $mepr_current_user); ?></div>
<?php endif; ?>

<?php
if($saved)
  echo '<div id="mepr-account-saved-message">'.__('Your account has been saved.', 'memberpress').'</div>';
?>

<form action="" method="post">
  <input type="hidden" name="mepr-process-account" value="Y" />
  <table id="mepr-account-table">
    <tr>
      <td class="mepr-account-info-label"><label for="user_first_name"><?php _e('First Name:', 'memberpress'); echo ($mepr_options->require_fname_lname)?'*':''; ?></label></td>
      <td class="mepr-account-info-input"><input type="text" id="user_first_name" name="user_first_name" value="<?php echo $mepr_current_user->first_name; ?>" /></td>
      <td class="mepr-account-change-password"><a href="<?php echo $account_url.$delim.'action=newpassword'; ?>"><?php _e('Change Password', 'memberpress'); ?></a></td>
    </tr>
    <tr>
      <td class="mepr-account-info-label"><label for="user_last_name"><?php _e('Last Name:', 'memberpress'); echo ($mepr_options->require_fname_lname)?'*':''; ?></label></td>
      <td class="mepr-account-info-input"><input type="text" id="user_last_name" name="user_last_name" value="<?php echo $mepr_current_user->last_name; ?>" /></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td class="mepr-account-info-label"><label for="user_email"><?php _e('Email:', 'memberpress');  ?>*</label></td>
      <td class="mepr-account-info-input"><input type="text" id="user_email" name="user_email" value="<?php echo $mepr_current_user->user_email; ?>" /></td>
      <td>&nbsp;</td>
    </tr>
    <?php
      $custom_fields = $mepr_options->custom_fields;
      
      if($mepr_options->show_address_fields)
        $custom_fields = array_merge($mepr_options->address_fields, $custom_fields); //Genious
      
      foreach($custom_fields as $line)
      {
        $required = ($line->required)?'*':'';
        $value = get_user_meta($mepr_current_user->ID, $line->field_key, true);
        
        if(empty($value))
          $value = stripslashes($line->default_value);
        
        ?>
        <tr>
          <td style="vertical-align:middle;">
            <?php //if($line->field_type != 'checkbox'): ?>
            <?php //we'll uncomment this if when we kill the tables on this page ?>
              <label for="<?php echo $line->field_key; ?>"><?php echo stripslashes($line->field_name).':'.$required; ?></label>
            <?php //endif; ?>
          </td>
          <td colspan="2">
          <?php
          
          switch($line->field_type)
          {
            case 'text':
              $value = (isset($_POST[$line->field_key]) && !empty($_POST[$line->field_key]))?stripslashes($_POST[$line->field_key]):$value;
              echo '<input type="text" name="'.$line->field_key.'" id="'.$line->field_key.'" value="'.$value.'" />';
              break;
            case 'textarea':
              $value = (isset($_POST[$line->field_key]) && !empty($_POST[$line->field_key]))?stripslashes($_POST[$line->field_key]):$value;
              echo '<textarea name="'.$line->field_key.'" id="'.$line->field_key.'">'.$value.'</textarea>';
              break;
            case 'checkbox':
              $checked = (isset($_POST[$line->field_key]) || $value)?true:(!isset($_POST['mepr-account-form']) && $line->default_value == 'checked');
              echo '<input type="checkbox" name="'.$line->field_key.'" id="'.$line->field_key.'" '.checked($checked, true, false).' />';
              break;
            case 'date':
              $value = (isset($_POST[$line->field_key]) && !empty($_POST[$line->field_key]))?stripslashes($_POST[$line->field_key]):$value;
              echo '<input type="text" name="'.$line->field_key.'" id="'.$line->field_key.'" value="'.$value.'" class="mepr-date-picker" />';
              break;
            case 'dropdown':
              $value = (isset($_POST[$line->field_key]))?$_POST[$line->field_key]:$value;
              ?>
              <select name="<?php echo $line->field_key; ?>" id="<?php echo $line->field_key; ?>">
              <?php
                foreach($line->options as $o)
                {
                ?>
                  <option value="<?php echo $o->option_value; ?>" <?php selected($o->option_value, $value); ?>><?php echo stripslashes($o->option_name); ?></option>
                <?php
                }
              ?>
              </select>
              <?php
              break;
          }
          
          ?>
          </td>
        </tr>
        <?php
      }
    ?>
    <?php do_action('mepr-account-home-fields', $mepr_current_user); ?>
    <tr>
      <td><input type="submit" name="mepr-account-form" value="<?php _e('Save Profile', 'memberpress'); ?>" class="mepr_front_button" /></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
  </table>
</form>

<p><small><?php _e('* Required field', 'memberpress'); ?></small></p>

<?php do_action('mepr_account_home', $mepr_current_user);
