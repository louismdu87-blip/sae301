<?php
/* Itération 2. */
defined( 'ABSPATH' ) || exit;

function buissiere_enqueue_styles() {
    $styles = wp_styles();
    foreach ( array( 'storefront-style' => 'storefront', 'storefront-icons' => 'icons', 'storefront-woocommerce-style' => 'woocommerce', 'storefront-gutenberg-blocks' => 'blocks' ) as $handle => $file ) {
        if ( isset( $styles->registered[ $handle ] ) ) {
            $styles->registered[ $handle ]->src = get_stylesheet_directory_uri() . '/assets/css/' . $file . '.css';

            $inline = $styles->get_data( $handle, 'after' );
            if ( $inline ) {
                $styles->add_data( $handle, 'after', array_map( function ( $css ) { return '@layer wordpress {' . $css . '}'; }, $inline ) );
            }
        }
    }
    if ( function_exists( 'is_product' ) && is_product() ) {
        $product = wc_get_product( get_queried_object_id() );
        if ( $product && $product->is_type( 'variable' ) ) {
            wp_enqueue_script( 'buissiere-product-options', get_stylesheet_directory_uri() . '/assets/js/product-options.js', array( 'jquery', 'wc-add-to-cart-variation' ), filemtime( get_stylesheet_directory() . '/assets/js/product-options.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
            wp_localize_script( 'buissiere-product-options', 'buissierePriceSettings', array(
                'decimals'          => wc_get_price_decimals(),
                'decimalSeparator'  => wc_get_price_decimal_separator(),
                'thousandSeparator' => wc_get_price_thousand_separator(),
                'currencySymbol'    => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
                'priceFormat'       => html_entity_decode( get_woocommerce_price_format(), ENT_QUOTES, 'UTF-8' ),
            ) );
        }
    }
    if ( function_exists( 'is_cart' ) && is_cart() ) {
        wp_enqueue_script( 'buissiere-cart', get_stylesheet_directory_uri() . '/assets/js/cart.js', array( 'jquery', 'wc-cart' ), filemtime( get_stylesheet_directory() . '/assets/js/cart.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
    }
    wp_enqueue_script( 'buissiere-navigation', get_stylesheet_directory_uri() . '/assets/js/navigation.js', array(), filemtime( get_stylesheet_directory() . '/assets/js/navigation.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
    wp_dequeue_style( 'storefront-fonts' );
    wp_dequeue_style( 'source-sans-pro' );
    wp_enqueue_style( 'storefront-child-style', get_stylesheet_uri(), array( 'storefront-style' ), filemtime( get_stylesheet_directory() . '/style.css' ) );
}
add_action( 'wp_enqueue_scripts', 'buissiere_enqueue_styles', 150 );
