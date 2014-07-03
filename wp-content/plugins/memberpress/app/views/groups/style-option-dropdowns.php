<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<div id="group-option-dropdowns">
  <h4 id="group-label-for-dropdowns"><?php _e('Style & Layout:', 'memberpress'); ?></h4>
  <table>
    <tr>
      <td>
        <label for="<?php echo MeprGroup::$group_page_layout_str; ?>"><?php _e('Layout:', 'memberpress'); ?></label>
      </td>
      <td>
        <select name="<?php echo MeprGroup::$group_page_layout_str; ?>" class="group-style-select">
          <option value="mepr-horizontal" <?php selected($group->group_page_style_options['layout'], 'mepr-horizontal'); ?>>
            <?php _e('Horizontal', 'memberpress'); ?>
          </option>
          <option value="mepr-vertical" <?php selected($group->group_page_style_options['layout'], 'mepr-vertical'); ?>>
            <?php _e('Vertical', 'memberpress'); ?>
          </option>
          <option value="custom" <?php selected($group->group_page_style_options['layout'], 'custom'); ?>>
            <?php _e('Custom', 'memberpress'); ?>
          </option>
        </select>
      </td>
      <td>
        <label for="<?php echo MeprGroup::$group_page_font_style_str; ?>"><?php _e('Font Style:', 'memberpress'); ?></label>
      </td>
      <td>
        <select name="<?php echo MeprGroup::$group_page_font_style_str; ?>" class="group-style-select">
          <option value="custom" <?php selected($group->group_page_style_options['font_style'], 'custom'); ?>>
            <?php _e('Site Default', 'memberpress'); ?>
          </option>
          <option value="mepr-arial" <?php selected($group->group_page_style_options['font_style'], 'mepr-arial'); ?>>
            <?php _e('Arial', 'memberpress'); ?>
          </option>
          <option value="mepr-times" <?php selected($group->group_page_style_options['font_style'], 'mepr-times'); ?>>
            <?php _e('Times', 'memberpress'); ?>
          </option>
          <option value="mepr-verdana" <?php selected($group->group_page_style_options['font_style'], 'mepr-verdana'); ?>>
            <?php _e('Verdana', 'memberpress'); ?>
          </option>
        </select>
      </td>
    </tr>
    
    <tr>
      <td>
        <label for="<?php echo MeprGroup::$group_page_style_str; ?>"><?php _e('Style:', 'memberpress'); ?></label>
      </td>
      <td>
        <select name="<?php echo MeprGroup::$group_page_style_str; ?>" class="group-style-select">
          <option value="mepr-gray" <?php selected($group->group_page_style_options['style'], 'mepr-gray'); ?>>
            <?php _e('Gray (default)', 'memberpress'); ?>
          </option>
          <option value="mepr-orange" <?php selected($group->group_page_style_options['style'], 'mepr-orange'); ?>>
            <?php _e('Orange', 'memberpress'); ?>
          </option>
          <option value="mepr-blue" <?php selected($group->group_page_style_options['style'], 'mepr-blue'); ?>>
            <?php _e('Blue', 'memberpress'); ?>
          </option>
          <option value="mepr-red" <?php selected($group->group_page_style_options['style'], 'mepr-red'); ?>>
            <?php _e('Red', 'memberpress'); ?>
          </option>
          <option value="mepr-green" <?php selected($group->group_page_style_options['style'], 'mepr-green'); ?>>
            <?php _e('Green', 'memberpress'); ?>
          </option>
        </select>
      </td>
      <td>
        <label for="<?php echo MeprGroup::$group_page_font_size_str; ?>"><?php _e('Font Size:', 'memberpress'); ?></label>
      </td>
      <td>
        <select name="<?php echo MeprGroup::$group_page_font_size_str; ?>" class="group-style-select">
          <option value="custom" <?php selected($group->group_page_style_options['font_size'], 'custom'); ?>>
            <?php _e('Site Default', 'memberpress'); ?>
          </option>
          <option value="mepr-small" <?php selected($group->group_page_style_options['font_size'], 'mepr-small'); ?>>
            <?php _e('Small', 'memberpress'); ?>
          </option>
          <option value="mepr-medium" <?php selected($group->group_page_style_options['font_size'], 'mepr-medium'); ?>>
            <?php _e('Medium', 'memberpress'); ?>
          </option>
          <option value="mepr-large" <?php selected($group->group_page_style_options['font_size'], 'mepr-large'); ?>>
            <?php _e('Large', 'memberpress'); ?>
          </option>
        </select>
      </td>
    </tr>
    
    <tr>
      <td>
        <label for="<?php echo MeprGroup::$group_page_button_size_str; ?>"><?php _e('Button Size:', 'memberpress'); ?></label>
      </td>
      <td>
        <select name="<?php echo MeprGroup::$group_page_button_size_str; ?>" class="group-style-select">
          <option value="mepr-medium" <?php selected($group->group_page_style_options['button_size'], 'mepr-meium'); ?>>
            <?php _e('Medium', 'memberpress'); ?>
          </option>
          <option value="mepr-large" <?php selected($group->group_page_style_options['button_size'], 'mepr-large'); ?>>
            <?php _e('Large', 'memberpress'); ?>
          </option>
          <option value="mepr-small" <?php selected($group->group_page_style_options['button_size'], 'mepr-small'); ?>>
            <?php _e('Small', 'memberpress'); ?>
          </option>
        </select>
      </td>
      <td>
        <label for="<?php echo MeprGroup::$group_page_button_color_str; ?>"><?php _e('Button Color:', 'memberpress'); ?></label>
      </td>
      <td>
        <select name="<?php echo MeprGroup::$group_page_button_color_str; ?>" class="group-style-select">
          <option value="mepr-button-gray" <?php selected($group->group_page_style_options['button_color'], 'mepr-button-gray'); ?>>
            <?php _e('Gray (default)', 'memberpress'); ?>
          </option>
          <option value="mepr-button-orange" <?php selected($group->group_page_style_options['button_color'], 'mepr-button-orange'); ?>>
            <?php _e('Orange', 'memberpress'); ?>
          </option>
          <option value="mepr-button-blue" <?php selected($group->group_page_style_options['button_color'], 'mepr-button-blue'); ?>>
            <?php _e('Blue', 'memberpress'); ?>
          </option>
          <option value="mepr-button-red" <?php selected($group->group_page_style_options['button_color'], 'mepr-button-red'); ?>>
            <?php _e('Red', 'memberpress'); ?>
          </option>
          <option value="mepr-button-green" <?php selected($group->group_page_style_options['button_color'], 'mepr-button-green'); ?>>
            <?php _e('Green', 'memberpress'); ?>
          </option>
        </select>
      </td>
    </tr>
    
    <tr>
      <td>
        <label for="<?php echo MeprGroup::$group_page_bullet_style_str; ?>"><?php _e('Bullet Style:', 'memberpress'); ?></label>
      </td>
      <td colspan="3">
        <select name="<?php echo MeprGroup::$group_page_bullet_style_str; ?>" class="group-style-select">
          <option value="mepr-circles" <?php selected($group->group_page_style_options['bullet_style'], 'mepr-circles'); ?>>
            <?php _e('Circles', 'memberpress'); ?>
          </option>
          <option value="mepr-discs" <?php selected($group->group_page_style_options['bullet_style'], 'mepr-discs'); ?>>
            <?php _e('Discs', 'memberpress'); ?>
          </option>
          <option value="mepr-squares" <?php selected($group->group_page_style_options['bullet_style'], 'mepr-squares'); ?>>
            <?php _e('Squares', 'memberpress'); ?>
          </option>
          <option value="mepr-checkmarks" <?php selected($group->group_page_style_options['bullet_style'], 'mepr-checkmarks'); ?>>
            <?php _e('Check Marks', 'memberpress'); ?>
          </option>
        </select>
      </td>
    </tr>
  </table>
</div>
