<?php

/**
 * Add VAT settings to Payment Settings page in admin.
 */
function pmprovat_pmpro_payment_option_fields()
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
					<?php _e('EU VAT Seller Country', 'pmprovat' ); ?>
				</td>
			</tr>
			
			<tr>
			<th scope="row" valign="top">
				<label for="pmprovt_seller_country"><?php _e('Seller Country', 'pmprovat' );?>:</label>
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
add_action( 'pmpro_payment_option_fields', 'pmprovat_pmpro_payment_option_fields', 10 );

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
        wp_register_script('pmprovat', plugins_url( 'js/pmprovat.js', dirname(__FILE__) ), array('jquery'), PMPRO_VAT_TAX_VERSION);

		//get values
		wp_localize_script('pmprovat', 'pmprovat',
			array(
				'eu_array' => array_keys($pmpro_european_union),
				'ajaxurl' => admin_url('admin-ajax.php'),
				'timeout' => apply_filters("pmpro_ajax_timeout", 5000, 'applydiscountcode'),
				'seller_country' => get_option('pmprovt_seller_country'),
				'verified_text' => __('VAT number was verifed', 'pmprovat'),
				'not_verified_text' => __('VAT number was not verifed. Please try again.', 'pmprovat'),				
				'hide_vat_same_country' => apply_filters( 'pmprovat_hide_vat_if_same_country', true ),
			)
		);
		//enqueue
		wp_enqueue_script('pmprovat');
	}
}
add_action('wp_enqueue_scripts', 'pmprovat_enqueue_scripts');

/**
 * Add VAT tax info to level cost text at checkout.
 *
 * @param string $cost being shown.
 * @param PMPro_Membership_Level $level being purchased.
 */
function pmprovat_pmpro_level_cost_text($cost, $level)
{
	global $pmpro_pages;
	if( is_page( $pmpro_pages["checkout"] ) && !pmpro_isLevelFree($level) )
		$cost .= " " . __("Members in the EU will be charged a VAT tax.", "pmprovat");

	return $cost;
}
add_filter("pmpro_level_cost_text", "pmprovat_pmpro_level_cost_text", 10, 2);

/**
 * Show VAT country and number field at checkout.
 */
function pmprovat_pmpro_checkout_boxes()
{
	global $pmpro_level, $pmpro_european_union, $pmpro_review;
	
	//if free, no need
	if(pmpro_isLevelFree($pmpro_level))
		return;
	
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
<div id="pmpro_vat_table" class="pmpro_checkout">
	<hr />
	<h3>
		<span class="pmpro_checkout-h3-name"><?php _e('European Union Residents VAT', 'pmprovat');?></span>
	</h3>
	<div class="pmpro_checkout-fields">
		<div id="vat_confirm_country" class="pmpro_checkout-field">
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
			<?php } elseif(!empty($eucountry)) { ?>
				<span><?php echo $pmpro_european_union[$eucountry];?></span>
			<?php } ?>
		</div>
		<input type="hidden" id="geo_ip" name="geo_ip" value=<?php echo pmprovat_determine_country_from_ip(); ?>>
		<?php if(!$pmpro_review) { ?>		
			<div id="vat_have_number" class="pmpro_checkout-field pmpro_checkout-field-checkbox">
				<input id="show_vat" type="checkbox" name="show_vat" value="1" <?php checked($show_vat, 1);?>>
				<label for="show_vat" class="pmpro_clickable"><?php _e('I have a VAT number', 'pmprovat');?></label>
			</div> <!-- end vat_have_number -->
			<div id="vat_number_validation_tr" class="pmpro_checkout-field">
				<label for="vat_number"><?php _e('Vat Number', 'pmprovat');?></label>
				<input id="vat_number" name="vat_number" class="input" type="text"  size="20" value="<?php echo esc_attr($vat_number);?>" />
				<input type="button" name="vat_number_validation_button" id="vat_number_validation_button" value="<?php _e('Apply', 'pmpro');?>" />
				<p id="vat_number_message" class="pmpro_message" style="display: none;"></p>
			</div> <!-- end vat_number_validation_tr -->
		<?php } elseif($pmpro_review && !empty($vat_number)) { ?>
			<div class="pmpro_checkout-field">
				<label for="vat_number"><?php _e('Vat Number', 'pmprovat');?></label>
				<?php echo $vat_number;?>
			</div> <!-- end pmpro_checkout-field -->
		<?php } ?>
	</div> <!-- end pmpro_checkout-fields -->
</div> <!-- end pmpro_vat_table -->	
<?php
}
add_action("pmpro_checkout_after_billing_fields", "pmprovat_pmpro_checkout_boxes");

/**
 * AJAX callback to check the VAT number.
 */
function pmprovat_vat_verification_ajax_callback() {
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
add_action( 'wp_ajax_nopriv_pmprovat_vat_verification_ajax_callback', 'pmprovat_vat_verification_ajax_callback' );
add_action( 'wp_ajax_pmprovat_vat_verification_ajax_callback', 'pmprovat_vat_verification_ajax_callback' );

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
		$pmpro_msg = __( "You must select a country for us to determine the VAT tax.",  'pmprovat' );
		$pmpro_msgt = "pmpro_error";
		$value = false;
	} elseif(empty($vat_number) && $show_vat == 1) {
		$pmpro_msg = __( "VAT number was not entered.",  'pmprovat' );
		$pmpro_msgt = "pmpro_error";
		$value = false;
	/* TODO: finding a source for this rule before enabling it
	} elseif($bcountry == $seller_country) {
		$pmpro_msg = __( "VAT number not accepted. Seller in same country",  'pmprovat' );
		$pmpro_msgt = "pmpro_error";
		$value = false;
	*/
	//they checked to box for VAT Number and entered the number but didn't
	//actually hit "Apply". If it verifies, go through with checkout
	//otherwise, assume they made a mistake and stop the checkout
	} elseif ( $show_vat && ! pmprovat_verify_vat_number( $eucountry, $vat_number ) ) {
		$pmpro_msg = __( "VAT number was not verifed. Please try again.",  'pmprovat' );
		$pmpro_msgt = "pmpro_error";
		$value = false;
	} elseif( ! empty( $bcountry ) && array_key_exists( $bcountry, $pmpro_european_union ) ) { //only if billing country is an EU country
		if($country_by_ip != $bcountry) {
			if($bcountry !== $eucountry) {
				$pmpro_msg = __( "Billing country and country self identification must match", 'pmprovat' );
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
 *
 * @param float $tax Amount of tax that is already present on the order.
 * @param array $values Various information from the MemberOrder object.
 * @param MemberOrder $order that tax is being calculated for.
 * @return float Amount of tax that should be recorded in the order.
 */
function pmprovat_pmpro_tax($tax, $values, $order) {
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
 * Save VAT to session when going to an offsite gateway.
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
 * Load VAT data from session.
 */
function pmprovat_init_load_session_vars() {
	if ( empty( $_REQUEST['vat_number_verified'] ) && ! empty( $_SESSION['vat_number_verified'] ) ) {
		$_REQUEST['vat_number_verified'] = $_SESSION['vat_number_verified'];
		$_REQUEST['vat_number'] = $_SESSION['vat_number'];
	}
}

add_action( 'init', 'pmprovat_init_load_session_vars', 5 );

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
