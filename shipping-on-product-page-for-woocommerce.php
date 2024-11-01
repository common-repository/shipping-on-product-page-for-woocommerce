<?php

/**
 * Plugin Name: Shipping on Product Page for WooCommerce
 * Plugin URI: http://www.codecanyon.net/user/portfolio/wpshowcase?ref=wpshowcase
 * Description: Displays the cost of shipping the product beneath the add to cart button. The shipping cost updates when the quantity changes.
 * Author: WPShowCase
 * Version: 1.1
 * Author URI: http://www.codecanyon.net/user/wpshowcase?ref=wpshowcase
 * WC tested up to: 3.3.2
 */
class SOPP_Shipping_On_Product_Page {

    /**
     * Actions
     */
    function __construct() {
        add_action( 'wp_ajax_sopp_get_shipping_info', array( $this, 'wp_ajax_sopp_get_shipping_info' ) );
        add_action( 'wp_ajax_nopriv_sopp_get_shipping_info', array( $this, 'wp_ajax_sopp_get_shipping_info' ) );
        add_action( 'woocommerce_before_single_product_summary', array( $this, 'woocommerce_before_single_product_summary' ) );
        add_action( 'woocommerce_after_single_product_summary', array( $this, 'woocommerce_after_single_product_summary' ) );
    }

    function woocommerce_before_single_product_summary() {
        add_action( 'woocommerce_get_price_html', array( $this, 'woocommerce_get_price_html' ) );
    }

    function woocommerce_after_single_product_summary() {
        remove_action( 'woocommerce_get_price_html', array( $this, 'woocommerce_get_price_html' ) );
    }

    /**
     * Places html after cart button
     */
    function woocommerce_get_price_html( $html ) {
        ob_start();
        print '<div class="sopp-shipping-costs">';
        print '</div>';
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-blockui' );
        wp_enqueue_script( 'sopp-product-page', plugins_url( 'assets/js/product-page.js', __FILE__ ) );
        wp_localize_script( 'sopp-product-page', 'SOPP_product_page_settings', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
        return $html . ob_get_clean();
    }

    /**
     * ajax
     */
    function wp_ajax_sopp_get_shipping_info() {
        $quantity = intval( $_POST[ 'sopp_quantity' ] );
        $product_id = intval( $_POST[ 'sopp_product_id' ] );
        $this->print_shipping_costs( $this->get_shipping( $product_id, $quantity ) );
        exit();
    }

    /**
     * Inner html
     */
    function print_shipping_costs( $shipping_cost ) {
        if ( $shipping_cost === 9999999999 ) {
            _e( 'Shipping Unavailable', 'shipping-costs-on-product-page-for-woocommerce' );
        } else {
            print sprintf( __( '(Shipping: %s)', 'shipping-costs-on-product-page-for-woocommerce' ), wc_price( $shipping_cost ) );
        }
    }

    /**
     * Gets the shipping cost
     */
    function get_shipping( $product_id, $quantity ) {
        $notices = WC()->session->get( 'wc_notices', array() );
        $packages = WC()->cart->get_shipping_packages();
        if ( !empty( $packages ) ) {
            foreach ( $packages as $package_id => $package ) {
                if ( empty( $package[ 'destination' ] ) ) {
                    $packages[ $package_id ][ 'destination' ] = array( 'country' => WC()->customer->get_shipping_country() );
                }
            }
        }
        $shipping_rates_costs_before_add_to_cart = array();
        $shipping_rates_array = WC()->shipping->calculate_shipping_for_package( $packages[ 0 ] );
        if ( !empty( $shipping_rates_array[ 'rates' ] ) ) {
            $shipping_rates_costs_before_add_to_cart = $shipping_rates_array[ 'rates' ];
        }
        $cart_item_data = array();
        $variation_id = 0;
        $cart_item_data = ( array ) apply_filters( 'woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id, $quantity );
        $cart_id = WC()->cart->generate_cart_id( $product_id, $variation_id, $variation_id, $cart_item_data );
        $cart_item_key = WC()->cart->find_product_in_cart( $cart_id );
        $quantity_before_add_to_cart = 0;
        if ( !empty( WC()->cart->cart_contents[ $cart_item_key ] ) ) {
            $quantity_before_add_to_cart = intval( WC()->cart->cart_contents[ $cart_item_key ][ 'quantity' ] );
            WC()->cart->set_quantity( $cart_item_key, $quantity_before_add_to_cart
                    + $quantity );
        } else {
            $cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity );
        }
        $packages = WC()->cart->get_shipping_packages();
        if ( !empty( $packages ) ) {
            foreach ( $packages as $package_id => $package ) {
                if ( empty( $package[ 'destination' ] ) ) {
                    $packages[ $package_id ][ 'destination' ] = array( 'country' => WC()->customer->get_shipping_country() );
                }
            }
        }
        $shipping_rates = array();
        $shipping_rates_array = WC()->shipping->calculate_shipping_for_package( $packages[ 0 ] );
        if ( !empty( $shipping_rates_array[ 'rates' ] ) ) {
            $shipping_rates = $shipping_rates_array[ 'rates' ];
        }
        $min_shipping_cost = 9999999999;
        if ( !empty( $shipping_rates ) ) {
            foreach ( $shipping_rates as $shipping_rate_id => $shipping_rate ) {
                $cost = floatval( $shipping_rate->cost );
                if ( !empty( $shipping_rates_costs_before_add_to_cart[ $shipping_rate_id ] ) ) {
                    $cost -= floatval( $shipping_rates_costs_before_add_to_cart[ $shipping_rate_id ]->cost );
                }
                if ( $min_shipping_cost > $cost ) {
                    $min_shipping_cost = $cost;
                }
            }
        }
        WC()->cart->set_quantity( $cart_item_key, $quantity_before_add_to_cart );
        WC()->session->set( 'wc_notices', $notices );
        return $min_shipping_cost;
    }

}

$sopp_shipping_on_product_page = new SOPP_Shipping_On_Product_Page();
