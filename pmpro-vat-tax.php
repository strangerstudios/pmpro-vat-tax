<?php
/*
Plugin Name: Paid Memberships Pro - VAT Tax
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-vat-tax/
Description: Calculate VAT tax at checkout and allow customers with a VAT Number lookup for VAT tax exemptions in EU countries.
Version: .4
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
Text Domain: pmprovat
*/

//uses: https://github.com/herdani/vat-validation/blob/master/vatValidation.class.php
//For EU VAT number checking.

/**
 * Load plugin textdomain.
 */
function pmprovat_load_textdomain() {
	$domain = 'pmprovat';
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	load_textdomain( $domain ,  WP_LANG_DIR.'/pmpro-vat-tax/'.$domain.'-'.$locale.'.mo' );
	load_plugin_textdomain( $domain , FALSE ,  dirname(plugin_basename(__FILE__)). '/languages/' );
}

add_action( 'plugins_loaded', 'pmprovat_load_textdomain' );


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
		"GR" => 0.24,
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
		"RO" => 0.20,
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
	$pmpro_european_union = array(""	 => __( "- Choose One -" , 'pmprovat' ),
							"NOTEU" => __( "Non-EU Resident" , 'pmprovat' ),
							"AT"  => __( "Austria" , 'pmprovat' ),
							"BE"  => __( "Belgium" , 'pmprovat' ),
							"BG"  => __( "Bulgaria" , 'pmprovat' ),
							"HR"  => __( "Croatia", 'pmprovat' ),
							"CY"  => __( "Cyprus" , 'pmprovat' ),
							"CZ"  => __( "Czech Republic", 'pmprovat' ),
							"DK"  => __( "Denmark" , 'pmprovat' ),
							"EE"  => __( "Estonia" , 'pmprovat' ),
							"FI"  => __( "Finland" , 'pmprovat' ),
							"FR"  => __( "France" , 'pmprovat' ),
							"DE"  => __( "Germany" , 'pmprovat' ),
							"GR"  => __( "Greece" , 'pmprovat' ),
							"HU"  => __( "Hungary" , 'pmprovat' ),
							"IE"  => __( "Ireland" , 'pmprovat' ),
							"IT"  => __( "Italy" , 'pmprovat' ),
							"LV"  => __( "Latvia" , 'pmprovat' ),
							"LT"  => __( "Lithuania" , 'pmprovat' ),
							"LU"  => __( "Luxembourg" , 'pmprovat' ),
							"MT"  => __( "Malta" , 'pmprovat' ),
							"NL"  => __( "Netherlands" , 'pmprovat' ),
							"PL"  => __( "Poland" , 'pmprovat' ),
							"PT"  => __( "Portugal" , 'pmprovat' ),
							"RO"  => __( "Romania" , 'pmprovat' ),
							"SK"  => __( "Slovakia" , 'pmprovat' ),
							"SI"  => __( "Slovenia" , 'pmprovat' ),
							"ES"  => __( "Spain" , 'pmprovat' ),
							"SE"  => __( "Sweden" , 'pmprovat' ),
							"GB"  => __( "United Kingdom", 'pmprovat' )
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
				'seller_country' => get_option('pmprovt_seller_country'),
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
		
	if(empty($country) || empty($vat_number)) {
		$result = false;
	} else {
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
	global $pmpro_pages;
	if( is_page( $pmpro_pages["checkout"] ) )
		$cost .= " " . __("Members in the EU will be charged a VAT tax.", "pmprovat");

	return $cost;
}
add_filter("pmpro_level_cost_text", "pmprovat_pmpro_level_cost_text", 10, 2);

/**
 * Show VAT country and number field at checkout.
 */
function pmprovat_pmpro_checkout_boxes()
{
	global $pmpro_european_union, $pmpro_review;
	
	//get some values
	if(!empty($_REQUEST['eucountry']))
		$eucountry = $_REQUEST['eucountry'];
	elseif(!empty($_SESSION['eucountry']))
		$eucountry = $_SESSION['eucountry'];
	else
		$eucountry = "";
	
	if(!empty($_REQUEST['show_vat']))
		$show_vat = $_REQUEST['show_vat'];
	elseif(!empty($_SESSION['show_vat']))
		$show_vat = $_SESSION['show_vat'];
	else
		$show_vat = "";
	
	if(!empty($_REQUEST['vat_number']))
		$vat_number = $_REQUEST['vat_number'];
	elseif(!empty($_SESSION['vat_number']))
		$vat_number = $_SESSION['vat_number'];
	else
		$vat_number = "";
?>
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
				<div id="eu_self_id_instructions"><?php _e('EU customers must confirm country of residence for VAT.', 'pmprovat');?></div>
				<label for="eucountry"><?php _e('Country of Residence', 'pmprovat');?></label>
				<?php if(!$pmpro_review) { ?>
					<?php
					//EU country
					?>					
					<select id="eucountry" name="eucountry" class=" <?php echo pmpro_getClassForField("eucountry");?>">
						<?php
							foreach($pmpro_european_union as $abbr => $country)
							{?>
								<option value="<?php echo $abbr?>" <?php selected($eucountry, $abbr);?>><?php echo $country?></option><?php
							}
						?>
					</select>					
					<?php //Hidden field to enable tax?>
					<input type="hidden" id="taxregion" name="taxregion" value="1">					
				<?php } elseif(!empty($eucountry)) { ?>
					<span><?php echo $pmpro_european_union[$eucountry];?></span>
				<?php } ?>
			</div>
		</td>		
	</tr>
	
	<?php if(!$pmpro_review) { ?>		
		<tr id="vat_have_number">
			<td>
				<div>
					<input id="show_vat" type="checkbox" name="show_vat" value="1" <?php checked($show_vat, 1);?>> <label for="show_vat" class="pmpro_normal pmpro_clickable"><?php _e('I have a VAT number', 'pmprovat');?></label>			
				</div>
			</td>
		</tr>

		<tr id="vat_number_validation_tr">
			<td>
				<div>
					<label for="vat_number"><?php _e('Vat Number', 'pmprovat');?></label>
					<input id="vat_number" name="vat_number" class="input" type="text"  size="20" value="<?php echo esc_attr($vat_number);?>" />
					<input type="button" name="vat_number_validation_button" id="vat_number_validation_button" value="<?php _e('Apply', 'pmpro');?>" />
					<p id="vat_number_message" class="pmpro_message" style="display: none;"></p>
				</div>
			</td>
		</tr>
	<?php } elseif($pmpro_review && !empty($vat_number)) { ?>
		<tr>
			<td>
				<div>
					<label for="vat_number"><?php _e('Vat Number', 'pmprovat');?></label>
					<?php echo $vat_number;?>
				</div>
			</td>
		</tr>
	<?php } ?>
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
	
	//	Greece is a special case as ISO Country Code is GR while in EU VAT it has EL.
	//	So in case the user selected Greece (GR), let's change it here to EL.
	$country = $country == 'GR' ? 'EL' : $country;
	
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

	$vat_number = $_REQUEST['vat_number'];
	$seller_country = get_option('pmprovt_seller_country');

	if(!empty($_REQUEST['show_vat']))
		$show_vat = 1;
	else
		$show_vat = 0;

	//check that we have values to check
	if(empty($eucountry)){
		$value = false;
	} elseif(empty($vat_number) && $show_vat == 1) {
		$pmpro_msg = __( "VAT number was not entered.",  'pmprovat' );
		$pmpro_msgt = "pmpro_error";
		$value = false;
	} elseif(!empty($bcountry) && array_key_exists($bcountry, $pmpro_european_union)) { //only if billing country is an EU country
		if($bcountry !== $eucountry) {
			$pmpro_msg = __( "Billing country and country self identification must match", 'pmprovat' );
			$pmpro_msgt = "pmpro_error";
			$value = false;
		}
	} elseif($bcountry  == $seller_country) {
		$pmpro_msg = __( "VAT number not accepted. Seller in same country",  'pmprovat' );
		$pmpro_msgt = "pmpro_error";
		$value = false;
	
	//they checked to box for VAT Number and entered the number but didn't
	//actually hit "Apply". If it verifies, go through with checkout
	//otherwise, assume they made a mistake and stop the checkout
	} elseif($show_vat && !pmprovat_verify_vat_number($eucountry, $vat_number)) {
		$pmpro_msg = __( "VAT number was not verifed. Please try again.",  'pmprovat' );
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
	elseif(!empty($_SESSION['vat_number']))
		$vat_number = $_SESSION['vat_number'];
	else
		$vat_number = "";

	if(!empty($_REQUEST['eucountry']))
		$eucountry = $_REQUEST['eucountry'];
	elseif(!empty($_SESSION['eucountry']))
		$eucountry = $_SESSION['eucountry'];
	elseif(!empty($values['billing_country']))
		$eucountry = $values['billing_country'];
	else
		$eucountry = "";

	if(!empty($_REQUEST['show_vat']))
		$show_vat = 1;
	elseif(!empty($_SESSION['show_vat']))
		$show_vat = $_SESSION['show_vat'];
	else
		$show_vat = 0;

	if(!empty($_REQUEST['vat_number_verified']) && $_REQUEST['vat_number_verified'] == "1")
		$vat_number_verified = true;
	elseif(!empty($_SESSION['vat_number_verified']) && $_SESSION['vat_number_verified'] == "1")
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
		elseif(!empty($eucountry) && array_key_exists($eucountry, $pmpro_vat_by_country))
		{
			//state VAT like British Columbia Canada
			if(is_array($pmpro_vat_by_country[$eucountry]))
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
				$vat_rate = $pmpro_vat_by_country[$eucountry];
		}
	}

	if(!empty($vat_rate))
		$tax = $tax + round((float)$values['price'] * $vat_rate, 2);

	return $tax;
}

/**
 * Save VAT to Session when going to an offsite gateway.
 */
function pmprovat_pmpro_checkout_before_processing() {
	if(!empty($_REQUEST['eucountry']))
		$_SESSION['eucountry'] = $_REQUEST['eucountry'];
	if(!empty($_REQUEST['bcountry']))
		$_SESSION['bcountry'] = $_REQUEST['bcountry'];
	if(!empty($_REQUEST['show_vat']))
		$_SESSION['show_vat'] = $_REQUEST['show_vat'];
	if(!empty($_REQUEST['vat_number']))
		$_SESSION['vat_number'] = $_REQUEST['vat_number'];
	if(!empty($_REQUEST['vat_number_verified']))
		$_SESSION['vat_number_verified'] = $_REQUEST['vat_number_verified'];
}
add_action('pmpro_checkout_before_processing', 'pmprovat_pmpro_checkout_before_processing');

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

function pmprovat_pmpro_payment_option_fields($payment_option_values, $gateway)
{
	global $pmpro_european_union;
		
	if(isset($_REQUEST['pmprovt_seller_country']))
	{
		$seller_country = $_REQUEST['pmprovt_seller_country'];
		update_option('pmprovt_seller_country', $seller_country, 'no');
	}
	else
		$seller_country = get_option('pmprovt_seller_country');
	
	?>
			<tr class="pmpro_settings_divider">
				<td colspan="2">
					<?php _e('EU Vat Seller Country', 'paid-memberships-pro' ); ?>
				</td>
			</tr>
			
			<tr>
			<th scope="row" valign="top">
				<label for="pmprovt_seller_country"><?php _e('Seller Country', 'paid-memberships-pro' );?>:</label>
			</th>
			<td>
				<select id = "pmprovt_seller_country" name = "pmprovt_seller_country">
					<?php
						foreach($pmpro_european_union as $abbr => $country)
						{

						?>
						<option value="<?php echo $abbr?>" <?php if($abbr == $seller_country) { ?>selected="selected"<?php } ?>><?php echo $country?></option>
						<?php
						}
					?>
				</select>
			</td>
			</tr>
<?php

}
add_action('pmpro_payment_option_fields', 'pmprovat_pmpro_payment_option_fields', 10, 2);

/**
 * Function to add VAT Number to order notes
 */
function pmprovat_pmpro_added_order($order)
{
	global $wpdb;
	
	if(isset($_REQUEST['vat_number']))
		$vat_number = $_REQUEST['vat_number'];
	
	if(isset($_REQUEST['vat_number_verified']))
		$vat_number_verified = $_REQUEST['vat_number_verified'];
	
	if(isset($_REQUEST['bcountry']))
		$bcountry = $_REQUEST['bcountry'];
	
	$notes = "";

	if(!empty($vat_number) && $vat_number_verified)
	{
		$notes .= "\n---\n{EU_VAT_NUMBER:" . $vat_number . "}\n---\n";
		$notes .= "\n---\n{EU_VAT_COUNTRY:" . $bcountry . "}\n---\n";
	}
	
	$order->notes .= $notes;
	$sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET notes = '" . esc_sql($order->notes) . "' WHERE id = '" . intval($order->id) . "' LIMIT 1";
	$wpdb->query($sqlQuery);
	
	return $order;
}

add_action('pmpro_added_order', 'pmprovat_pmpro_added_order');

function pmprovat_pmpro_invoice_bullets_bottom($pmpro_invoice)
{
	$vat_number	= pmpro_getMatches("/{EU_VAT_NUMBER:([^}]*)}/", $pmpro_invoice->notes, true);

	if(isset($vat_number))
		?><li><?php _e('VAT Number: ', 'pmprovat').$vat_number ?></li><?php
}
add_action('pmpro_invoice_bullets_bottom', 'pmprovat_pmpro_invoice_bullets_bottom');