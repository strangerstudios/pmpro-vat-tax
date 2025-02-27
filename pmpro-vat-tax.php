<?php
/*
Plugin Name: Paid Memberships Pro - VAT Tax
Plugin URI: https://www.paidmembershipspro.com/add-ons/vat-tax/
Description: Calculate VAT tax at checkout and allow customers with a VAT Number lookup for VAT tax exemptions in EU countries.
Version: 0.8.2
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Text Domain: pmpro-vat-tax
*/

//uses: https://github.com/herdani/vat-validation/blob/master/vatValidation.class.php
//For EU VAT number checking.

define( 'PMPRO_VAT_TAX_VERSION', '0.8.2' );

/**
 * Load plugin textdomain.
 */
function pmprovat_load_textdomain() {
	$domain = 'pmpro-vat-tax';
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

	//Uses ISO country designations
	$pmpro_vat_by_country = array(
		"BE" => 0.21,
		"BG" => 0.20,
		"CZ" => 0.21,
		"DK" => 0.25,
		"DE" => 0.19,
		"EE" => 0.22,
		"GR" => 0.24,		//EL
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
		"RO" => 0.19,
		"SI" => 0.22,
		"SK" => 0.23,
		"FI" => 0.255,
		"SE" => 0.25,
		"GB" => 0.20,		//UK
		"CA" => array("BC" => 0.05)
	);

	/**
	 * Filter to add or filter vat taxes by country
	 */
	$pmpro_vat_by_country = apply_filters('pmpro_vat_by_country', $pmpro_vat_by_country);

	//Identify EU countries. Uses ISO country designations
	global $pmpro_european_union;
	$pmpro_european_union = array(""	 => __( "- Choose One -" , 'pmpro-vat-tax' ),
							"NOTEU" => __( "Non-EU Resident" , 'pmpro-vat-tax' ),
							"AT"  => __( "Austria" , 'pmpro-vat-tax' ),
							"BE"  => __( "Belgium" , 'pmpro-vat-tax' ),
							"BG"  => __( "Bulgaria" , 'pmpro-vat-tax' ),
							"HR"  => __( "Croatia", 'pmpro-vat-tax' ),
							"CY"  => __( "Cyprus" , 'pmpro-vat-tax' ),
							"CZ"  => __( "Czech Republic", 'pmpro-vat-tax' ),
							"DK"  => __( "Denmark" , 'pmpro-vat-tax' ),
							"EE"  => __( "Estonia" , 'pmpro-vat-tax' ),
							"FI"  => __( "Finland" , 'pmpro-vat-tax' ),
							"FR"  => __( "France" , 'pmpro-vat-tax' ),
							"DE"  => __( "Germany" , 'pmpro-vat-tax' ),
							"GR"  => __( "Greece" , 'pmpro-vat-tax' ),
							"HU"  => __( "Hungary" , 'pmpro-vat-tax' ),
							"IE"  => __( "Ireland" , 'pmpro-vat-tax' ),
							"IT"  => __( "Italy" , 'pmpro-vat-tax' ),
							"LV"  => __( "Latvia" , 'pmpro-vat-tax' ),
							"LT"  => __( "Lithuania" , 'pmpro-vat-tax' ),
							"LU"  => __( "Luxembourg" , 'pmpro-vat-tax' ),
							"MT"  => __( "Malta" , 'pmpro-vat-tax' ),
							"NL"  => __( "Netherlands" , 'pmpro-vat-tax' ),
							"PL"  => __( "Poland" , 'pmpro-vat-tax' ),
							"PT"  => __( "Portugal" , 'pmpro-vat-tax' ),
							"RO"  => __( "Romania" , 'pmpro-vat-tax' ),
							"SK"  => __( "Slovakia" , 'pmpro-vat-tax' ),
							"SI"  => __( "Slovenia" , 'pmpro-vat-tax' ),
							"ES"  => __( "Spain" , 'pmpro-vat-tax' ),
							"SE"  => __( "Sweden" , 'pmpro-vat-tax' ),
							"GB"  => __( "United Kingdom", 'pmpro-vat-tax' )
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
 * Get a VAT tax rate from a country code
 */
function pmprovat_getTaxRate($country, $state = NULL) {
	global $pmpro_vat_by_country;
	
	//non-EU
	if(empty($pmpro_vat_by_country[$country]))
		return 0;
	
	//default to 0
	$vat_rate = 0;
	
	//state VAT like British Columbia Canada
	if(is_array($pmpro_vat_by_country[$country])) {
		if(!empty($state) && array_key_exists($state, $pmpro_vat_by_country[$country])) {
			$vat_rate = $pmpro_vat_by_country[$country][$state];
		}
	//single VAT for country
	} else {	
		$vat_rate = $pmpro_vat_by_country[$country];
	}
	
	return $vat_rate;
}

/**
 * Enqueue VAT JS on checkout page
 */
function pmprovat_enqueue_scripts() {
	global $pmpro_pages, $pmpro_european_union;

	//PMPro not active
	if(empty($pmpro_pages))
		return;

	//only if we're on the checkout page
	if ( pmpro_is_checkout() ) {
		//register
		wp_register_script('pmprovat', plugin_dir_url( __FILE__ ) . 'js/pmprovat.js', array('jquery'), PMPRO_VAT_TAX_VERSION);

		//get values
		wp_localize_script('pmprovat', 'pmprovat',
			array(
				'eu_array' => array_keys($pmpro_european_union),
				'ajaxurl' => admin_url('admin-ajax.php'),
				'timeout' => apply_filters("pmpro_ajax_timeout", 5000, 'applydiscountcode'),
				'seller_country' => get_option('pmprovt_seller_country'),
				'verified_text' => __('VAT number was verifed', 'pmpro-vat-tax'),
				'not_verified_text' => __('VAT number was not verifed. Please try again.', 'pmpro-vat-tax'),				
				'hide_vat_same_country' => apply_filters( 'pmprovat_hide_vat_if_same_country', true ),
			)
		);
		//enqueue
		wp_enqueue_script('pmprovat');
	}
}
add_action('wp_enqueue_scripts', 'pmprovat_enqueue_scripts');

/**
 * Get VAT Validation Class
 */
function pmprovat_getVATValidation() {
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
	/**
	 * Sometimes developers prefer to skip validation
	 */
	if( apply_filters('pmprovat_skip_validation', false) ){
		return true;
	}

	/**
	 * GB will be verified based on a regex to provide improved validation
	 * for UK VAT numbers after Brexit.
	 */
	if( $country === 'GB' ){
		preg_match('/^([GB])*(([1-9]\d{8})|([1-9]\d{11}))$/', $vat_number, $matches);
		$uk_vat_is_valid = isset($matches) && count($matches) > 0;
		return $uk_vat_is_valid;
	}

	/**
	 * Validation of any other country
	 */
	$vatValidation = pmprovat_getVATValidation();
		
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
	if( is_page( $pmpro_pages["checkout"] ) && !pmpro_isLevelFree($level) )
		$cost .= " " . __("Members in the EU will be charged a VAT tax.", "pmpro-vat-tax");

	return $cost;
}
add_filter("pmpro_level_cost_text", "pmprovat_pmpro_level_cost_text", 10, 2);

/**
 * Show VAT country and number field at checkout.
 */
function pmprovat_pmpro_checkout_boxes() {
	global $pmpro_level, $pmpro_european_union, $pmpro_review;

	//if free, no need
	if( pmpro_isLevelFree( $pmpro_level ) ) {
		return;
	}
	
	//get some values
	if ( ! empty( $_REQUEST['eucountry'] ) ) {
		$eucountry = $_REQUEST['eucountry'];
	} elseif ( ! empty( $_SESSION['eucountry'] ) ) {
		$eucountry = $_SESSION['eucountry'];
	} else {
		$eucountry = "";
	}
	
	if ( ! empty( $_REQUEST['show_vat'] ) ) {
		$show_vat = $_REQUEST['show_vat'];
	} elseif ( ! empty( $_SESSION['show_vat'] ) ) {
		$show_vat = $_SESSION['show_vat'];
	} else {
		$show_vat = "";
	}
	
	if ( ! empty( $_REQUEST['vat_number'] ) ) {
		$vat_number = $_REQUEST['vat_number'];
	} elseif ( ! empty( $_SESSION['vat_number'] ) ) {
		$vat_number = $_SESSION['vat_number'];
	} else {
		$vat_number = "";
	}
?>
<fieldset id="pmpro_form_fieldset-vat-tax" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fieldset', 'pmpro_form_fieldset-vat-tax' ) ); ?>">
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
			<legend class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_legend' ) ); ?>">
				<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_heading pmpro_font-large' ) ); ?>"><?php esc_html_e( 'European Union Residents VAT', 'pmpro-vat-tax' );?></h2>
			</legend> <!-- end pmpro_form_legend -->
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields' ) ); ?>">
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-select pmpro_form_field-eucountry', 'pmpro_form_field-eucountry' ) );?>">
					<label for="eucountry" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'Country of Residence', 'pmpro-vat-tax' );?></label>
					<?php if( ! $pmpro_review ) { ?>
						<select class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-select', 'eucountry' ) ); ?>" id="eucountry" name="eucountry">
							<?php
								foreach( $pmpro_european_union as $abbr => $country ) { ?>
									<option value="<?php echo esc_attr( $abbr ); ?>" <?php selected( $eucountry, $abbr );?>><?php echo esc_html( $country ); ?></option><?php
								}
							?>
						</select>
						<p id="eu_self_id_instructions" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_hint', 'eu_self_id_instructions' ) );?>"><?php esc_html_e( 'EU customers must confirm country of residence for VAT.', 'pmpro-vat-tax' );?></p>
					<?php } elseif ( ! empty( $eucountry ) ) { ?>
						<span><?php echo esc_html( $pmpro_european_union[$eucountry] ); ?></span>
					<?php } ?>
				</div> <!-- end pmpro_form_field-eucountry -->
				<input type="hidden" id="geo_ip" name="geo_ip" value=<?php echo esc_attr( pmprovat_determine_country_from_ip() ); ?>>
				<?php if ( ! $pmpro_review ) { ?>
					<div id="vat_have_number" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-checkbox' ) ); ?>">
						<label for="show_vat" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label pmpro_form_label-inline pmpro_clickable' ) ); ?>"><input name="show_vat" type="checkbox" value="1" id="show_vat" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-checkbox', 'pmpro_form_input-checkbox' ) ); ?>" <?php checked($show_vat, 1); ?>><?php esc_html_e( 'I have a VAT number', 'pmpro-vat-tax' );?></label>
					</div> <!-- end #vat_have_number -->
					<div id="vat_number_validation_tr" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field pmpro_form_field-text' ) ); ?>">
						<label for="vat_number" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'VAT Number', 'pmpro-vat-tax' ); ?></label>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_fields-inline' ) ); ?>">
							<input id="vat_number" name="vat_number" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_input pmpro_form_input-text' ) ); ?>" type="text" value="<?php echo esc_attr( $vat_number );?>" />
							<input type="button" name="vat_number_validation_button" id="vat_number_validation_button" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn' ) ); ?>" value="<?php esc_html_e( 'Apply', 'pmpro-vat-tax' );?>" />
						</div>
						<div id="vat_number_message" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message' ) ); ?>" style="display: none;"></div>
					</div> <!-- end vat_number_validation_tr -->
				<?php } elseif ( $pmpro_review && ! empty( $vat_number ) ) { ?>
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_field' ) ); ?>">
						<label for="vat_number" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_form_label' ) ); ?>"><?php esc_html_e( 'VAT Number', 'pmpro-vat-tax' ); ?></label>
						<span><?php echo esc_html( $vat_number ); ?></span>
					</div> <!-- end pmpro_form_field -->
				<?php } ?>
			</div> <!-- end pmpro_form_fields -->
		</div> <!-- end pmpro_card_content -->
	</div> <!-- end pmpro_card -->
