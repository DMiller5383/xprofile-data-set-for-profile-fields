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
	$datasets_dropdown = xp_dataset_build_dataset_dropdown();
	wp_localize_script('xp_dataset_admin', 'xp_datasets', array( 'xp_dataset_dropdown' => $datasets_dropdown )); 
    wp_enqueue_script( 'xp_dataset_admin' );
}

add_action( 'admin_enqueue_scripts', 'xp_dataset_enqueue_scripts' );

add_action( 'init', 'xp_dataset_create_dataset_post_type' );

function xp_dataset_create_dataset_post_type() {
  register_post_type( 'xp_dataset_dataset',
    array(
      'labels' => array(
        'name' => __( 'XProfile Data Sets' ),
        'singular_name' => __( 'XProfile Data Set' )
      ),
      'public' => true,
      'has_archive' => true,
      'supports' => array('title')
    )
  );
}

add_action( 'add_meta_boxes', 'xp_dataset_add_meta_box' );

function xp_dataset_add_meta_box() {

	add_meta_box('dataid', 'Data', 'xp_dataset_data_callback_func', 'xp_dataset_dataset', 'normal');

}

function xp_dataset_data_callback_func() {

	wp_nonce_field( 'xp_dataset_metabox', 'xp_dataset_nonce' );
	$meta_box = '<div id = "xp_dataset_wrapper">';
	$xp_dataset = get_post_meta( get_the_ID(), 'xp_dataset', true);

	if ($xp_dataset) {

		foreach( $xp_dataset as $key => $dataset ) {

			$meta_box .= xp_dataset_admin_values_html( $dataset['value'], $dataset['text'] );
		}	

	} else {
		
		$meta_box .= xp_dataset_admin_values_html();

	}

	$meta_box .= '</div><div id = "xp_dataset_add_item"><a href = "#">Add Item</a></div>';

	echo $meta_box;

}


function xp_dataset_admin_values_html( $value = '', $text = '' ) {
	
	ob_start();

		?>
		<div id = "xp_dataset_input_wrapper-0" class = "xp_dataset_input_wrapper">
			<label for="xp_dataset_value-0">
			<?= _e( 'Value', 'myplugin_textdomain' ); ?>
			</label> 
			<input type="text" id="xp_dataset_value-0" name="xp_dataset_value[]" value="<?= $value ?>" />

			<label for="xp_dataset_text[]">
			<?= _e( 'Text', 'myplugin_textdomain' ); ?>
			</label> 
			<input type="text" id="xp_dataset_text-0" name="xp_dataset_text[]" value="<?= $text ?>" />
			<a href = "#" id = "xp_dataset_remove_item-0" class="xp_dataset_remove_item">Remove Item</a>
		</div>

		
			
		


		<?php

		$meta_box = ob_get_clean();

		return $meta_box;


}


/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function xp_dataset_save_meta_box_data( $post_id ) {

	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */

	// Check if our nonce is set.


	if ( ! isset( $_POST['xp_dataset_nonce'] ) ) {

		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['xp_dataset_nonce'], 'xp_dataset_metabox' ) ) {
		
		
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'xp_dataset_dataset' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}



	/* OK, it's safe for us to save the data now. */
	// Make sure that it is set.
	$xp_dataset_values = $_POST['xp_dataset_value'];
	$xp_dataset_texts = $_POST['xp_dataset_text'];

	$xp_datasets = array();

	foreach( $xp_dataset_values as $key => $value ) {

		if( $value ) {

			$value = sanitize_text_field( $value );
			if ( $xp_dataset_texts[$key] ) {

				$text = sanitize_text_field( $xp_dataset_texts[$key] );
				
				array_push( $xp_datasets, array( 'value' => $value, 'text' => $text) );
			}

		}
	} 


	update_post_meta( $post_id, 'xp_dataset', $xp_datasets );

}
add_action( 'save_post', 'xp_dataset_save_meta_box_data' );

function xp_dataset_build_dataset_dropdown() {

	$args = array( 'post_type' => 'xp_dataset_dataset' );

	$xp_dataset_query = new WP_Query( $args );

	$xp_dataset_dropdown = array();
	if ( $xp_dataset_query->have_posts() ) {

		while ( $xp_dataset_query->have_posts() ) {
			$xp_dataset_query->the_post();

			$dataset_id = get_the_ID();
			$dataset_title = get_the_title();
			
			$xp_dataset_dropdown[$dataset_id] = $dataset_title;

		}

	} else {
	
	}
	/* Restore original Post Data */
	wp_reset_postdata();

	return $xp_dataset_dropdown;

}
