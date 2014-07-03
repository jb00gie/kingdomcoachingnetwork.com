<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<style>
  .icon32 {
    float: right;
    display: inline-block;
    background:url('<?php echo MEPR_IMAGES_URL; ?>/memberpress-32.png') !important;
    width: 267px !important;
    height: 32px !important;
  }
</style>

<div class="wrap">
  <div class="icon32"></div>
  <h2><?php _e('MemberPress Quick Helps', 'memberpress'); ?></h2>
  <p><?php _e('Here are a few videos from our user manual on basic topics within MemberPress.', 'memberpress'); ?></p>
  <p><strong><?php printf(__('Check out our full %1$sUser Manual%2$s here!', 'memberpress'),'<a href="http://www.memberpress.com/user-manual/" target="_blank">','</a>'); ?></strong></p>

  <h3><?php _e('Setting Up Paypal', 'memberpress'); ?></h3>
  <iframe src="//www.youtube.com/embed/2KZAmkiuTVM" width="640" height="360" allowfullscreen="" frameborder="0"></iframe>

  <h3><?php _e('Setting Up Stripe', 'memberpress'); ?></h3>
  <iframe src="//www.youtube.com/embed/qXBWB1eCMPU" width="640" height="360" allowfullscreen="" frameborder="0"></iframe>

  <h3><?php _e('Setting Up Authorize.net', 'memberpress'); ?></h3>
  <iframe width="640" height="360" src="//www.youtube.com/embed/DqU9PN_i0ro" frameborder="0" allowfullscreen></iframe>

  <h3><?php _e('Options Overview', 'memberpress'); ?></h3>
  <iframe src="//www.youtube.com/embed/Hiu9baayIq0" width="640" height="360" allowfullscreen="" frameborder="0"></iframe>

  <h3><?php _e('Products', 'memberpress'); ?></h3>
  <iframe src="//www.youtube.com/embed/cO44Krz2hCQ" width="640" height="360" allowfullscreen="" frameborder="0"></iframe>

  <h3><?php _e('Rules', 'memberpress'); ?></h3>
  <iframe src="//www.youtube.com/embed/vvAvJsH97yw" width="640" height="360" allowfullscreen="" frameborder="0"></iframe>

  <h3><?php _e('Coupons', 'memberpress'); ?></h3>
  <iframe width="640" height="360" src="//www.youtube.com/embed/GFFTIplO9IM" frameborder="0" allowfullscreen></iframe>

  <h3><?php _e('Reports', 'memberpress'); ?></h3>
  <iframe width="640" height="360" src="//www.youtube.com/embed/6XYQnEyo92A" frameborder="0" allowfullscreen></iframe>
</div>
