<?php
/*
Plugin Name: Paid Memberships Pro - VAT Tax
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-vat-tax/
Description: Calculate VAT tax at checkout and allow customers with a VAT Number to avoid the tax.
Version: .1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

//add tax info to cost text.
function pmprovat_pmpro_tax($tax, $values, $order)
{  	
	$tax = round((float)$values[price] * 0.07, 2);		
	return $tax;
}
 
function pmprovat_pmpro_level_cost_text($cost, $level)
{
	//only applicable for levels > 1
	$cost .= " Members in the EU will be charged a VAT tax.";
	
	return $cost;
}
add_filter("pmpro_level_cost_text", "pmprovat_pmpro_level_cost_text", 10, 2);
 
//add BC checkbox to the checkout page
function pmprovat_pmpro_checkout_boxes()
{
?>
<table id="pmpro_pricing_fields" class="pmpro_checkout" width="100%" cellpadding="0" cellspacing="0" border="0">
<thead>
	<tr>
		<th>
			European Union Residents
		</th>						
	</tr>
</thead>
<tbody>                
	<tr>	
		<td>
			<div>				
				
			</div>				
		</td>
	</tr>
</tbody>
</table>
<?php
}
add_action("pmpro_checkout_boxes", "pmprovat_pmpro_checkout_boxes");
 
//update tax calculation if buyer is in EU
function pmprovat_region_tax_check()
{
	//check request and session
	if(isset($_REQUEST['taxregion']))
	{
		//update the session var
		$_SESSION['taxregion'] = $_REQUEST['taxregion'];	
		
		//not empty? setup the tax function
		if(!empty($_REQUEST['taxregion']))
			add_filter("pmpro_tax", "pmprovat_pmpro_tax", 10, 3);
	}
	elseif(!empty($_SESSION['taxregion']))
	{
		//add the filter
		add_filter("pmpro_tax", "pmprovat_pmpro_tax", 10, 3);
	}
	else
	{
		//check state and country
		if(!empty($_REQUEST['bstate']) && !empty($_REQUEST['bcountry']))
		{
			$bstate = trim(strtolower($_REQUEST['bstate']));
			$bcountry = trim(strtolower($_REQUEST['bcountry']));
			if(($bstate == "bc" || $bstate == "british columbia") && $bcountry = "ca")
			{
				//billing address is in BC
				add_filter("pmpro_tax", "pmprovat_pmpro_tax", 10, 3);
			}
		}
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
