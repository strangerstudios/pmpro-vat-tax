<?php

/**
 * Function to add VAT Number to order notes.
 *
 * @param MemberOrder $order that was added.
 */
function pmprovat_pmpro_added_order($order)
{
	global $wpdb, $pmpro_european_union;
	
	if( function_exists( 'pmpro_doing_webhook' ) && pmpro_doing_webhook() ){

		$first_order = $order->get_original_subscription_order( $order->subscription_transaction_id );

		if( !empty( $first_order ) ){

			$vat_number = pmprovat_get_vat_number_for_order( $first_order );
			$eucountry = pmprovat_get_country_for_order( $first_order );
			$vat_rate = floatval( pmprovat_get_tax_rate_for_order( $first_order ) );

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
}
add_action('pmpro_added_order', 'pmprovat_pmpro_added_order');

/**
 * Add VAT number and EU Country to order CSV export.
 *
 * @param array $columns in order CSV export.
 * @return array
 */
function pmprovat_pmpro_orders_csv_extra_columns( $columns ) {
	$columns['eu_vat_number'] = 'pmprovat_get_vat_number_for_order';
	$columns['eu_vat_country'] = 'pmprovat_get_country_for_order';
	$columns['eu_vat_tax_rate'] = 'pmprovat_get_tax_rate_for_order';
	return $columns;
}
add_filter('pmpro_orders_csv_extra_columns', 'pmprovat_pmpro_orders_csv_extra_columns');

/**
 * Add VAT fields to invoices.
 *
 * @param MemberOrder $pmpro_invoice being shown.
 */
function pmprovat_pmpro_invoice_bullets_bottom($pmpro_invoice) {
	global $pmpro_european_union;
	
	$vat_number	  = pmprovat_get_vat_number_for_order( $pmpro_invoice );
	$vat_country  = pmprovat_get_country_for_order( $pmpro_invoice );
	$vat_tax_rate = pmprovat_get_tax_rate_for_order( $pmpro_invoice );
	if ( ! empty( $vat_number ) ) {
		?><li><strong><?php _e('VAT Number: ', 'pmprovat');?></strong><?php echo $vat_number;?></li><?php
	}
	if ( ! empty( $vat_country ) ) {
		?><li><strong><?php _e('VAT Country: ', 'pmprovat');?></strong><?php echo $vat_country;?></li><?php
	}
	if ( ! empty( $vat_tax_rate ) ) {
		?><li><strong><?php _e('VAT Tax Rate: ', 'pmprovat');?></strong><?php echo $vat_tax_rate;?></li><?php
	}
}
add_action('pmpro_invoice_bullets_bottom', 'pmprovat_pmpro_invoice_bullets_bottom');
