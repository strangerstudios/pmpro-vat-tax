jQuery(document).ready(function(){
	//find billing country
	var billing_country = jQuery("select[name='bcountry']");

	//find gateway option
	var gateway_option = jQuery("input[name='gateway']");
	if(gateway_option.length < 0)
		gateway_option = jQuery("select[name='gateway']");
	
	// Assume 'hide'
	var showHideVATTable = -1;
	
	// country is used multiple places so define the variable once
	var country;
	
	var country_by_ip = jQuery('#geo_ip').val();

	// toggle the entire VAT table
	function pmprovt_toggleVATTable() {
		//always showing the table if no billing country or there are multiple gateway options
		if(billing_country.length < 1 || gateway_option.length > 0) {
			showHideVATTable = 1;
		} else {
			//otherwise check the billing country
			country = billing_country.find('option:selected').val();
			showHideVATTable = jQuery.inArray(country, pmprovat.eu_array);			
		}

		if(showHideVATTable > -1)
		{
			jQuery('#pmpro_vat_table').show();
			jQuery('#vat_number_validation_tr').hide();
			if(jQuery('#eucountry').val() == '' && jQuery('select[name=bcountry]').val() != '')
				jQuery('#eucountry').val( country );
			
			if(pmprovat.seller_country == country && pmprovat.hide_vat_same_country)
				jQuery('#vat_have_number').hide();
			else
				jQuery('#vat_have_number').show();
		
			if(country_by_ip == country)
				jQuery('#vat_confirm_country').hide();
			else
				jQuery('#vat_confirm_country').show();
		
		}
		else
		{
			jQuery('#pmpro_vat_table').hide();
			jQuery('#pmpro_vat_table').focus();
		}
		
		pmprovt_toggleVATNumber();
	}
	
	// toggle when the bcountry changes
	billing_country.change(function() {		
		pmprovt_toggleVATTable();	
	}).change();
	
	// toggle on load
	pmprovt_toggleVATTable();
	
	jQuery('#vat_number_validation_button').click(function() {
		var vat_number = jQuery('#vat_number').val();
		var eu_country = jQuery("select[name='eucountry']").find('option:selected').val();
		
		jQuery('#vat_number_message').hide();
		
		if(vat_number) {
			jQuery.ajax({
				url: pmprovat.ajaxurl,
				type:'GET',
				timeout: pmprovat.timeout,
				dataType: 'text',
				data: "action=pmprovat_vat_verification_ajax_callback&country=" + eu_country + "&vat_number=" + vat_number,
				error: function(xml){					
					console.log(xml);
					alert('Error verifying VAT: ' + xml.statusText);
					},
				success: function(response)
				{										
					response = JSON.parse(response);
					if(response.success == true)
					{						
						//print message
						jQuery('#pmpro_message, #vat_number_message').show();
						jQuery('#pmpro_message, #vat_number_message').removeClass('pmpro_error');
						jQuery('#pmpro_message, #vat_number_message').addClass('pmpro_success');
						jQuery('#pmpro_message, #vat_number_message').html(pmprovat.verified_text);

						jQuery('<input>').attr({
							type: 'hidden',
							id: 'vat_number_verified',
							name: 'vat_number_verified',
							value: '1'
						}).appendTo('#pmpro_form');
					}
					else
					{
						jQuery('#pmpro_message, #vat_number_message').show();
						jQuery('#pmpro_message, #vat_number_message').removeClass('pmpro_success');
						jQuery('#pmpro_message, #vat_number_message').addClass('pmpro_error');
						jQuery('#pmpro_message, #vat_number_message').html(pmprovat.not_verified_text);

						jQuery('#pmpro_form #vat_number_verified').remove();
					}
				}
			});
		}
	});
	
	// toggle the VATNumber area
	function pmprovt_toggleVATNumber() {
		if(jQuery('#show_vat').is(":checked"))
		{
			jQuery('#vat_number_validation_tr').show();
			jQuery('#pmpro_vat_table').focus();
		}
		
		else
		{
			jQuery('#vat_number_validation_tr').hide();
			jQuery('#pmpro_vat_table').focus();
		}
	}
	
	//toggle when checking
	jQuery('#show_vat').change(function(){
		pmprovt_toggleVATNumber();
	});
	
	//toggle on load
	pmprovt_toggleVATNumber();
});
