jQuery(document).ready(function(){
	jQuery("select[name='bcountry']").change(function() {
		var country = jQuery('select[name="bcountry"] option:selected').val();		
		var showHideVATTable;
		showHideVATTable = jQuery.inArray(country, pmprovat.eu_array);
		
		if(showHideVATTable > -1)
		{
			jQuery('#pmpro_vat_table').show();
			jQuery('#vat_number_validation_tr').hide();
			jQuery('#pmpro_vat_table').focus();
		}
		else
		{
			jQuery('#pmpro_vat_table').hide();
			jQuery('#pmpro_vat_table').focus();
		}
		
	}).change();
	
	jQuery('#vat_number_validation_button').click(function() {
		var vat_number = jQuery('#vat_number').val();
		var country = jQuery('select[name="bcountry"] option:selected').val();
		
		if(vat_number)
		{
			jQuery.ajax({
				url: pmprovat.ajaxurl,
				type:'GET',
				timeout: pmprovat.timeout,
				dataType: 'text',
				data: "action=pmprovat_vat_verification_ajax_callback&country=" + country + "&vat_number=" + vat_number,
				error: function(xml){					
					alert('Error verifying VAT [2]');
					},
				success: function(responseHTML)
				{
					if(responseHTML.trim() == 'true')
					{
						//alert("VAT Number was verified");
						//print message
						jQuery('#pmpro_message, #vat_number_message').show();							
						jQuery('#pmpro_message, #vat_number_message').addClass('pmpro_success');
						jQuery('#pmpro_message, #vat_number_message').html('VAT number was verifed');

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
						jQuery('#pmpro_message, #vat_number_message').html('VAT number was not verifed. Please try again.');
					}
				}
			});
		}	
	});
	
	function pmprovt_toggleVAT() {
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
		pmprovt_toggleVAT();
	});
	
	//toggle on load
	pmprovt_toggleVAT();
});