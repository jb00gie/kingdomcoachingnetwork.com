<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>

<div class="wrap">
  <div class="icon32"></div>
  <h2><?php _e('New Transaction', 'memberpress'); ?></h2>

  <?php require(MEPR_VIEWS_PATH . "/shared/errors.php"); ?>
  
  <div class="form-wrap">
    <form action="" method="post">
      <table class="form-table">
        <tbody>
          <?php require(MEPR_VIEWS_PATH . "/transactions/trans_form.php"); ?>
        </tbody>
      </table>
      <p class="submit">
        <input type="submit" id="submit" class="button button-primary" value="<?php _e('Create', 'memberpress'); ?>" />
      </p>
    </form>
  </div>
</div>
