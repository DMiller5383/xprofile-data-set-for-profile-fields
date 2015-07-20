<?php
/*
Plugin Name: BuddyPress xProfile Data Sets for Profile Fields
Plugin URI: http://www.buddypresser.com
Description: Allows a user to add a data sets to a dropdown, checkbox, or radio button (utilize value and text field).  Comes with Country and States data set.   
Tags: buddypress
Version: 1.0
Author: Daniel Miller

*/


/**
*
* Enqueues necessary script file for plugin and localizes various variables.
* 
* Enqueue xp_dataset admin script.  This script will also localize variables for use in javascript file.  
* Creates an array called xp_datasets which has three parameters:
*   xp_dataset_dropdown: contains an array of all xp_dataset post type with the id and title.
*   dataset: The xp_dataset that the active field is using.
*   field_type: The type of the field being edited. 
**/

function xp_dataset_enqueue_scripts() {
	global $post;

	//Get the post.  After running xp_dataset_build dataset dropdown, global variable $post will incorrect.
	$current_post = $post;
	wp_register_script( 'xp_dataset_admin', plugin_dir_url( __FILE__ ) . '/assets/js/xp_dataset_admin.js' );
	$datasets_dropdown = xp_dataset_build_dataset_dropdown();

	//reset global post variable to was it was before running query in xp_dataset_build_dataset_dropdown.
	$post = $current_post;

	$field_id = $_GET['field_id'];
	$dataset = get_xp_dataset( $field_id );

	$field = xprofile_get_field( $field_id );

	$field_type = $field->type;
	wp_localize_script('xp_dataset_admin', 'xp_datasets', array( 'xp_dataset_dropdown' => $datasets_dropdown, 'dataset' => $dataset, 'field_type' => $field_type )); 
    wp_enqueue_script( 'xp_dataset_admin' );

}

add_action( 'admin_enqueue_scripts', 'xp_dataset_enqueue_scripts', 20 );

add_action( 'init', 'xp_dataset_create_dataset_post_type' );

/**
*
* Creates xp_dataset post type.
*
**/


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

/**
*
* Adds meta-box to for dataset post type.
*
**/

function xp_dataset_add_meta_box() {

	add_meta_box('dataid', 'Data', 'xp_dataset_data_callback_func', 'xp_dataset_dataset', 'normal');

}

/**
*
* Responsible for rendering all input fields within the xp_dataset metabox.
* 
**/

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

/**
*
* Render html required to create the xp_data inputs for the xp_dataset metabox.
*
* @param string $value Optional		Value field of current item in dataset post meta being iterated through.
* @param string $text  Optional 	Text field of current item in dataset post meta being iterated through.
*
* @return $metabox string 	HTML outputs for inputs in meta box.
**/

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
 * When the xp_dataset is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 *
 * @return $datasets_meta bool OR int returns the meta value if new post.  Return true if meta successfully updated.
 */
function xp_dataset_save_meta_box_data( $post_id ) {


	if ( ! isset( $_POST['xp_dataset_nonce'] ) ) {

		return;
	}

	if ( ! wp_verify_nonce( $_POST['xp_dataset_nonce'], 'xp_dataset_metabox' ) ) {
		
		
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $_POST['post_type'] ) && 'xp_dataset_dataset' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}


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


	$datasets_meta = update_post_meta( $post_id, 'xp_dataset', $xp_datasets );

	return $datasets_meta;

}
add_action( 'save_post', 'xp_dataset_save_meta_box_data' );

/**
*
* Build array of xp_datasets to be localized.
* Array is structured to contain the dataset id as the item key and dataset title as the item value.
* 
* @return array $xp_dataset_dropdown List of datasets ( dataset_id => dataset_title)
**/

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

	}
	/* Restore original Post Data */

	wp_reset_query();
	return $xp_dataset_dropdown;

}

add_action( 'xprofile_field_after_save', 'xp_dataset_save_field');

/**
*
* Saves the dataset id into the database for the specified field.
* 
* Saves the id of the xp_dataset post type being used by profile in xp_dataset field in xprofile_data table (if applicable).
*
* @param object $field 		xprofile field that is being saved.
* @global object $wpdb		WordPress database object.
* @global object $bp 		BuddyPress object
*
**/

