<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

abstract class MeprCptController extends MeprBaseController
{
  public function __construct() {
    add_action('init', array( $this, 'register_post_type' ), 0);
    parent::__construct();
  }

  abstract public function register_post_type();
}

