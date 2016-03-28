<?php
/*
Plugin Name: Paid Memberships Pro - VAT Tax
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-vat-tax/
Description: Calculate VAT tax at checkout and allow customers with a VAT Number lookup for VAT tax exemptions in EU countries.
Version: .1.1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

//uses: https://github.com/herdani/vat-validation/blob/master/vatValidation.class.php
//For EU VAT number checking.

/**
 * Setup required classes and global variables.
 */
function pmprovat_init()
{	
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
	
	/**
	 * Filter to add or filter vat taxes by country
	 */
	$pmpro_vat_by_country = apply_filters('pmpro_vat_by_country', $pmpro_vat_by_country);
    
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
	
	/**
	 * Filter to add/edit EU countries
	 */
	$pmpro_european_union = apply_filters('pmpro_european_union', $pmpro_european_union);
	
	add_action( 'wp_ajax_nopriv_pmprovat_vat_verification_ajax_callback', 'pmprovat_vat_verification_ajax_callback' );
	add_action( 'wp_ajax_pmprovat_vat_verification_ajax_callback', 'pmprovat_vat_verification_ajax_callback' );
}
add_action("init", "pmprovat_init");

/**
 * Enqueue VAT JS on checkout page
 */
function pmprovat_enqueue_scripts() {
	global $pmpro_pages, $pmpro_european_union;
	
	//PMPro not active
	if(empty($pmpro_pages))
		return;
	
	//only if we're on the checkout page
	if(!empty($_REQUEST['level']) || is_page($pmpro_pages['checkout'])) {		
		//register
		wp_register_script('pmprovat', plugin_dir_url( __FILE__ ) . 'js/pmprovat.js');
		
		//get values			
		wp_localize_script('pmprovat', 'pmprovat', 
			array(
				'eu_array' => array_keys($pmpro_european_union),
				'ajaxurl' => admin_url('admin-ajax.php'),
				'timeout' => apply_filters("pmpro_ajax_timeout", 5000, "applydiscountcode"),				
			)
		);
		//enqueue
		wp_enqueue_script('pmprovat', NULL, array('jquery'), '.1');
	}
}
add_action('wp_enqueue_scripts', 'pmprovat_enqueue_scripts');

/**
 * Get VAT Validation Class
 */
function pmprovat_get_VAT_validation() {	
	global $vatValidation;
	if(empty($vatValidation))
	{
		if(!class_exists("vatValidation"))
		{
			require_once(dirname(__FILE__) . "/includes/vatValidation.class.php");
		}
		
		$vatValidation = new vatValidation(array('debug' => false));
	}
	
	return $vatValidation;
}

/**
 * Helper function to verify a VAT number.
 */
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

/**
 * Show VAT tax info below level cost text.
 */
function pmprovat_pmpro_level_cost_text($cost, $level)
{
	$cost .= __(" Members in the EU will be charged a VAT tax.", "pmprovat");
	
	return $cost;
}
add_filter("pmpro_level_cost_text", "pmprovat_pmpro_level_cost_text", 10, 2);

/**
 * Show VAT country and number field at checkout.
 */
