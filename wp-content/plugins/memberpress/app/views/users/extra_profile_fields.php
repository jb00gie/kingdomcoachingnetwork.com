<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<h3><?php _e('Membership Information', 'memberpress'); ?></h3>

<table class="form-table">
  <tbody>
  <?php
    $custom_fields = $mepr_options->custom_fields;
    
    if($mepr_options->show_address_fields)
      $custom_fields = array_merge($mepr_options->address_fields, $custom_fields); //Genius
    
    if(!empty($custom_fields))
      foreach($custom_fields as $line)
      {
        $value = get_user_meta($user->ID, $line->field_key, true);
        $required = ($line->required)?'<span class="description">'.__('(required)', 'memberpress').'</span>':'';
        
        if(empty($value))
          $value = $line->default_value;
        
        ?>
        <tr>
          <th>
            <label for="<?php echo $line->field_key; ?>"><?php echo stripslashes($line->field_name).' '.$required; ?></label>
          </th>
          <td>
          <?php
          
          switch($line->field_type)
          {
            case 'text':
              $value = (isset($_POST[$line->field_key]) && !empty($_POST[$line->field_key]))?stripslashes($_POST[$line->field_key]):$value;
              echo '<input type="text" name="'.$line->field_key.'" id="'.$line->field_key.'" class="regular-text" value="'.$value.'" />';
              break;
            case 'textarea':
              $value = (isset($_POST[$line->field_key]) && !empty($_POST[$line->field_key]))?stripslashes($_POST[$line->field_key]):$value;
              echo '<textarea name="'.$line->field_key.'" id="'.$line->field_key.'">'.$value.'</textarea>';
              break;
            case 'checkbox':
              $checked = (isset($_POST[$line->field_key]) || $value)?true:(!isset($_POST['submit']) && $line->default_value == 'checked');
              echo '<input type="checkbox" name="'.$line->field_key.'" id="'.$line->field_key.'" '.checked($checked, true, false).' />';
              break;
            case 'date':
              $value = (isset($_POST[$line->field_key]) && !empty($_POST[$line->field_key]))?stripslashes($_POST[$line->field_key]):$value;
              echo '<input type="text" name="'.$line->field_key.'" id="'.$line->field_key.'" value="'.$value.'" class="mepr-date-picker regular-text" />';
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
    
    if(is_super_admin()) //Super admin works on both MU and Single site
    {
    ?>
      <tr>
        <td colspan="2">
          <a href="<?php echo admin_url('admin.php?page=memberpress-trans&member='.$user->user_login); ?>" class="button"><?php _e("View Member's Transactions", "memberpress");?></a>
        </td>
      </tr>
      <tr>
        <td>
          <a href="<?php echo admin_url('admin.php?page=memberpress-subscriptions&member='.$user->user_login); ?>" class="button"><?php _e("View Member's Subscriptions", "memberpress");?></a>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <a class="button mepr-resend-welcome-email" href="#" user-id="<?php echo $user->ID; ?>" mepr-nonce="<?php echo wp_create_nonce('mepr-resend-welcome-email'); ?>"><?php _e('Resend MemberPress Welcome Email', 'memberpress'); ?></a>&nbsp;&nbsp;<img src="<?php echo admin_url('images/loading.gif'); ?>" alt="<?php _e('Loading...', 'memberpress'); ?>" class="mepr-resend-welcome-email-loader" />&nbsp;&nbsp;<span class="mepr-resend-welcome-email-message">&nbsp;</span>
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <h4><?php _e('Custom MemberPress Account Message', 'memberpress'); ?></h4>
          <?php wp_editor($user->user_message, MeprUser::$user_message_str); ?>
        </td>
      </tr>
    <?php
    }
  ?>
  </tbody>
</table>
