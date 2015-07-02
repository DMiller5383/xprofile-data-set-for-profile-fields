<?php
/*
Plugin Name: BuddyPress xProfile Data Sets for Profile Fields
Plugin URI: http://www.buddypresser.com
Description: Allows a user to add a data sets to a dropdown, checkbox, or radio button (utilize value and text field).  Comes with Country and States data set.   
Tags: buddypress
Version: 1.0
Author: Daniel Miller

*/



function xp_dataset_enqueue_scripts() {

	wp_register_script( 'xp_dataset_admin', plugin_dir_url( __FILE__ ) . '/assets/js/xp_dataset_admin.js' );
	wp_localize_script('xp_dataset_admin', 'WPURLS', array( 'siteurl' => get_option('siteurl') )); 
    wp_enqueue_script( 'xp_dataset_admin' );
}

add_action( 'admin_enqueue_scripts', 'xp_dataset_enqueue_scripts' );