function xp_dataset_save_field( $field ) {

		global $wpdb, $bp;

		$field_type = $field->type;
		$field_id = $field->id;

		if ($_POST['xp-dataset-enable_' . $field_type] == 'enabled') {

			if ( ! $field->id ) {
				
				$field_id = xprofile_get_field_id_from_name( $field->name );

			}
			
			
			$xp_dataset = get_xp_dataset( $field_id );
			$dataset_id = $_POST['xp-dataset-select_' . $field_type];
			
			if($dataset_id) {

				set_xp_dataset( $field_id, $dataset_id);

			} else {

				set_xp_dataset($field_id, '');
			}
			
			

		} else {

			set_xp_dataset($field_id, '');
		}

}

add_filter( 'bp_get_the_profile_field_options_multiselect' ,'xp_dataset_render_dataset_options', 10 , 5 );
add_filter( 'bp_get_the_profile_field_options_checkbox' ,'xp_dataset_render_dataset_options', 10 , 5 );
add_filter( 'bp_get_the_profile_field_options_radio' ,'xp_dataset_render_dataset_options', 10 , 5 );
add_filter( 'bp_get_the_profile_field_options_select' ,'xp_dataset_render_dataset_options', 10 , 5 );

/**
*
* Renders options from dataset if profile field is using a dataset.
*
* Calls function to render option html based on name of the field - ie. xp_dataset_checkbox_option_html
*
* @param string $value HTML tag for option being rendered.
* @param object $object Current option being rendered for.
* @param int $id Id of field object.
* @param string $selected Current selected value.
* @param int $k current Index of the foreach loop.
*
* @global object $bp BuddyPress object.
*
* @return string $value option value or list of options (if dataset) to be rendered.
*
**/

function xp_dataset_render_dataset_options( $value, $object, $id, $selected, $k ) {

	$dataset = get_xp_dataset( $id );
	$options = '';

	$field_type = bp_get_the_profile_field_type();

	if ( !empty( $dataset ) ) {

		global $bp;
		$user_id = $bp->displayed_user->id;
		$user_value = xprofile_get_field_data( $id, $user_id );
		$value = '';

		if ($k == 0) {
			
			$dataset = get_post_meta($dataset, 'xp_dataset', true );

			foreach ($dataset as $data ) {

				$function_name = 'xp_dataset_' . $field_type . '_option_html';
				$args = array( 'object' => $object,  'value' => $data['value'], 'text' => $data['text'], 'field_id' => $id, 'option_id' => $object->id, 'user_values' => $user_value );
				
			
				$value .= call_user_func( $function_name, $args);

			}
			
		}

		
	}

	return $value;

}

/**
*
* Creates xp_dataset column in bp_xprofile_fields table (if it does not already exist) when plugin activates.
*
* @global object $wpdb WordPress database object.
* @global object $bp BuddyPress object.
*
* @return bool $wpdb->query( $sql ) Return true if query is successful.
*
**/

function xp_dataset_install() {

	global $wpdb, $bp;

	$sql = "ALTER TABLE {$bp->profile->table_name_fields} ADD xp_dataset varchar(50)";
	
	return $wpdb->query( $sql );
}

register_activation_hook( __FILE__, 'xp_dataset_install' );



//helper functions

/**
*
* Retrieves a dataset based on field id.
* 
* @param int $field_id Id of field to retrieve dataset from.
*
* @global object $wpdb WordPress database object.
* @global object $bp BuddyPress object.
*
* @return $xp_dataset int Id of xp_dataset post type.
*
**/

function get_xp_dataset( $field_id ) {

	global $wpdb, $bp;

	$sql = $wpdb->prepare("SELECT xp_dataset FROM {$bp->profile->table_name_fields} WHERE id=%s", $field_id);

	$result = $wpdb->get_results( $sql );
	
	$xp_dataset = $result[0]->xp_dataset;

	return $xp_dataset;

}

/**
*
* Sets the value of xp_dataset for a given field.
* 
* @param int $field_id Id of field set dataset.
* @param int $dataset_id Id of dataset to use for fields dataset.
*
* @global object $wpdb WordPress database object.
* @global object $bp BuddyPress object.
*
* @return bool $result 1 if successful and 0 if query failed.
*
**/

function set_xp_dataset( $field_id, $dataset_id ) {

	global $wpdb, $bp;

	$sql  = $wpdb->prepare( "UPDATE {$bp->profile->table_name_fields} SET xp_dataset=%s WHERE id=%s", $dataset_id, $field_id ); 

	$result = $wpdb->query( $sql );

	return $result;

}