function pmprovat_pmpro_checkout_boxes()
{	
	global $pmpro_european_union;?>

<table id="pmpro_vat_table" class="pmpro_checkout" width="100%" cellpadding="0" cellspacing="0" border="0">
<thead>
	<tr>
		<th>
			<?php _e('European Union Residents VAT', 'pmprovat');?>
		</th>
	</tr>
</thead>
<tbody>
	<tr id="vat_confirm_country">	
		<td>
			<div>
				<?php
				//Add section below if billing address is from EU country
				if(!empty($_REQUEST['eucountry']))
					$eucountry = $_REQUEST['eucountry'];
				else
					$eucountry = "";
				?>
				<div id="eu_self_id_instructions"><?php _e('EU customers must confirm country of residence for VAT.', 'pmprovat');?></div>
				<label for="eucountry"><?php _e('Country of Residence', 'pmpro');?></label>
					<select name="eucountry" class=" <?php echo pmpro_getClassForField("eucountry");?>">
						<?php
							foreach($pmpro_european_union as $abbr => $country)
							{?>
								<option value="<?php echo $abbr?>" <?php selected($eucountry, $abbr);?>><?php echo $country?></option><?php
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
			<div><input id="show_vat" type="checkbox" name="show_vat" value="1"> <label for="show_vat" class="pmpro_normal pmpro_clickable"><?php _e('I have a VAT number', 'pmprovat');?></label></div>
		</td>
	</tr>
	
	<tr id="vat_number_validation_tr">
		<td>
			<div>
				<label for="vat_number"><?php _e('Vat Number', 'pmpro');?></label>
				<input id="vat_number" name="vat_number" class="input" type="text"  size="20" value="<?php ?>" />
				<input type="button" name="vat_number_validation_button" id="vat_number_validation_button" value="<?php _e('Apply', 'pmpro');?>" />
				<p id="vat_number_message" class="pmpro_message" style="display: none;"></p>
			</div>
		</td>
	</tr>
</tbody>
</table>
<?php
}
add_action("pmpro_checkout_after_billing_fields", "pmprovat_pmpro_checkout_boxes");

/**
 * AJAX callback to check the VAT number.
 */
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

/**
 * Check self identified country with billing address country and verify VAT number
 */
function pmprovat_check_vat_fields_submission($value)
{
	global $pmpro_european_union, $pmpro_msg, $pmpro_msgt;
	
	if(!empty($_REQUEST['bcountry']))
		$bcountry = $_REQUEST['bcountry'];
	else
		$bcountry = "";
	
	if(!empty($_REQUEST['eucountry']))
		$eucountry = $_REQUEST['eucountry'];
	else
		$eucountry = "";
	
	//only if billing country is an EU country
	if(!empty($bcountry) && array_key_exists($bcountry, $pmpro_european_union))
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
	
	if(!empty($_REQUEST['show_vat']))
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

/**
 * Update tax calculation if buyer is in EU or other states that charge VAT
 */
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

/**
 * Apply the VAT tax if an EU country is chosen at checkout.
 */
function pmprovat_pmpro_tax($tax, $values, $order)
{  	
	global $pmpro_vat_by_country;
		
	if(!empty($_REQUEST['vat_number']))
		$vat_number = $_REQUEST['vat_number'];
	else
		$vat_number = "";
		
	if(!empty($_REQUEST['eucountry']))
		$eucountry = $_REQUEST['eucountry'];
	else
		$eucountry = "";
	
	if(!empty($_REQUEST['show_vat']))
		$show_vat = 1;
	else
		$show_vat = 0;
	
	if(!empty($_REQUEST['vat_number_verified']) && $_REQUEST['vat_number_verified'] == "1")
		$vat_number_verified = true;
	else
		$vat_number_verified = false;
	
	$vat_rate = 0;
		
	//They didn't use the AJAX verify. Either they don't have a VAT number or
	//entered it didn't use it.
	if(!$vat_number_verified)
	{
		//they didn't use AJAX verify. Verify them now.
		if(!empty($vat_number) && !empty($eucountry) && pmprovat_verify_vat_number($eucountry, $vat_number))
		{
			$vat_rate = 0;
		}
		//they don't have a VAT number.
		elseif(!empty($values['billing_country']) && array_key_exists($values['billing_country'], $pmpro_vat_by_country))
		{
			//state VAT like British Columbia Canada
			if(is_array($pmpro_vat_by_country[$values['billing_country']]))
			{
				if(!empty($_REQUEST['bstate']))
					$state = $_REQUEST['bstate'];
				else
					$state = "";
				
				if(!empty($state) && array_key_exists($state, $pmpro_vat_by_country[$values['billing_country']]))
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

/**
 * Remove the taxregion session var on checkout
 */
function pmprovat_pmpro_after_checkout()
{
	if(isset($_SESSION['taxregion']))
		unset($_SESSION['taxregion']);
}
add_action("pmpro_after_checkout", "pmprovat_pmpro_after_checkout");

/**
 * Function to add links to the plugin row meta
 */
function pmprovat_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-vat-tax.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('http://www.paidmembershipspro.com/add-ons/plus-add-ons/vat-tax/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmprovat' ) ) . '">' . __( 'Support', 'pmprovat' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprovat_plugin_row_meta', 10, 2);