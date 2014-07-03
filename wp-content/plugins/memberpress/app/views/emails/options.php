<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<p>
  <label for="<?php echo $this->field_name('enabled'); ?>">
    <input type="checkbox"
           name="<?php echo $this->field_name('enabled'); ?>"
           id="<?php echo $this->field_name('enabled', true); ?>"<?php checked($this->enabled()); ?>/>
    <?php printf(__('Send %s','memberpress'), $this->title); ?>
  </label>
  <?php MeprAppHelper::info_tooltip( $this->dashed_name(),
                                     $this->title,
                                     $this->description ); ?>
  <a href="#"
     class="mepr-edit-email-toggle button"
     data-id="edit-<?php echo $this->dashed_name(); ?>"
     data-edit-text="<?php _e('Edit', 'memberpress'); ?>"
     data-cancel-text="<?php _e('Hide Editor', 'memberpress'); ?>"><?php _e('Edit', 'memberpress'); ?></a>
  <a href="#"
     class="mepr-send-test-email button"
     data-obj-dashed-name="<?php echo $this->dashed_name(); ?>"
     data-obj-name="<?php echo get_class($this); ?>"
     data-subject-id="<?php echo $this->field_name('subject', true); ?>"
     data-use-template-id="<?php echo $this->field_name('use_template', true); ?>"
     data-body-id="<?php echo $this->field_name('body', true); ?>"><?php _e('Send Test', 'memberpress'); ?></a>
  <a href="#"
     class="mepr-reset-email button"
     data-obj-dashed-name="<?php echo $this->dashed_name(); ?>"
     data-subject-id="<?php echo $this->field_name('subject', true); ?>"
     data-body-obj="<?php echo get_class($this); ?>"
     data-use-template-id="<?php echo $this->field_name('use_template', true); ?>"
     data-body-id="<?php echo $this->field_name('body', true); ?>"><?php _e('Reset to Default', 'memberpress'); ?></a>
  <img src="<?php echo MEPR_IMAGES_URL . '/square-loader.gif'; ?>" alt="<?php _e('Loading...', 'memberpress'); ?>" id="mepr-loader-<?php echo $this->dashed_name(); ?>" class="mepr_loader" />
</p>
<div id="edit-<?php echo $this->dashed_name(); ?>" class="mepr-hidden mepr-options-pane mepr-edit-email">
  <ul>
    <li>
      <span class="mepr-field-label"><?php _e('Subject', 'memberpress'); ?></span><br/>
      <input class="form-field" type="text" id="<?php echo $this->field_name('subject', true); ?>" name="<?php echo $this->field_name('subject'); ?>" value="<?php echo $this->subject(); ?>" />
    </li>
    <li>
      <span class="mepr-field-label"><?php _e('Body', 'memberpress'); ?></span><br/>
      <?php wp_editor( $this->body(),
                       $this->field_name('body', true),
                       array( 'textarea_name' => $this->field_name('body') )
                     ); ?>
    </li>
    <li>
      <select id="var-<?php echo $this->dashed_name(); ?>">
        <?php foreach( $this->variables as $var ): ?>
          <option value="{$<?php echo $var; ?>}">{$<?php echo $var; ?>}</option>
        <?php endforeach; ?>
      </select>

      <a href="#" class="button mepr-insert-email-var" data-variable-id="var-<?php echo $this->dashed_name(); ?>"
         data-textarea-id="<?php echo $this->field_name('body', true); ?>"><?php _e('Insert &uarr;', 'memberpress'); ?></a>
    </li>
    <li>
      <br/>
      <input type="checkbox"
             name="<?php echo $this->field_name('use_template'); ?>"
             id="<?php echo $this->field_name('use_template', true); ?>"<?php checked($this->use_template()); ?>/>
      <span class="mepr-field-label">
        <?php _e('Use default template', 'memberpress'); ?>
        <?php MeprAppHelper::info_tooltip( $this->dashed_name() . '-template',
                                           __('Default Email Template', 'memberpress'),
                                           __('When this is checked the body of this email will be wrapped in the default email template.', 'memberpress') ); ?>
      </span>
    </li>
  </ul>
</div>

