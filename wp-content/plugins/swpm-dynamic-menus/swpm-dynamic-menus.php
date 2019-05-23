<?php

/**
 * Plugin Name: Simple Membership Dynamic Menus
 * Plugin URI: http://www.tipsandtricks-hq.com
 * Description: Allows you to set custom menu for logged in members and specific membership levels.
 * Version: 1.0
 * Author: Tips and Tricks HQ, alexanderfoxc
 * Author URI: http://www.tipsandtricks-hq.com/
 * Requires at least: 3.0
 */
class SWPM_DYNAMIC_MENUS {

    function __construct() {
	add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	add_filter( 'wp_nav_menu', array( $this, 'nav_menu_handler' ), 10, 2 );
    }

    function plugins_loaded() {
	if ( defined( 'SIMPLE_WP_MEMBERSHIP_VER' ) ) {
	    if ( is_admin() ) {
		include_once(dirname( __FILE__ ) . '/swpm-dynamic-menus-admin.php');
	    }
	}
    }

    function nav_menu_handler( $nav_menu, $args ) {
	if ( defined( 'SIMPLE_WP_MEMBERSHIP_VER' ) ) {
	    $auth = SwpmAuth::get_instance();
	    if ( $auth->is_logged_in() ) {
		$logged_in_menu		 = get_option( 'swpm_dm_logged_in_menu', true );
		$membership_level	 = $auth->get( 'membership_level' );
		$level_opts		 = get_option( 'swpm_dm_menu_level', true );
		if ( $membership_level && isset( $level_opts[ $membership_level ] ) && $level_opts[ $membership_level ] !== '0' ) {
		    $logged_in_menu = $level_opts[ $membership_level ];
		}
		remove_filter( 'wp_nav_menu', array( $this, 'nav_menu_handler' ) );
		$new_args		 = get_object_vars( $args );
		$new_args[ 'menu' ]	 = $logged_in_menu;
		$new_args[ 'echo' ]	 = false;
		$nav_menu		 = wp_nav_menu( $new_args );
	    }
	}
	return $nav_menu;
    }

}

new SWPM_DYNAMIC_MENUS();