/**
*
* Renders the HTML for a checkbox option.
*
* @param array $args Arguments passed in from xp_render_dataset_options function necessary for HTML rendering.
*
* @return string $option HTML to render for checkbox option.
*
**/

function xp_dataset_checkbox_option_html( $args ) {

	$checked = in_array( $args['value'], $args['user_values'] ) ? 'checked' : '';
	
	$option = '<label><input type="checkbox" name = "field_' . $args['object']->parent_id . '[]" value="'. $args['value'] .'" ' . $checked . '>' . $args['text'] . '</label>';

	return $option;
}

/**
*
* Renders the HTML for a selectbox option.
*
* @param array $args Arguments passed in from xp_render_dataset_options function necessary for HTML rendering.
*
* @return string $option HTML to render for selectbox option.
*
**/

function xp_dataset_selectbox_option_html( $args ) {

	$selected = $args['value'] == $args['user_values'] ? 'selected' : '';
	
	$option = '<option value="' . $args['value'] . '" ' . $selected . '>'. $args['text'] .'</option>';

	return $option;
}

/**
*
* Renders the HTML for a multiselect box option.
*
* @param array $args Arguments passed in from xp_render_dataset_options function necessary for HTML rendering.
*
* @return string $option HTML to render for multiselect box option.
*
**/

function xp_dataset_multiselectbox_option_html( $args ) {

	$selected = in_array( $args['value'], $args['user_values'] ) ? 'selected' : '';


	$option = '<option value="' . $args['value'] . '" ' . $selected .  '>'. $args['text'] .'</option>';

	return $option;

}

/**
*
* Renders the HTML for a radio button option.
*
* @param array $args Arguments passed in from xp_render_dataset_options function necessary for HTML rendering.
*
* @return string $option HTML to render for radio button option.
*
**/

function xp_dataset_radio_option_html( $args ) {

	$option = '<label><input type="radio" name="field_'. $args['field_id'] . '" id="option_' . $args['option_id'] . ' value="' . $args['value'] . '>' . $args['text'] .'</label>';

	return $option;
}


add_filter('bp_xprofile_set_field_data_pre_validate', 'xp_dataset_create_global_field', 10, 3);

/**
*
* Creates global field for use in xp_dataset_validate options
*
* @global $xp_dataset_field object 
*
* @return string $value value option value submitted if multi option is available for this field.
*
**/


function xp_dataset_create_global_field( $value, $field, $field_type_obj ) {

	global $xp_dataset_field;

	$xp_dataset_field = $field;

	

	return $value;
}

/**
*
* Validates dataset option
* 
* If field is using a dataset to generate options, vaidation will fail because option must be
* whitelisted by BuddyPress.  Therefore we must just make sure that the value passed in is 
* valid within our dataset.
*
* @param bool $validated True if option is valid
* @param array OR string $values Option(s) submitted by field.  If multiple values( ie. checkboxes) variable is array.
* @param object $this BP_Xprofile_Field_Type.
*
* @global $xp_dataset_field object bp_xprofile_field being validated.
*
* @return bool $validated true if object is valid.
*
**/

function xp_dataset_validate_options( $validated, $values, $this ) {

	global $xp_dataset_field;

	$field_id = $xp_dataset_field->id;

	$xp_dataset = get_xp_dataset( $field_id );

	if ( $xp_dataset ) {
		
		$dataset = get_post_meta( $xp_dataset, 'xp_dataset', true);
		$valid_values = array();
		
		foreach ( $dataset as $data ) {

			$valid_values[] = $data['value']; 
		}

		if ( is_array( $values) ) {

			foreach ( $values as $value ) {

				if ( in_array( $value, $valid_values ) ) {

					$validated = true;
				
				} else {

					$validated = false;
					return $validated;
				} 
			}
		
		} else {

			if ( in_array( $values, $valid_values ) ) {

				$validated = true;
			
			} else {

				$validated = false;
				return $validated;
			} 
		}

	}

	return $validated;

}

add_filter( 'bp_xprofile_field_type_is_valid', 'xp_dataset_validate_options', 10, 3);

add_action ('plugins_loaded', 'xp_dataset_add_datasets_on_load');

/**
* 
* Loads pre-made datasets after plugin is loaded.  
*
* Comes with Countries and US States.  Also contains action hook to add custom datasets on load.
*
**/