</fieldset> <!-- end pmpro_form_fieldset-vat-tax -->
<?php
}
add_action("pmpro_checkout_after_billing_fields", "pmprovat_pmpro_checkout_boxes");

/**
 * AJAX callback to check the VAT number.
 */
function pmprovat_vat_verification_ajax_callback()
{
	$vat_number = sanitize_text_field($_REQUEST['vat_number']);
	$country = sanitize_text_field($_REQUEST['country']);
	
	//Greece is a special case as ISO Country Code is GR while in EU VAT it has EL.
	//So in case the user selected Greece (GR), let's change it here to EL.
	$country = $country == 'GR' ? 'EL' : $country;	

	$result = pmprovat_verify_vat_number($country, $vat_number);

	//clean up any warnings/etc that may have been output above our status we want to send here
	ob_clean();
		
	if($result)
		wp_send_json_success();
	else
		wp_send_json_error();
	
	exit();
}

/**
 * Check self identified country with billing address country and verify VAT number
 */
function pmprovat_check_vat_fields_submission($value)
{	
	global $pmpro_level, $pmpro_european_union, $pmpro_msg, $pmpro_msgt;

	//if free, no need
	if(pmpro_isLevelFree($pmpro_level))
		return $value;
	
	if(!empty($_REQUEST['bcountry']))
		$bcountry = sanitize_text_field($_REQUEST['bcountry']);
	elseif(!empty($_SESSION['bcountry']))
		$bcountry = sanitize_text_field($_SESSION['bcountry']);
	else
		$bcountry = "";

	if(!empty($_REQUEST['eucountry']))
		$eucountry = sanitize_text_field($_REQUEST['eucountry']);
	elseif(!empty($_SESSION['eucountry']))
		$eucountry = sanitize_text_field($_SESSION['eucountry']);
	else
		$eucountry = "";

	if(!empty($_REQUEST['vat_number']))
		$vat_number = sanitize_text_field($_REQUEST['vat_number']);
	elseif(!empty($_SESSION['vat_number']))
		$vat_number = sanitize_text_field($_SESSION['vat_number']);
	else
		$vat_number = "";
	
	$seller_country = get_option('pmprovt_seller_country');

	if(!empty($_REQUEST['show_vat']))
		$show_vat = 1;
	else
		$show_vat = 0;
	
	if(!empty($_REQUEST['geo_ip']))
		$country_by_ip = $_REQUEST['geo_ip'];
	else
		$country_by_ip = '';

	//check that we have values to check
	if(empty($bcountry) && empty($eucountry)){
		$pmpro_msg = __( "You must select a country for us to determine the VAT tax.",  'pmpro-vat-tax' );
		$pmpro_msgt = "pmpro_error";
		$value = false;
	} elseif(empty($vat_number) && $show_vat == 1) {
		$pmpro_msg = __( "VAT number was not entered.",  'pmpro-vat-tax' );
		$pmpro_msgt = "pmpro_error";
		$value = false;
	/* TODO: finding a source for this rule before enabling it
	} elseif($bcountry == $seller_country) {
		$pmpro_msg = __( "VAT number not accepted. Seller in same country",  'pmpro-vat-tax' );
		$pmpro_msgt = "pmpro_error";
		$value = false;
	*/
	//they checked to box for VAT Number and entered the number but didn't
	//actually hit "Apply". If it verifies, go through with checkout
	//otherwise, assume they made a mistake and stop the checkout
	} elseif($show_vat && !pmprovat_verify_vat_number($eucountry, $vat_number)) {
		$pmpro_msg = __( "VAT number was not verifed. Please try again.",  'pmpro-vat-tax' );
		$pmpro_msgt = "pmpro_error";
		$value = false;
	} elseif(!empty($bcountry) && array_key_exists($bcountry, $pmpro_european_union)) { //only if billing country is an EU country
		if($country_by_ip != $bcountry) {
			if($bcountry !== $eucountry) {
				$pmpro_msg = __( "Billing country and country self identification must match", 'pmpro-vat-tax' );
				$pmpro_msgt = "pmpro_error";
				$value = false;
			}
		}
	}

	return $value;
}

