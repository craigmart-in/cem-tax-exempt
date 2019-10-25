<?php

/**
 * Plugin Name: CEM Tax Exempt
 * Plugin URI: https://github.com/craigmart-in/
 * Description: Tax Exempt Form during checkout.
 * Version: 1.0.2
 * Author: Craig Martin
 * Author URI: https://craigmart.in
 * Text Domain: cem-tax-exempt
 */


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Check if WooCommerce is active
if ( ! cem_tax_exempt::is_woocommerce_active() )
	return;

/**
 * The cem_tax_exempt global object
 * @name $cem_tax_exempt
 * @global cem_tax_exempt $GLOBALS['cem_tax_exempt']
 */
$GLOBALS['cem_tax_exempt'] = new cem_tax_exempt();

class cem_tax_exempt {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'taxexempt_scripts') );

        add_filter( 'woocommerce_checkout_fields' , array( $this, 'taxexempt_checkout_fields') );
        add_action('woocommerce_before_order_notes', array( $this, 'taxexempt_before_order_notes') );
		add_action('woocommerce_before_order_notes', array( $this, 'howheard_before_order_notes') );

        add_action( 'woocommerce_checkout_update_order_review', array( $this, 'taxexempt_checkout_update_order_review' ));
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'taxexempt_checkout_update_order_meta') );

        // add custom field to invoice email
        add_action( 'woocommerce_email_after_order_table', array( $this, 'taxexempt_custom_invoice_fields'), 30, 1 );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'howheard_custom_invoice_fields'), 31, 2 );
    }

    public function taxexempt_scripts() {
        global $post;
        
        wp_register_script( 'cem-tax-exempt-script',  plugins_url( 'cem-tax-exempt.js' , __FILE__ ) , array('jquery'), '1.0', false );

        wp_register_style( 'cem-tax-exempt-style', plugins_url( 'cem-tax-exempt.css' , __FILE__ ), true);

        if (is_checkout()) {
            wp_enqueue_style( 'cem-tax-exempt-style' );

            wp_enqueue_script('cem-tax-exempt-script');
        }
    }

    public function taxexempt_checkout_fields( $fields ) {
        //unset($fields['order']['order_comments']);

        return $fields;
    }

    public function taxexempt_before_order_notes( $checkout ) {

        echo '<div style="clear: both"></div>
        </br>
        <div class="cem-tax-exempt-wrapper">
        <h3>Tax Exempt Details</h3>';

        woocommerce_form_field( 'tax_exempt_checkbox', array(
            'type'          => 'checkbox',
            'class'         => array('taxexempt'),array( 'form-row-wide', 'address-field' ),
            'label'         => __('Tax Exempt'),
            ), $checkout->get_value( 'tax_exempt_checkbox' ));

        woocommerce_form_field( 'tax_exempt_name', array(
            'type'          => 'text',
            'class'         => array('form-row-first', 'taxexempt', 'textbox', 'hidden'),
            'label'         => __('Tax Exempt Name'),
            ), $checkout->get_value( 'tax_exempt_name' ));

        woocommerce_form_field( 'tax_exempt_id', array(
            'type'          => 'text',
            'class'         => array('form-row-last', 'taxexempt', 'textbox', 'hidden', 'update_totals_on_change'),
            'label'         => __('Tax Exempt Id'),
            ), $checkout->get_value( 'tax_exempt_id' ));

        echo '</div>';
    }
	
	public function howheard_before_order_notes( $checkout ) {

        echo '<div style="clear: both"></div>
        </br>
        <div>';

        woocommerce_form_field( 'how_heard_about_us', array(
            'type'          => 'textarea',
            'class'         => array('form-row notes', 'howheard', 'textarea'),
            'label'         => __('How did you hear about us?'),
			'placeholder'	=> 'For example, from a conference.'
            ), $checkout->get_value( 'how_heard_about_us' ));

        echo '</div>';
    }

    public function taxexempt_checkout_update_order_review( $post_data ) {
        global $woocommerce;

        $woocommerce->customer->set_is_vat_exempt(FALSE);

        parse_str($post_data);

        if ( isset($tax_exempt_checkbox) && isset($tax_exempt_id) && $tax_exempt_checkbox == '1' && !empty($tax_exempt_id))
            $woocommerce->customer->set_is_vat_exempt(true);                
    }

    public function taxexempt_checkout_update_order_meta( $order_id ) {
        global $woocommerce;

        if ( $woocommerce->customer->is_vat_exempt() ) {
            if ($_POST['tax_exempt_name'])
            update_post_meta( $order_id, 'Tax Exempt Name', esc_attr($_POST['tax_exempt_name']));

        if ($_POST['tax_exempt_id'])
            update_post_meta( $order_id, 'Tax Exempt Id', esc_attr($_POST['tax_exempt_id']));
        }
		
		update_post_meta( $order_id, 'How did you hear about us?', esc_attr($_POST['how_heard_about_us']));
    }

    public function taxexempt_custom_invoice_fields( $order ) {
		$taxExemptName = get_post_meta( $order->get_id(), 'Tax Exempt Name', true );
		
		if ($taxExemptName) {
        ?>
        <h3>Tax Exempt Details</h3>
        <p><strong><?php _e('Tax Exempt Name:', 'woocommerce'); ?></strong> <?php echo $taxExemptName; ?></p>
        <p><strong><?php _e('Tax Exempt Id:', 'woocommerce'); ?></strong> <?php echo get_post_meta( $order->get_id(), 'Tax Exempt Id', true ); ?></p>
        <?php
		}
    }
	
	public function howheard_custom_invoice_fields( $order, $sent_to_admin ) {
		if ( $sent_to_admin == false ) {
            return;
        }
        ?>
        <h3>How did you hear about us?</h3>
        <p><?php echo get_post_meta( $order->get_id(), 'How did you hear about us?', true ); ?></p>
        <?php
    }
    
    
    /** Helper Methods ******************************************************/


    /**
     * Checks if WooCommerce is active
     *
     * @since  1.0
     * @return bool true if WooCommerce is active, false otherwise
     */
    public static function is_woocommerce_active() {

        $active_plugins = (array) get_option( 'active_plugins', array() );

        if ( is_multisite() )
            $active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

        return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
    }
}
?>
