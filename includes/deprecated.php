<?php

/**
 * @deprecated 1.0. Use pmprovat_get_vat_number_for_order() instead.
 */
function pmprovat_vat_number_for_orders_csv( $order ) {
	return pmprovat_get_vat_number_for_order( $order );
}

/**
 * @deprecated 1.0. Use pmprovat_get_country_for_order() instead.
 */
function pmprovat_eucountry_for_orders_csv( $order ) {
	return pmprovat_get_country_for_order( $order );
}

/**
 * @deprecated 1.0. Use pmprovat_get_country_for_order() instead.
 */
function pmprovat_tax_rate_for_orders_csv( $order ) {
	return pmprovat_get_tax_rate_for_order( $order );
}

/**
 * @deprecated 1.0. Use pmprovat_get_vat_number_for_order(), pmprovat_get_country_for_order(),
 * or pmprovat_get_tax_rate_for_order() instead.
 */
function pmprovat_get_tax_order_notes( $value_name, $order ){
	$value = pmpro_getMatches( "/{" . $value_name . ":([^}]*)}/", $order->notes, true );
	return $value;
}