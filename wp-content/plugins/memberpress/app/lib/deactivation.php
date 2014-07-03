<?php
if(!defined('ABSPATH')) { die('You are not allowed to call this page directly.'); }

remove_action('mod_rewrite_rules', 'MeprRulesController::mod_rewrite_rules');

MeprUtils::flush_rewrite_rules();

