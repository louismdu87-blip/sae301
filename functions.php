<?php
/**
 * Fonctions du thème enfant Buissière.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Charge le CSS enfant après le CSS de Storefront.
 * Réutilise le handle de Storefront pour éviter un double chargement.
 */
function buissiere_enqueue_styles() {
    wp_enqueue_style(
        'storefront-child-style',
        get_stylesheet_uri(),
        array( 'storefront-style' ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'buissiere_enqueue_styles', 20 );