add_filter("pmpro_registration_checks", "pmprovat_check_vat_fields_submission");

/**
 * Apply the VAT tax if an EU country is chosen at checkout.
 */
function pmprovat_pmpro_tax($tax, $values, $order)
{
	global $current_user, $pmpro_vat_by_country;

	if(!empty($_REQUEST['vat_number']))
		$vat_number = sanitize_text_field($_REQUEST['vat_number']);
	elseif(!empty($_SESSION['vat_number']))
		$vat_number = sanitize_text_field($_SESSION['vat_number']);
	else
		$vat_number = "";

	//Check the billing country first. If an EU country was selected, we would have made sure it matched the billing country.
	if(!empty($values['billing_country']))
		$eucountry = $values['billing_country'];	
	elseif(!empty($_REQUEST['eucountry']))
		$eucountry = sanitize_text_field($_REQUEST['eucountry']);		//but you might have an eucountry set with no billing country
	elseif(!empty($_SESSION['eucountry']))
		$eucountry = sanitize_text_field($_SESSION['eucountry']);		//ditto if you store in a session to go offsite
	else
		$eucountry = "";

	if(!empty($values['billing_state']))
		$bstate = $values['billing_state'];
	elseif(!empty($_REQUEST['bstate']))
		$bstate = sanitize_text_field($_REQUEST['bstate']);
	elseif(!empty($_SESSION['bstate']))
		$bstate = sanitize_text_field($_SESSION['bstate']);
	else
		$bstate = '';

	$vat_rate = 0;	//default to 0
	
	//check for vat number, validate if needed, set tax rate
	if(!empty($_REQUEST['vat_number_verified']) && $_REQUEST['vat_number_verified'] == "1") {
		$vat_number_verified = true;		
	} elseif(!empty($_SESSION['vat_number_verified']) && $_SESSION['vat_number_verified'] == "1") {
		$vat_number_verified = true;		
	} else {
		$vat_number_verified = false;
		//they didn't use AJAX verify. Verify them now.
		if(!empty($vat_number) && !empty($eucountry) && pmprovat_verify_vat_number($eucountry, $vat_number))
		{
			$vat_rate = 0;
		}
		//they don't have a VAT number.
		elseif(!empty($eucountry) && array_key_exists($eucountry, $pmpro_vat_by_country))
		{			
			$vat_rate = pmprovat_getTaxRate($eucountry, $bstate);		
		}
	}

	//add vat to total taxes
	if(!empty($vat_rate))
		$tax = $tax + round((float)$values['price'] * $vat_rate, 2);

	return apply_filters( 'pmprovat_calculated_taxes', $tax, $values, $vat_rate, $vat_number, $eucountry, $bstate );
}
add_filter("pmpro_tax", "pmprovat_pmpro_tax", 10, 3);

