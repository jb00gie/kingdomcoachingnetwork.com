<?php
/*
Plugin Name: JW Forced Login
Description: This simple plugin forces users to login to view the site.  If not logged in it takes you to the login page.  Unfortunately this doesn't work for a multisite install.
Version: 1.0
Author: Jaffe
Author URI: http://jaff.es
License: A "Slug" license name e.g. GPL2
/*  Copyright 2013  Jaffe  (email : jaffe75@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Hide site unless you're logged in

function fm_reroute_user(){
	if (!is_user_logged_in() && strpos($_SERVER['REQUEST_URI'], 'wp-login.php') === false) {
		header('Location: ' . get_bloginfo('url') . '/wp-login.php');
		exit;
	}
}

add_action('init', 'fm_reroute_user');

?>