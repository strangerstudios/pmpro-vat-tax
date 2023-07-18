<?php
/*
Plugin Name: Paid Memberships Pro - VAT Tax
Plugin URI: https://www.paidmembershipspro.com/add-ons/vat-tax/
Description: Calculate VAT tax at checkout and allow customers with a VAT Number lookup for VAT tax exemptions in EU countries.
Version: 0.7.1
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Text Domain: pmprovat
*/

//uses: https://github.com/herdani/vat-validation/blob/master/vatValidation.class.php
//For EU VAT number checking.

define( 'PMPRO_VAT_TAX_VERSION', '0.7' );
define( 'PMPROVAT_DIR', dirname( __FILE__ ) );

require_once( PMPROVAT_DIR . '/includes/functions.php' );
require_once( PMPROVAT_DIR . '/includes/checkout.php' );
require_once( PMPROVAT_DIR . '/includes/orders.php' );
require_once( PMPROVAT_DIR . '/includes/deprecated.php' );

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
 * Set up tax rates and countries.
 */
function pmprovat_init() {
	global $pmpro_vat_by_country;

	//Uses ISO country designations
	$pmpro_vat_by_country = array(
		"BE" => 0.21,
		"BG" => 0.20,
		"CZ" => 0.21,
		"DK" => 0.25,
		"DE" => 0.19,
		"EE" => 0.20,
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
		"SK" => 0.20,
		"FI" => 0.24,
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
}
add_action("init", "pmprovat_init");

/**
 * Function to add links to the plugin row meta
 */
function pmprovat_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-vat-tax.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/vat-tax/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('https://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmprovat' ) ) . '">' . __( 'Support', 'pmprovat' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprovat_plugin_row_meta', 10, 2);