/**
 * Save VAT to Session when going to an offsite gateway.
 */
function pmprovat_pmpro_checkout_before_processing() {
	if(!empty($_REQUEST['eucountry']))
		$_SESSION['eucountry'] = sanitize_text_field($_REQUEST['eucountry']);
	if(!empty($_REQUEST['bcountry']))
		$_SESSION['bcountry'] = sanitize_text_field($_REQUEST['bcountry']);
	if(!empty($_REQUEST['bstate']))
		$_SESSION['bstate'] = sanitize_text_field($_REQUEST['bstate']);
	if(!empty($_REQUEST['show_vat']))
		$_SESSION['show_vat'] = intval($_REQUEST['show_vat']);
	if(!empty($_REQUEST['vat_number']))
		$_SESSION['vat_number'] = sanitize_text_field($_REQUEST['vat_number']);
	if(!empty($_REQUEST['vat_number_verified']))
		$_SESSION['vat_number_verified'] = intval($_REQUEST['vat_number_verified']);
}
add_action('pmpro_checkout_before_processing', 'pmprovat_pmpro_checkout_before_processing');

/**
 * Remove the session vars on checkout
 */
function pmprovat_pmpro_after_checkout() {		
	if(isset($_SESSION['eucountry']))
		unset($_SESSION['eucountry']);
	if(isset($_SESSION['show_vat']))
		unset($_SESSION['show_vat']);
	if(isset($_SESSION['bcountry']))
		unset($_SESSION['bcountry']);
	if(isset($_SESSION['bstate']))
		unset($_SESSION['bstate']);
	if(isset($_SESSION['vat_number']))
		unset($_SESSION['vat_number']);
	if(isset($_SESSION['vat_number_verified']))
		unset($_SESSION['vat_number_verified']);
}
add_action("pmpro_after_checkout", "pmprovat_pmpro_after_checkout");
add_action('pmpro_before_send_to_paypal_standard', 'pmprovat_pmpro_after_checkout', 10);

