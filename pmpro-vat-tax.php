<?php
/*
Plugin Name: Paid Memberships Pro - VAT Tax
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-vat-tax/
Description: Calculate VAT tax at checkout and allow customers with a VAT Number lookup for VAT tax exemptions in EU countries.
Version: .1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

//uses: https://github.com/herdani/vat-validation/blob/master/vatValidation.class.php
//For EU VAT number checking.

function pmprovat_pmpro_tax($tax, $values, $order)
{  	
	global $pmpro_vat_by_country;
	
	$vat_number = $_REQUEST['vat_number'];
	$bcountry = $_REQUEST['bcountry'];
	
	if($_REQUEST['show_vat'] == 1)
		$show_vat = 1;
	else
		$show_vat = 0;
	
	if($_REQUEST['vat_number_verified'] == "1")
		$vat_number_verified = true;
	else
		$vat_number_verified = false;
	
	$vat_rate = 0;
	
	//They didn't use the AJAX verify. Either they don't have a VAT number or
	//entered it didn't use it.
	if(!$vat_number_verified)
	{
		//they didn't use AJAX verify. Verify them now.
		if(!empty($vat_number) && pmprovat_verify_vat_number($bcountry, $vat_number))
		{	
			$vat_rate = 0;	
		}
		
		//they don't have a VAT number.
		elseif(array_key_exists($values['billing_country'], $pmpro_vat_by_country))
		{
			//state VAT like British Columbia Canada
			if(is_array($pmpro_vat_by_country[$values['billing_country']]))
			{
				$state = $_REQUEST['bstate'];
				
				if(array_key_exists($state, $pmpro_vat_by_country[$values['billing_country']]))
				{
					$vat_rate = $pmpro_vat_by_country[$values['billing_country']][$state];
				}
			}
			
			else
				$vat_rate = $pmpro_vat_by_country[$values['billing_country']];
		}
	}

	$tax = round((float)$values['price'] * $vat_rate, 2);
	
	return $tax;
}

function pmprovat_init()
{
	if(!class_exists("vatValidation"))
	{
		require_once(dirname(__FILE__) . "/includes/vatValidation.class.php");
	}
	
	global $pmpro_vat_by_country;
	
	$pmpro_vat_by_country = array(
	"BE" => 0.21,
	"BG" => 0.20,
	"CZ" => 0.21,
	"DK" => 0.25,
	"DE" => 0.19,
	"EE" => 0.20,
	"GR" => 0.23,
	"ES" => 0.21,
	"FR" => 0.20,
	"HR" => 0.25,
	"IE" => 0.23,
	"IT" => 0.22,
	"CY" => 0.19,
	"LV" => 0.21,
	"LT" => 0.21,
	"LU" => 0.17,
	"HU" => 0.27,
	"MT" => 0.18,
	"NL" => 0.21,
	"AT" => 0.20,
	"PL" => 0.23,
	"PT" => 0.23,
	"RO" => 0.24,
	"SI" => 0.22,
	"SK" => 0.20,
	"FI" => 0.24,
	"SE" => 0.25,
	"UK" => 0.20,
	"CA" => array("BC" => 0.05)
);
    	//Identify EU countries
	global $pmpro_european_union;
	$pmpro_european_union = array(""	 => "",
							"BE"  => "Belgium",
							"BG"  => "Bulgaria",
							"CZ"  => "Czech Republic",
							"DK"  => "Denmark",
							"DE"  => "Germany",
							"EE"  => "Estonia",
							"IE"  => "Ireland",
							"EL"  => "Greece",
							"ES"  => "Spain",
							"FR"  => "France",
							"IT"  => "Italy",
							"CY"  => "Cyprus",
							"LV"  => "Latvia",
							"LT"  => "Lithuania",
							"LU"  => "Luxembourg",
							"HU"  => "Hungary",
							"MT"  => "Malta",
							"NL"  => "Netherlands",
							"AT"  => "Austria",
							"PL"  => "Poland",
							"PT"  => "Portugal",
							"RO"  => "Romania",
							"SI"  => "Slovenia",
							"SK"  => "Slovakia",
							"FI"  => "Finland",
							"SE"  => "Sweden",
							"UK"  => "United Kingdom"    
						    );
	
	add_action( 'wp_ajax_nopriv_pmprovat_vat_verification_ajax_callback', 'pmprovat_vat_verification_ajax_callback' );
	add_action( 'wp_ajax_pmprovat_vat_verification_ajax_callback', 'pmprovat_vat_verification_ajax_callback' );	
}

add_action("init", "pmprovat_init");

function pmprovat_get_VAT_validation()
{
	global $vatValidation;
	if(empty($vatValidation))
	{
		$vatValidation = new vatValidation(array('debug' => false));
	}
	
	return $vatValidation;
}


function pmprovat_pmpro_level_cost_text($cost, $level)
{
	$cost .= " Members in the EU will be charged a VAT tax.";
	
	return $cost;
}
add_filter("pmpro_level_cost_text", "pmprovat_pmpro_level_cost_text", 10, 2);
 
function pmprovat_pmpro_checkout_boxes()
{	
	global $pmpro_european_union;?>

<table id="pmpro_vat_table" class="pmpro_checkout" width="100%" cellpadding="0" cellspacing="0" border="0">
<thead>
	<tr>
		<th>
			European Union Residents VAT
		</th>
	</tr>
</thead>
<tbody>
	<tr id="vat_confirm_country">	
		<td>
			<div>
				<?php
				//Add section below if billing address is from EU country
				?>
				<div id="eu_self_id_instructions">EU customers must confirm country of residence for VAT.</div>
				<label for="eucountry"><?php _e('Country of Residence', 'pmpro');?></label>
					<select name="eucountry" class=" <?php echo pmpro_getClassForField("eucountry");?>">
						<?php
							foreach($pmpro_european_union as $abbr => $country)
							{?>
								<option value="<?php echo $abbr?>"><?php echo $country?></option><?php
							}
						?>
					</select>
				
				<?php //Hidden field to enable tax?>
				
				<input type="hidden" id="taxregion" name="taxregion" value="1">
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<div><input id="show_vat" type="checkbox" name="show_vat" value="1"> I have a VAT number</div>
		</td>
	</tr>
	
	<tr id="vat_number_validation_tr">
		<td>
			<div>
				<label for="vat_number"><?php _e('Vat Number', 'pmpro');?></label>
				<input id="vat_number" name="vat_number" type="text"  size="20" value="<?php ?>" />
				<input type="button" name="vat_number_validation_button" id="vat_number_validation_button" value="<?php _e('Apply', 'pmpro');?>" />
			</div>
		</td>
	</tr>
</tbody>
</table>
<script>
jQuery(document).ready(function(){
	jQuery("select[name='bcountry']").change(function() {
		var country = jQuery('select[name="bcountry"] option:selected').val();<?php
		$eu_array = array();
		foreach($pmpro_european_union as $abbreviation => $country_name)
		{
			$eu_array[] = "'".$abbreviation."'";
		}?>
		
		var showHideVATTable;
		
		showHideVATTable = jQuery.inArray(country, [<?php echo implode(',', $eu_array);?>]);
		
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
					url: '<?php echo admin_url('admin-ajax.php')?>',
					type:'GET',
					timeout:<?php echo apply_filters("pmpro_ajax_timeout", 5000, "applydiscountcode");?>,
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
							jQuery('#pmpro_message').show();
							jQuery('#pmpro_message').addClass('pmpro_success');
							jQuery('#pmpro_message').html('VAT number was verifed');

							jQuery('<input>').attr({
								type: 'hidden',
								id: 'vat_number_verified',
								name: 'vat_number_verified',
								value: '1'
							}).appendTo('#pmpro_form');
						}
						else
						{
							jQuery('#pmpro_message').show();
							jQuery('#pmpro_message').removeClass('pmpro_success');
							jQuery('#pmpro_message').addClass('pmpro_error');
							jQuery('#pmpro_message').html('VAT number was not verifed. Please try again.');
						}
					}
				});
			}	
		});
		
		jQuery('#show_vat').change(function(){
			if(jQuery(this).is(":checked"))
			{
				jQuery('#vat_number_validation_tr').show();
				jQuery('#pmpro_vat_table').focus();
			}
			
			else
			{
				jQuery('#vat_number_validation_tr').hide();
				jQuery('#pmpro_vat_table').focus();
			}
		});
});			   
</script>
<?php
}
add_action("pmpro_checkout_after_billing_fields", "pmprovat_pmpro_checkout_boxes");

function pmprovat_vat_verification_ajax_callback()
{
	$vat_number = $_REQUEST['vat_number'];
	$country = $_REQUEST['country'];
		
	$result = pmprovat_verify_vat_number($country, $vat_number);
	
	if($result)
		echo "true";
	else
		echo "false";
	
	exit();
} 

function pmprovat_verify_vat_number($country, $vat_number)
{
	$vatValidation = pmprovat_get_VAT_validation();
	
	if(empty($country) || empty($vat_number))
	{
		$result = false;
	}
	
	else
	{
		$result = $vatValidation->check($country, $vat_number);
	}
	
	$result = apply_filters('pmprovat_custom_vat_number_validate', $result);
	
	return $result;
}

//Check self identified country with billing address country and verify VAT number
function pmprovat_check_vat_fields_submission($value)
{
	global $pmpro_european_union, $pmpro_msg, $pmpro_msgt;
	
	$bcountry = $_REQUEST['bcountry'];
	$eucountry = $_REQUEST['eucountry'];
	
	//only if billing country is an EU country
	if(array_key_exists($bcountry, $pmpro_european_union))
	{
		if($bcountry !== $eucountry)
		{
			$pmpro_msg = "Billing country and country self identification must match";
			$pmpro_msgt = "pmpro_error";
			$value = false;	
		}
	}
	
	//they checked to box for VAT Number and entered the number but didn't 
	//actually hit "Apply". If it verifies, go through with checkout
	//otherwise, assume they made a mistake and stop the checkout
	
	$vat_number = $_REQUEST['vat_number'];
	
	if($_REQUEST['show_vat'] == 1)
		$show_vat = 1;
	else
		$show_vat = 0;
	
	if($show_vat && !pmprovat_verify_vat_number($bcountry, $vat_number))
	{
		$pmpro_msg = "VAT number was not verifed. Please try again.";
		$pmpro_msgt = "pmpro_error";
		$value = false;
	}

	return $value;
}

add_filter("pmpro_registration_checks", "pmprovat_check_vat_fields_submission");

//update tax calculation if buyer is in EU or other states that charge VAT
function pmprovat_region_tax_check()
{
	//check request and session
	if(isset($_REQUEST['taxregion']))
	{
		//update the session var
		$_SESSION['taxregion'] = $_REQUEST['taxregion'];	
		
		//not empty? setup the tax function
		if(!empty($_REQUEST['taxregion']))
		{
			add_filter("pmpro_tax", "pmprovat_pmpro_tax", 10, 3);
		}
	}
	elseif(!empty($_SESSION['taxregion']))
	{
		//add the filter
		add_filter("pmpro_tax", "pmprovat_pmpro_tax", 10, 3);
	}
	else
	{
		add_filter("pmpro_tax", "pmprovat_pmpro_tax", 10, 3);
	}
}
add_action("init", "pmprovat_region_tax_check");
 
//remove the taxregion session var on checkout
function pmprovat_pmpro_after_checkout()
{
	if(isset($_SESSION['taxregion']))
		unset($_SESSION['taxregion']);
}
add_action("pmpro_after_checkout", "pmprovat_pmpro_after_checkout");

/*
Function to add links to the plugin row meta
*/
function pmprovat_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-vat-tax.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprovat_plugin_row_meta', 10, 2);