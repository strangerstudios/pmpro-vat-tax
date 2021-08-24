<?php

/**
 * Get a VAT tax rate from a country code.
 *
 * @param string $country to get tax rate for.
 * @param string|null $state to get tax rate for.
 * @return float tax rate.
 */
function pmprovat_getTaxRate( $country, $state = NULL ) {
	global $pmpro_vat_by_country;
	
	//non-EU
	if ( empty( $pmpro_vat_by_country[$country] ) ) {
		return 0;
    }
	
	//default to 0
	$vat_rate = 0;
	
	//state VAT like British Columbia Canada
	if ( is_array( $pmpro_vat_by_country[$country] ) ) {
		if( ! empty( $state ) && array_key_exists( $state, $pmpro_vat_by_country[$country] ) ) {
			$vat_rate = $pmpro_vat_by_country[$country][$state];
		}
	//single VAT for country
	} else {	
		$vat_rate = $pmpro_vat_by_country[$country];
	}
	
	return $vat_rate;
}

/**
 * Get VAT Validation Class.
 *
 * @return vatValidation
 */
function pmprovat_getVATValidation() {
	global $vatValidation;
	if ( empty( $vatValidation ) ) {
		if ( ! class_exists( 'vatValidation' ) ) {
			require_once( PMPROVAT_DIR . '/includes/lib/vat-validation/vatValidation.class.php' );
		}
		$vatValidation = new vatValidation( array( 'debug' => false ) );
	}

	return $vatValidation;
}

/**
 * Helper function to verify a VAT number.
 *
 * @param string $country ISO designation that vat number is from.
 * @param string $vat_number to verify.
 * @return bool whether VAT number is valid.
 */
function pmprovat_verify_vat_number($country, $vat_number)
{
	/**
	 * GB will be verified based on a regex to provide improved validation
	 * for UK VAT numbers after Brexit.
	 */
	if ( $country === 'GB' ){
		preg_match('/^([GB])*(([1-9]\d{8})|([1-9]\d{11}))$/', $vat_number, $matches);
		$uk_vat_is_valid = isset($matches) && count($matches) > 0;
		return $uk_vat_is_valid;
	}
	
	if ( apply_filters('pmprovat_skip_validation', false) ){
		return true;
	}

	$vatValidation = pmprovat_getVATValidation();
		
	if ( empty( $country ) || empty( $vat_number ) ) {
		$result = false;
	} else {
		$result = $vatValidation->check( $country, $vat_number );
	}

	$result = apply_filters( 'pmprovat_custom_vat_number_validate', $result );

	return $result;
}

/**
 * Get the VAT number used for a particular order.
 *
 * @param MemberOrder $order to get VAT number for.
 * @return string VAT number used.
 */
function pmprovat_get_vat_number_for_order( $order ) {
    $vat_number = pmpro_getMatches( "/{EU_VAT_NUMBER:([^}]*)}/", $order->notes, true );
	return $vat_number;
}

/**
 * Get the EU country used for a particular order.
 *
 * @param MemberOrder $order to get contry for.
 * @return string country used.
 */
function pmprovat_get_country_for_order( $order ) {
    $vat_country = pmprovat_iso2vat( pmpro_getMatches( "/{EU_VAT_COUNTRY:([^}]*)}/", $order->notes, true ) );
	return $vat_country;
}

/**
 * Get the tax rate used for a particular order.
 *
 * @param MemberOrder $order to get tax rate for.
 * @return string tax rate used.
 */
function pmprovat_get_tax_rate_for_order( $order ) {
    $tax_rate = pmpro_getMatches( "/{EU_VAT_TAX_RATE:([^}]*)}/", $order->notes, true );
	return $tax_rate;
}

/**
 * Convert ISO country designation to EU Vat country designation
 * 
 * @param string $iso_code ISO country code
 * @return string EU Vat country code
 */
function pmprovat_iso2vat( $iso_code ) {
	$vat_country_code = $iso_code;
		
	if( $iso_code == 'GR' )
		$vat_country_code = 'EL';
	
	return $vat_country_code;
}

/**
 * Convert EU Vat country designation to ISO country designation
 * 
 * @param string $vat_country_code EU Vat country code
 * @return string ISO country code
 */
function pmprovat_vat2iso( $vat_country_code ) {
	$iso_code = $vat_country_code;	
	if( $vat_country_code == 'EL' )
		$iso_code = 'GR';
	
	return $iso_code;
}

/**
 * Get the current user's country from their IP address.
 * Requires GEO IP Detect plugin to be active.
 */
function pmprovat_determine_country_from_ip() {
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

/**
 * Apply a vat rate to a membership level's prices.
 *
 * @param PMPro_Membership_Level $level to apply VAT tax to.
 * @param float $vat_rate to apply to level prices.
 */
function pmprovat_pmpro_apply_vat_to_level( $level, $vat_rate ) {
	$level->initial_payment = $level->initial_payment * ( 1 + $vat_rate );
	$level->trial_amount = $level->trial_amount * ( 1 + $vat_rate );
	$level->billing_amount = $level->billing_amount * ( 1 + $vat_rate );
	
	return $level;
}

/**
 * Calculate the subtotal for an order based on the total and the tax
 * rate applied.
 *
 * @param float $final_amount charged for the order.
 * @param float $tax_rate charged for the order.
 * @param float subtotal.
 */
function pmprovat_calculate_subtotal( $final_amount, $tax_rate ) {

	$original_amount = $final_amount / ( 1 + $tax_rate );

	return round( $original_amount, apply_filters( 'pmprovat_rounding', 2 ) );

}
