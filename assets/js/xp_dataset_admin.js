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

	jQuery("#fieldtype").change( function() {

		if( jQuery( this ).val() == 'selectbox' ) {

			jQuery( '#selectbox .inside').append('<p>HELLO</p>');
		}
	});


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

