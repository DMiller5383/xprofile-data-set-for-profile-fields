jQuery(document).ready(function(){


	jQuery("#xp_dataset_add_item").click( function ( event ) {

		event.preventDefault();
	
		id_num = xp_dataset_get_html_id_num();

		jQuery("#xp_dataset_wrapper").append( xp_dataset_admin_values_html( id_num) );
	
		jQuery(".xp_dataset_remove_item").click( function( event ) {

			event.preventDefault();
			id = jQuery(this).attr('id');
			id_num = xp_dataset_get_html_id_num_from_id( id );
			jQuery('#xp_dataset_input_wrapper-' + id_num).remove();


		});

	});

	jQuery(".xp_dataset_remove_item").click( function( event ) {

		event.preventDefault();
		id = jQuery(this).attr('id');
		id_num = xp_dataset_get_html_id_num_from_id( id );
		jQuery('#xp_dataset_input_wrapper-' + id_num).remove();

	

	});

	
	jQuery(".postbox.bp-options-box.ui-sortable .inside").append('<div class="xp-dataset-options"></div>');
	
	jQuery(".xp-dataset-options").append( '<input type="checkbox" name="xp-dataset-enable" id="xp-dataset-enable" class="xp-dataset-enable" value="enabled" '+ ( xp_datasets.dataset ? 'checked' : '' ) +'/>Use Dataset' );
	
	jQuery(".xp-dataset-options").append(' <select class="xp-dataset-select" id="xp-dataset-select"><option value="">Select</option>');

	
	


	jQuery(".xp-dataset-options").each( function( index, value ){

		field_type = jQuery( value ).parent().parent().attr('id');
		children = jQuery( value ).children();

		jQuery(this).attr( 'id', 'xp-dataset-options_' + field_type );

		jQuery(children).each( function( i, child ) {

			if ( jQuery(child).attr('class') == 'xp-dataset-select' ) {

				jQuery(child).attr( 'id', 'xp-dataset-select_' + field_type );
				jQuery(child).attr( 'name', 'xp-dataset-select_' + field_type );

				for (var key in xp_datasets['xp_dataset_dropdown']) {

						jQuery( '#xp-dataset-select_' + field_type ).append('<option value=' + key +' ' + ( key == xp_datasets.dataset ? "selected" : "" ) + '>' + xp_datasets['xp_dataset_dropdown'][key] + '</option>');

				}
			
			} else {

				jQuery(child).attr( 'id', 'xp-dataset-enable_' + field_type );  
				jQuery(child).attr( 'name', 'xp-dataset-enable_' + field_type );

				if ( jQuery('#xp-dataset-enable_' + field_type ).is( ':checked' ) ) {

					jQuery('#' + field_type + ' a').bind( 'click', function () {

						return false;
					
					});

				}

			}

		});

	});

	
	jQuery('.xp-dataset-enable').click( function() {


		id = jQuery(this).parent().parent().parent().attr('id');

		options = jQuery( '[id^='+ id +'_option]' );

		if (this.checked) {

			jQuery('#' + id + ' a').bind( 'click', function () {

				return false;
					
			});

			jQuery("#xp-dataset-select_" + id).prop('readonly', false);

			options.each( function( index, option ) {

				jQuery(option).prop( 'readonly', true );
				jQuery('[name^=isDefault_' + id + '_option]').prop('readonly', true);

				
			
			});

			if ( !xp_dataset_xprofile_option_values_populated( id ) ) {

				console.log( id );
				jQuery('#' + id + '_option1').val('Using Dataset');

			}


		} else {

			jQuery('#' + id + ' a').unbind( 'click' );
			jQuery('#xp-dataset-select_' + id).prop('readonly', true);

			options.each( function( index, option ) {

				jQuery(option).prop( 'readonly', false );
				jQuery('[name^=isDefault_' + id + '_option]').prop('readonly', false);

			});


			if ( jQuery('#' + id + '_option1').val().trim() == 'Using Dataset' ) {

				jQuery('#' + id + '_option1').val('');
			}

		}

	});


	if ( xp_datasets.dataset ) {

		id = xp_datasets.field_type;

		options = jQuery( '[id^='+ id +'_option]' );

		options.each( function( index, option ) {

			jQuery(option).prop( 'readonly', true );
			jQuery('[name^=isDefault_' + id + '_option]').prop('readonly', true);

		});
	}
	

});

function xp_dataset_admin_values_html( id_num ) {

	xp_dataset_values_html = '<div id="xp_dataset_input_wrapper-' + id_num + '">';

	xp_dataset_values_html += '<label for="xp_dataset_value-' + id_num +'">Value </label>';
	xp_dataset_values_html += '<input type="text" id="xp_dataset_value-' + id_num +'" name="xp_dataset_value[]" value="" />';

	xp_dataset_values_html += ' <label for="xp_dataset_text-' + id_num + '">Text </label>';
	xp_dataset_values_html += '<input type="text" id="xp_dataset_text-' + id_num +'" name="xp_dataset_text[]" value="" />';


	xp_dataset_values_html += ' <a href = "#" id = "xp_dataset_remove_item-' + id_num + '" class="xp_dataset_remove_item">Remove Item</a>';


	return xp_dataset_values_html;
}

function xp_dataset_get_html_id_num() {

	id_num = jQuery(".xp_dataset_input_wrapper").length;

	return id_num;
}

function xp_dataset_get_html_id_num_from_id( id ) {

	id_num = id.substr(id.indexOf("-") + 1);

	return id_num;
}

function xp_dataset_xprofile_option_values_populated( id ) {

	options = jQuery('[id^="' + id + '_div"]');
	populated = false;

	options.each( function (key, value) {

		div_id = jQuery(value).attr('id');
		text_box = jQuery("#" + div_id + ' input[type="text"]');
		
		if ( text_box.val().trim() != '') {

			populated = true;
			return false;

		}

	}); 

	return populated;
}