/**
 * Function to add links to the plugin row meta
 */
function pmprovat_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-vat-tax.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/vat-tax/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-vat-tax' ) ) . '">' . __( 'Docs', 'pmpro-vat-tax' ) . '</a>',
			'<a href="' . esc_url('https://www.paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-vat-tax' ) ) . '">' . __( 'Support', 'pmpro-vat-tax' ) . '</a>',
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
		$seller_country = sanitize_text_field($_REQUEST['pmprovt_seller_country']);
		update_option('pmprovt_seller_country', $seller_country, 'no');
	}
	else
		$seller_country = get_option('pmprovt_seller_country');
	
	?>
			<tr class="pmpro_settings_divider">
				<td colspan="2">
					<?php _e('EU VAT Seller Country', 'pmpro-vat-tax' ); ?>
				</td>
			</tr>
			
			<tr>
			<th scope="row" valign="top">
				<label for="pmprovt_seller_country"><?php _e('Seller Country', 'pmpro-vat-tax' );?>:</label>
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
	global $wpdb, $pmpro_european_union;
	
	if( function_exists( 'pmpro_doing_webhook' ) && pmpro_doing_webhook() ){

		$first_order = $order->get_original_subscription_order( $order->subscription_transaction_id );

		if( !empty( $first_order ) ){

			$vat_number = pmprovat_get_tax_order_notes( 'EU_VAT_NUMBER', $first_order );
			$eucountry = pmprovat_get_tax_order_notes( 'EU_VAT_COUNTRY', $first_order );
			$vat_rate = floatval( pmprovat_get_tax_order_notes( 'EU_VAT_TAX_RATE', $first_order ) );

			$order->subtotal = pmprovat_calculate_subtotal( $order->total, $vat_rate );

			$order->tax = $order->total - $order->subtotal;

		}
	
		$wpdb->update(
			$wpdb->pmpro_membership_orders,
			array( 'tax' => $order->tax, 'subtotal' => $order->subtotal ),
			array( 'id' => $order->id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

	} else {

		if(!empty($_REQUEST['vat_number']))
			$vat_number = sanitize_text_field($_REQUEST['vat_number']);
		elseif(!empty($_SESSION['vat_number']))
			$vat_number = sanitize_text_field($_SESSION['vat_number']);
		else
			$vat_number = '';
		
		//Check the billing country first. If an EU country was selected, we would have made sure it matched the billing country.
		if(!empty($order->billing) && !empty($order->billing->country))
			$eucountry = $order->billing->country;
		elseif(!empty($_REQUEST['bcountry']))
			$eucountry = sanitize_text_field($_REQUEST['bcountry']);
		elseif(!empty($_SESSION['bcountry']))
			$eucountry = sanitize_text_field($_SESSION['bcountry']);
		elseif(!empty($_REQUEST['eucountry']))
			$eucountry = sanitize_text_field($_REQUEST['eucountry']);
		elseif(!empty($_SESSION['eucountry']))
			$eucountry = sanitize_text_field($_SESSION['eucountry']);
		else
			$eucountry = '';
		
		//if country is not in EU, blank it out
		if(!empty($eucountry) && ($eucountry == 'NOTEU' || !array_key_exists($eucountry, $pmpro_european_union)))
			$eucountry = "";
		
		if(!empty($_REQUEST['bstate']))
			$bstate = sanitize_text_field($_REQUEST['bstate']);
		elseif(!empty($_SESSION['bstate']))
			$bstate = sanitize_text_field($_SESSION['bstate']);
		else
			$bstate = '';
		
		$vat_rate = 0;	//default to 0
		
		//check for vat number, validate if needed, set tax rate
		if(!empty($_REQUEST['vat_number_verified']) && $_REQUEST['vat_number_verified'] == "1") {
			$vat_number_verified = true;		
		} elseif(!empty($_SESSION['vat_number_verified']) && $_SESSION['vat_number_verified'] == "1") {
			$vat_number_verified = true;		
		} else {
			$vat_number_verified = false;
			//they didn't use AJAX verify. Verify them now.
			if(!empty($vat_number) && !empty($eucountry) && pmprovat_verify_vat_number($eucountry, $vat_number))
			{
				$vat_rate = 0;
			}
			//they don't have a VAT number.
			elseif(!empty($eucountry) && array_key_exists($eucountry, $pmpro_european_union))
			{			
				$vat_rate = pmprovat_getTaxRate($eucountry, $bstate);		
			}
		}
	}

	
	if($vat_rate === 0) $vat_rate = '';		//we want this blank if 0
	
	$notes = "";
	
	// Strip the country code from the VAT number if it's there. We don't need it.
	$vat_number = preg_replace( '/^' . $eucountry . '/', '', $vat_number );

	if(!empty($vat_number) || !empty($eucountry)) {
		$notes .= "\n---\n";
		$notes .= "{EU_VAT_NUMBER:" . $vat_number . "}\n";
		$notes .= "{EU_VAT_COUNTRY:" . pmprovat_iso2vat($eucountry) . "}\n";
		$notes .= "{EU_VAT_TAX_RATE:" . $vat_rate . "}\n";
		$notes .= "---\n";
	}
	
	$order->notes .= $notes;

	$sqlQuery = "UPDATE $wpdb->pmpro_membership_orders SET notes = '" . esc_sql($order->notes) . "' WHERE id = '" . intval($order->id) . "' LIMIT 1";

	$wpdb->query($sqlQuery);
	
	return $order;
}
add_action('pmpro_added_order', 'pmprovat_pmpro_added_order');

/**
 * Add VAT number and EU Country to order CSV export
 */
function pmprovat_pmpro_orders_csv_extra_columns($columns)
{
	$columns['eu_vat_number'] = 'pmprovat_vat_number_for_orders_csv';
	$columns['eu_vat_country'] = 'pmprovat_eucountry_for_orders_csv';
	$columns['eu_vat_tax_rate'] = 'pmprovat_tax_rate_for_orders_csv';
	return $columns;
}
add_filter('pmpro_orders_csv_extra_columns', 'pmprovat_pmpro_orders_csv_extra_columns');

//call backs
function pmprovat_vat_number_for_orders_csv($order) {
	$vat_number = pmpro_getMatches("/{EU_VAT_NUMBER:([^}]*)}/", $order->notes, true);
	return $vat_number;
}

function pmprovat_eucountry_for_orders_csv($order) {
	$vat_country = pmprovat_iso2vat(pmpro_getMatches("/{EU_VAT_COUNTRY:([^}]*)}/", $order->notes, true));
	return $vat_country;
}

function pmprovat_tax_rate_for_orders_csv($order) {
	$tax_rate = pmpro_getMatches("/{EU_VAT_TAX_RATE:([^}]*)}/", $order->notes, true);
	return $tax_rate;
}

/**
 * Add VAT information to orders for PMPro v3.1+
 *
 * @since 0.8
 *
 * @param array $pmpro_order_single_meta Array of order meta.
 * @param object $pmpro_invoice The order object.
 * @return array $pmpro_order_single_meta Array of order meta.
 */
function pmprovat_pmpro_order_single_meta( $pmpro_order_single_meta, $pmpro_invoice ) {
	global $pmpro_european_union;

	$vat_number	= pmpro_getMatches("/{EU_VAT_NUMBER:([^}]*)}/", $pmpro_invoice->notes, true);
	$vat_country	= pmpro_getMatches("/{EU_VAT_COUNTRY:([^}]*)}/", $pmpro_invoice->notes, true);
	$vat_tax_rate	= pmpro_getMatches("/{EU_VAT_TAX_RATE:([^}]*)}/", $pmpro_invoice->notes, true);

	// Build VAT information.
	$vat_information = '';

	// Add VAT number to the Bill to section.
	/* translators: %s: VAT Number */
	$vat_information .= ! empty( $vat_number ) ? '<br />' . sprintf( esc_html__( 'VAT Number: %s', 'pmpro-vat-tax' ), esc_html( $vat_number ) ) : '';

	// Add VAT country to the Bill to section.
	/* translators: %s: VAT Country */
	$vat_information .= ! empty( $vat_country ) && array_key_exists( pmprovat_vat2iso( $vat_country ), $pmpro_european_union ) ? '<br />' . sprintf( esc_html__( 'VAT Country: %s', 'pmpro-vat-tax' ), esc_html( pmprovat_iso2vat( $vat_country ) ) ) : '';

	// Add VAT rate to the Bill to section.
	/* translators: %s: VAT Rate */
	$vat_information .= ! empty( $vat_tax_rate ) ? '<br />' . sprintf( esc_html__( 'VAT Rate: %s', 'pmpro-vat-tax' ), esc_html( $vat_tax_rate ) ) : '';

	// Add VAT information to the Bill to section.
	$pmpro_order_single_meta['bill_to']['value'] .= $vat_information;
	return $pmpro_order_single_meta;
}
add_action( 'pmpro_order_single_meta', 'pmprovat_pmpro_order_single_meta', 10, 2 );

/**
 * Legacy method to add VAT fields to orders.
 */
function pmprovat_pmpro_invoice_bullets_bottom( $pmpro_invoice ) {
	// Check if PMPro is v3.1+ or legacy. TODO: Remove this later once we are confident most people are using 3.1
	// Don't show if we are already using the v3.1+ hook to show VAT information.
	if ( version_compare( PMPRO_VERSION, '3.1' ) >= 0 ) {
		return;
	}

	global $pmpro_european_union;
	
	$vat_number	= pmpro_getMatches("/{EU_VAT_NUMBER:([^}]*)}/", $pmpro_invoice->notes, true);
	$vat_country	= pmpro_getMatches("/{EU_VAT_COUNTRY:([^}]*)}/", $pmpro_invoice->notes, true);
	$vat_tax_rate	= pmpro_getMatches("/{EU_VAT_TAX_RATE:([^}]*)}/", $pmpro_invoice->notes, true);
	if(!empty($vat_number)) {
		?><li><strong><?php _e('VAT Number: ', 'pmpro-vat-tax');?></strong><?php echo $vat_number;?></li><?php
	}
	if(!empty($vat_country) && array_key_exists(pmprovat_vat2iso($vat_country), $pmpro_european_union)) {
		?><li><strong><?php _e('VAT Country: ', 'pmpro-vat-tax');?></strong><?php echo pmprovat_iso2vat($vat_country);?></li><?php
	}
	if(!empty($vat_tax_rate)) {
		?><li><strong><?php _e('VAT Tax Rate: ', 'pmpro-vat-tax');?></strong><?php echo $vat_tax_rate;?></li><?php
	}
}
add_action('pmpro_invoice_bullets_bottom', 'pmprovat_pmpro_invoice_bullets_bottom');

/**
 * Convert ISO country designation to EU Vat country designation
 * 
 * @param string $iso_code ISO country code
 * @return string EU Vat country code
 */
function pmprovat_iso2vat($iso_code)
{
	$vat_country_code = $iso_code;
		
	if($iso_code == 'GR')
		$vat_country_code = 'EL';
	
	return $vat_country_code;
}

/**
 * Convert EU Vat country designation to ISO country designation
 * 
 * @param string $vat_country_code EU Vat country code
 * @return string ISO country code
 */
function pmprovat_vat2iso($vat_country_code)
{
	$iso_code = $vat_country_code;	
	if($vat_country_code == 'EL')
		$iso_code = 'GR';
	
	return $iso_code;
}

function pmprovat_determine_country_from_ip()
{
	global $country_from_ip;
	
	//check if the GEO IP Detect plugin is active
	if(!defined('GEOIP_DETECT_VERSION'))
		return false;
	
	if(!isset($country_from_ip))
	{
		//get the country
		$record = geoip_detect2_get_info_from_current_ip();
		$country_from_ip = $record->country->isoCode;

		if(empty($country_from_ip))
			$country_from_ip = false;
	}
	
	return $country_from_ip;
}

function pmprovat_pmpro_apply_vat_to_level($level, $vat_rate)
{
	$level->initial_payment = $level->initial_payment * (1 + $vat_rate);
	$level->trial_amount = $level->trial_amount * (1 + $vat_rate);
	$level->billing_amount = $level->billing_amount * (1 + $vat_rate);
	
	return $level;
}

function pmprovat_init_load_session_vars($params)
{
	if(empty($_REQUEST['vat_number_verified']) && !empty($_SESSION['vat_number_verified']))
	{
		$_REQUEST['vat_number_verified'] = $_SESSION['vat_number_verified'];
		$_REQUEST['vat_number'] = $_SESSION['vat_number'];
	}
	
	return $params;
}

add_action('init', 'pmprovat_init_load_session_vars', 5);


function pmprovat_get_tax_order_notes( $value_name, $order ){

	$value = pmpro_getMatches( "/{".$value_name.":([^}]*)}/", $order->notes, true );
	
	return $value;

}

function pmprovat_calculate_subtotal( $final_amount, $tax_rate ){

	$original_amount = $final_amount / ( 1 + $tax_rate );

	return round( $original_amount, apply_filters( 'pmprovat_rounding', 2 ) );

}