function xp_dataset_add_datasets_on_load() {

	$countries = get_page_by_title( 'Countries With Abbreviation', 'OBJECT', 'xp_dataset_dataset');

	if (! $countries ) {

		$args = array('post_title' => 'Countries With Abbreviation', 'post_type' => 'xp_dataset_dataset', 'post_status' => 'publish');

		$post_id = wp_insert_post( $args );

		
		$countries = array(

			array( 'value' => 'AF', 'text' => 'Afghanistan'),
			array( 'value' => 'AX', 'text' => 'Aland Islands'),
			array( 'value' => 'AL', 'text' => 'Albania'),
			array( 'value' => 'DZ', 'text' => 'Algeria'),
			array( 'value' => 'AS', 'text' => 'American Samoa'),
			array( 'value' => 'AD', 'text' => 'Andorra'),
			array( 'value' => 'AO', 'text' => 'Angola'),
			array( 'value' => 'AI', 'text' => 'Anguilla'),
			array( 'value' => 'AQ', 'text' => 'Antarctica'),
			array( 'value' => 'AG', 'text' => 'Antigua And Barbuda'),
			array( 'value' => 'AR', 'text' => 'Argentina'),
			array( 'value' => 'AM', 'text' => 'Armenia'),
			array( 'value' => 'AW', 'text' => 'Aruba'),
			array( 'value' => 'AU', 'text' => 'Australia'),
			array( 'value' => 'AT', 'text' => 'Austria'),
			array( 'value' => 'AZ', 'text' => 'Azerbaijan'),
			array( 'value' => 'BS', 'text' => 'Bahamas'),
			array( 'value' => 'BH', 'text' => 'Bahrain'),
			array( 'value' => 'BD', 'text' => 'Bangladesh'),
			array( 'value' => 'BB', 'text' => 'Barbados'),
			array( 'value' => 'BY', 'text' => 'Belarus'),
			array( 'value' => 'BE', 'text' => 'Belgium'),
			array( 'value' => 'BZ', 'text' => 'Belize'),
			array( 'value' => 'BJ', 'text' => 'Benin'),
			array( 'value' => 'BM', 'text' => 'Bermuda'),
			array( 'value' => 'BT', 'text' => 'Bhutan'),
			array( 'value' => 'BO', 'text' => 'Bolivia'),
			array( 'value' => 'BA', 'text' => 'Bosnia And Herzegovina'),
			array( 'value' => 'BW', 'text' => 'Botswana'),
			array( 'value' => 'BV', 'text' => 'Bouvet Island'),
			array( 'value' => 'BR', 'text' => 'Brazil'),
			array( 'value' => 'IO', 'text' => 'British Indian Ocean Territory'),
			array( 'value' => 'BN', 'text' => 'Brunei Darussalam'),
			array( 'value' => 'BG', 'text' => 'Bulgaria'),
			array( 'value' => 'BF', 'text' => 'Burkina Faso'),
			array( 'value' => 'BI', 'text' => 'Burundi'),
			array( 'value' => 'KH', 'text' => 'Cambodia'),
			array( 'value' => 'CM', 'text' => 'Cameroon'),
			array( 'value' => 'CA', 'text' => 'Canada'),
			array( 'value' => 'CV', 'text' => 'Cape Verde'),
			array( 'value' => 'KY', 'text' => 'Cayman Islands'),
			array( 'value' => 'CF', 'text' => 'Central African Republic'),
			array( 'value' => 'TD', 'text' => 'Chad'),
			array( 'value' => 'CL', 'text' => 'Chile'),
			array( 'value' => 'CN', 'text' => 'China'),
			array( 'value' => 'CX', 'text' => 'Christmas Island'),
			array( 'value' => 'CC', 'text' => 'Cocos (Keeling) Islands'),
			array( 'value' => 'CO', 'text' => 'Colombia'),
			array( 'value' => 'KM', 'text' => 'Comoros'),
			array( 'value' => 'CG', 'text' => 'Congo'),
			array( 'value' => 'CD', 'text' => 'Congo, Democratic Republic'),
			array( 'value' => 'CK', 'text' => 'Cook Islands'),
			array( 'value' => 'CR', 'text' => 'Costa Rica'),
			array( 'value' => 'CI', 'text' => 'Cote D\'Ivoire'),
			array( 'value' => 'HR', 'text' => 'Croatia'),
			array( 'value' => 'CU', 'text' => 'Cuba'),
			array( 'value' => 'CY', 'text' => 'Cyprus'),
			array( 'value' => 'CZ', 'text' => 'Czech Republic'),
			array( 'value' => 'DK', 'text' => 'Denmark'),
			array( 'value' => 'DJ', 'text' => 'Djibouti'),
			array( 'value' => 'DM', 'text' => 'Dominica'),
			array( 'value' => 'DO', 'text' => 'Dominican Republic'),
			array( 'value' => 'EC', 'text' => 'Ecuador'),
			array( 'value' => 'EG', 'text' => 'Egypt'),
			array( 'value' => 'SV', 'text' => 'El Salvador'),
			array( 'value' => 'GQ', 'text' => 'Equatorial Guinea'),
			array( 'value' => 'ER', 'text' => 'Eritrea'),
			array( 'value' => 'EE', 'text' => 'Estonia'),
			array( 'value' => 'ET', 'text' => 'Ethiopia'),
			array( 'value' => 'FK', 'text' => 'Falkland Islands (Malvinas)'),
			array( 'value' => 'FO', 'text' => 'Faroe Islands'),
			array( 'value' => 'FJ', 'text' => 'Fiji'),
			array( 'value' => 'FI', 'text' => 'Finland'),
			array( 'value' => 'FR', 'text' => 'France'),
			array( 'value' => 'GF', 'text' => 'French Guiana'),
			array( 'value' => 'PF', 'text' => 'French Polynesia'),
			array( 'value' => 'TF', 'text' => 'French Southern Territories'),
			array( 'value' => 'GA', 'text' => 'Gabon'),
			array( 'value' => 'GM', 'text' => 'Gambia'),
			array( 'value' => 'GE', 'text' => 'Georgia'),
			array( 'value' => 'DE', 'text' => 'Germany'),
			array( 'value' => 'GH', 'text' => 'Ghana'),
			array( 'value' => 'GI', 'text' => 'Gibraltar'),
			array( 'value' => 'GR', 'text' => 'Greece'),
			array( 'value' => 'GL', 'text' => 'Greenland'),
			array( 'value' => 'GD', 'text' => 'Grenada'),
			array( 'value' => 'GP', 'text' => 'Guadeloupe'),
			array( 'value' => 'GU', 'text' => 'Guam'),
			array( 'value' => 'GT', 'text' => 'Guatemala'),
			array( 'value' => 'GG', 'text' => 'Guernsey'),
			array( 'value' => 'GN', 'text' => 'Guinea'),
			array( 'value' => 'GW', 'text' => 'Guinea-Bissau'),
			array( 'value' => 'GY', 'text' => 'Guyana'),
			array( 'value' => 'HT', 'text' => 'Haiti'),
			array( 'value' => 'HM', 'text' => 'Heard Island & Mcdonald Islands'),
			array( 'value' => 'VA', 'text' => 'Holy See (Vatican City State)'),
			array( 'value' => 'HN', 'text' => 'Honduras'),
			array( 'value' => 'HK', 'text' => 'Hong Kong'),
			array( 'value' => 'HU', 'text' => 'Hungary'),
			array( 'value' => 'IS', 'text' => 'Iceland'),
			array( 'value' => 'IN', 'text' => 'India'),
			array( 'value' => 'ID', 'text' => 'Indonesia'),
			array( 'value' => 'IR', 'text' => 'Iran, Islamic Republic Of'),
			array( 'value' => 'IQ', 'text' => 'Iraq'),
			array( 'value' => 'IE', 'text' => 'Ireland'),
			array( 'value' => 'IM', 'text' => 'Isle Of Man'),
			array( 'value' => 'IL', 'text' => 'Israel'),
			array( 'value' => 'IT', 'text' => 'Italy'),
			array( 'value' => 'JM', 'text' => 'Jamaica'),
			array( 'value' => 'JP', 'text' => 'Japan'),
			array( 'value' => 'JE', 'text' => 'Jersey'),
			array( 'value' => 'JO', 'text' => 'Jordan'),
			array( 'value' => 'KZ', 'text' => 'Kazakhstan'),
			array( 'value' => 'KE', 'text' => 'Kenya'),
			array( 'value' => 'KI', 'text' => 'Kiribati'),
			array( 'value' => 'KR', 'text' => 'Korea'),
			array( 'value' => 'KW', 'text' => 'Kuwait'),
			array( 'value' => 'KG', 'text' => 'Kyrgyzstan'),
			array( 'value' => 'LA', 'text' => 'Lao People\'s Democratic Republic'),
			array( 'value' => 'LV', 'text' => 'Latvia'),
			array( 'value' => 'LB', 'text' => 'Lebanon'),
			array( 'value' => 'LS', 'text' => 'Lesotho'),
			array( 'value' => 'LR', 'text' => 'Liberia'),
			array( 'value' => 'LY', 'text' => 'Libyan Arab Jamahiriya'),
			array( 'value' => 'LI', 'text' => 'Liechtenstein'),
			array( 'value' => 'LT', 'text' => 'Lithuania'),
			array( 'value' => 'LU', 'text' => 'Luxembourg'),
			array( 'value' => 'MO', 'text' => 'Macao'),
			array( 'value' => 'MK', 'text' => 'Macedonia'),
			array( 'value' => 'MG', 'text' => 'Madagascar'),
			array( 'value' => 'MW', 'text' => 'Malawi'),
			array( 'value' => 'MY', 'text' => 'Malaysia'),
			array( 'value' => 'MV', 'text' => 'Maldives'),
			array( 'value' => 'ML', 'text' => 'Mali'),
			array( 'value' => 'MT', 'text' => 'Malta'),
			array( 'value' => 'MH', 'text' => 'Marshall Islands'),
			array( 'value' => 'MQ', 'text' => 'Martinique'),
			array( 'value' => 'MR', 'text' => 'Mauritania'),
			array( 'value' => 'MU', 'text' => 'Mauritius'),
			array( 'value' => 'YT', 'text' => 'Mayotte'),
			array( 'value' => 'MX', 'text' => 'Mexico'),
			array( 'value' => 'FM', 'text' => 'Micronesia, Federated States Of'),
			array( 'value' => 'MD', 'text' => 'Moldova'),
			array( 'value' => 'MC', 'text' => 'Monaco'),
			array( 'value' => 'MN', 'text' => 'Mongolia'),
			array( 'value' => 'ME', 'text' => 'Montenegro'),
			array( 'value' => 'MS', 'text' => 'Montserrat'),
			array( 'value' => 'MA', 'text' => 'Morocco'),
			array( 'value' => 'MZ', 'text' => 'Mozambique'),
			array( 'value' => 'MM', 'text' => 'Myanmar'),
			array( 'value' => 'NA', 'text' => 'Namibia'),
			array( 'value' => 'NR', 'text' => 'Nauru'),
			array( 'value' => 'NP', 'text' => 'Nepal'),
			array( 'value' => 'NL', 'text' => 'Netherlands'),
			array( 'value' => 'AN', 'text' => 'Netherlands Antilles'),
			array( 'value' => 'NC', 'text' => 'New Caledonia'),
			array( 'value' => 'NZ', 'text' => 'New Zealand'),
			array( 'value' => 'NI', 'text' => 'Nicaragua'),
			array( 'value' => 'NE', 'text' => 'Niger'),
			array( 'value' => 'NG', 'text' => 'Nigeria'),
			array( 'value' => 'NU', 'text' => 'Niue'),
			array( 'value' => 'NF', 'text' => 'Norfolk Island'),
			array( 'value' => 'MP', 'text' => 'Northern Mariana Islands'),
			array( 'value' => 'NO', 'text' => 'Norway'),
			array( 'value' => 'OM', 'text' => 'Oman'),
			array( 'value' => 'PK', 'text' => 'Pakistan'),
			array( 'value' => 'PW', 'text' => 'Palau'),
			array( 'value' => 'PS', 'text' => 'Palestinian Territory, Occupied'),
			array( 'value' => 'PA', 'text' => 'Panama'),
			array( 'value' => 'PG', 'text' => 'Papua New Guinea'),
			array( 'value' => 'PY', 'text' => 'Paraguay'),
			array( 'value' => 'PE', 'text' => 'Peru'),
			array( 'value' => 'PH', 'text' => 'Philippines'),
			array( 'value' => 'PN', 'text' => 'Pitcairn'),
			array( 'value' => 'PL', 'text' => 'Poland'),
			array( 'value' => 'PT', 'text' => 'Portugal'),
			array( 'value' => 'PR', 'text' => 'Puerto Rico'),
			array( 'value' => 'QA', 'text' => 'Qatar'),
			array( 'value' => 'RE', 'text' => 'Reunion'),
			array( 'value' => 'RO', 'text' => 'Romania'),
			array( 'value' => 'RU', 'text' => 'Russian Federation'),
			array( 'value' => 'RW', 'text' => 'Rwanda'),
			array( 'value' => 'BL', 'text' => 'Saint Barthelemy'),
			array( 'value' => 'SH', 'text' => 'Saint Helena'),
			array( 'value' => 'KN', 'text' => 'Saint Kitts And Nevis'),
			array( 'value' => 'LC', 'text' => 'Saint Lucia'),
			array( 'value' => 'MF', 'text' => 'Saint Martin'),
			array( 'value' => 'PM', 'text' => 'Saint Pierre And Miquelon'),
			array( 'value' => 'VC', 'text' => 'Saint Vincent And Grenadines'),
			array( 'value' => 'WS', 'text' => 'Samoa'),
			array( 'value' => 'SM', 'text' => 'San Marino'),
			array( 'value' => 'ST', 'text' => 'Sao Tome And Principe'),
			array( 'value' => 'SA', 'text' => 'Saudi Arabia'),
			array( 'value' => 'SN', 'text' => 'Senegal'),
			array( 'value' => 'RS', 'text' => 'Serbia'),
			array( 'value' => 'SC', 'text' => 'Seychelles'),
			array( 'value' => 'SL', 'text' => 'Sierra Leone'),
			array( 'value' => 'SG', 'text' => 'Singapore'),
			array( 'value' => 'SK', 'text' => 'Slovakia'),
			array( 'value' => 'SI', 'text' => 'Slovenia'),
			array( 'value' => 'SB', 'text' => 'Solomon Islands'),
			array( 'value' => 'SO', 'text' => 'Somalia'),
			array( 'value' => 'ZA', 'text' => 'South Africa'),
			array( 'value' => 'GS', 'text' => 'South Georgia And Sandwich Isl.'),
			array( 'value' => 'ES', 'text' => 'Spain'),
			array( 'value' => 'LK', 'text' => 'Sri Lanka'),
			array( 'value' => 'SD', 'text' => 'Sudan'),
			array( 'value' => 'SR', 'text' => 'Suriname'),
			array( 'value' => 'SJ', 'text' => 'Svalbard And Jan Mayen'),
			array( 'value' => 'SZ', 'text' => 'Swaziland'),
			array( 'value' => 'SE', 'text' => 'Sweden'),
			array( 'value' => 'CH', 'text' => 'Switzerland'),
			array( 'value' => 'SY', 'text' => 'Syrian Arab Republic'),
			array( 'value' => 'TW', 'text' => 'Taiwan'),
			array( 'value' => 'TJ', 'text' => 'Tajikistan'),
			array( 'value' => 'TZ', 'text' => 'Tanzania'),
			array( 'value' => 'TH', 'text' => 'Thailand'),
			array( 'value' => 'TL', 'text' => 'Timor-Leste'),
			array( 'value' => 'TG', 'text' => 'Togo'),
			array( 'value' => 'TK', 'text' => 'Tokelau'),
			array( 'value' => 'TO', 'text' => 'Tonga'),
			array( 'value' => 'TT', 'text' => 'Trinidad And Tobago'),
			array( 'value' => 'TN', 'text' => 'Tunisia'),
			array( 'value' => 'TR', 'text' => 'Turkey'),
			array( 'value' => 'TM', 'text' => 'Turkmenistan'),
			array( 'value' => 'TC', 'text' => 'Turks And Caicos Islands'),
			array( 'value' => 'TV', 'text' => 'Tuvalu'),
			array( 'value' => 'UG', 'text' => 'Uganda'),
			array( 'value' => 'UA', 'text' => 'Ukraine'),
			array( 'value' => 'AE', 'text' => 'United Arab Emirates'),
			array( 'value' => 'GB', 'text' => 'United Kingdom'),
			array( 'value' => 'US', 'text' => 'United States'),
			array( 'value' => 'UM', 'text' => 'United States Outlying Islands'),
			array( 'value' => 'UY', 'text' => 'Uruguay'),
			array( 'value' => 'UZ', 'text' => 'Uzbekistan'),
			array( 'value' => 'VU', 'text' => 'Vanuatu'),
			array( 'value' => 'VE', 'text' => 'Venezuela'),
			array( 'value' => 'VN', 'text' => 'Viet Nam'),
			array( 'value' => 'VG', 'text' => 'Virgin Islands, British'),
			array( 'value' => 'VI', 'text' => 'Virgin Islands, U.S.'),
			array( 'value' => 'WF', 'text' => 'Wallis And Futuna'),
			array( 'value' => 'EH', 'text' => 'Western Sahara'),
			array( 'value' => 'YE', 'text' => 'Yemen'),
			array( 'value' => 'ZM', 'text' => 'Zambia'),
			array( 'value' => 'ZW', 'text' => 'Zimbabwe')

		);

	}

	update_post_meta( $post_id, 'xp_dataset', $countries );

	//us states

	$us_states = get_page_by_title( 'US States With Abbreviation', 'OBJECT', 'xp_dataset_dataset');

	
	if (! $us_states ) {

		$args = array('post_title' => 'US States With Abbreviation', 'post_type' => 'xp_dataset_dataset', 'post_status' => 'publish');

		$post_id = wp_insert_post( $args );



		$us_states = array(

		  array( 'value' =>   'AL', 'text' => 'Alabama'),
		  array( 'value' =>   'AK', 'text' => 'Alaska'),
		  array( 'value' =>   'AZ', 'text' => 'Arizona'),
		  array( 'value' =>   'AR', 'text' => 'Arkansas'),
		  array( 'value' =>   'CA', 'text' => 'California'),
		  array( 'value' =>   'CO', 'text' => 'Colorado'),
		  array( 'value' =>   'CT', 'text' => 'Connecticut'),
		  array( 'value' =>   'DE', 'text' => 'Delaware'),
		  array( 'value' =>   'FL', 'text' => 'Florida'),
		  array( 'value' =>   'GA', 'text' => 'Georgia'),
		  array( 'value' =>   'HI', 'text' => 'Hawaii'),
		  array( 'value' =>   'ID', 'text' => 'Idaho'),
		  array( 'value' =>   'IL', 'text' => 'Illinois'),
		  array( 'value' =>   'IN', 'text' => 'Indiana'),
		  array( 'value' =>   'IA', 'text' => 'Iowa'),
		  array( 'value' =>   'KS', 'text' => 'Kansas'),
		  array( 'value' =>   'KY', 'text' => 'Kentucky'),
		  array( 'value' =>   'LA', 'text' => 'Louisiana'),
		  array( 'value' =>   'ME', 'text' => 'Maine'),
		  array( 'value' =>   'MD', 'text' => 'Maryland'),
		  array( 'value' =>   'MA', 'text' => 'Massachusetts'),
		  array( 'value' =>   'MI', 'text' => 'Michigan'),
		  array( 'value' =>   'MN', 'text' => 'Minnesota'),
		  array( 'value' =>   'MS', 'text' => 'Mississippi'),
		  array( 'value' =>   'MO', 'text' => 'Missouri'),
		  array( 'value' =>   'MT', 'text' => 'Montana'),
		  array( 'value' =>   'NE', 'text' => 'Nebraska'),
		  array( 'value' =>   'NV', 'text' => 'Nevada'),
		  array( 'value' =>   'NH', 'text' => 'New Hampshire'),
		  array( 'value' =>   'NJ', 'text' => 'New Jersey'),
		  array( 'value' =>   'NM', 'text' => 'New Mexico'),
		  array( 'value' =>   'NY', 'text' => 'New York'),
		  array( 'value' =>   'NC', 'text' => 'North Carolina'),
		  array( 'value' =>   'ND', 'text' => 'North Dakota'),
		  array( 'value' =>   'OH', 'text' => 'Ohio'),
		  array( 'value' =>   'OK', 'text' => 'Oklahoma'),
		  array( 'value' =>   'OR', 'text' => 'Oregon'),
		  array( 'value' =>   'PA', 'text' => 'Pennsylvania'),
		  array( 'value' =>   'RI', 'text' => 'Rhode Island'),
		  array( 'value' =>   'SC', 'text' => 'South Carolina'),
		  array( 'value' =>   'SD', 'text' => 'South Dakota'),
		  array( 'value' =>   'TN', 'text' => 'Tennessee'),
		  array( 'value' =>   'TX', 'text' => 'Texas'),
		  array( 'value' =>   'UT', 'text' => 'Utah'),
		  array( 'value' =>   'VT', 'text' => 'Vermont'),
		  array( 'value' =>   'VA', 'text' => 'Virginia'),
		  array( 'value' =>   'WA', 'text' => 'Washington'),
		  array( 'value' =>   'WV', 'text' => 'West Virginia'),
		  array( 'value' =>   'WI', 'text' => 'Wisconsin'),
		  array( 'value' =>   'WY', 'text' => 'Wyomin')
	    
	    );

		update_post_meta( $post_id, 'xp_dataset', $us_states );

}

	/**
	 * Fires after checking if Countries and States datasets have been loaded.
	 *
	 */

	do_action('xp_dataset_add_datasets_on_load');

}