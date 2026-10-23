<?php
/* Itérations 1 à 15. */
defined( 'ABSPATH' ) || exit;

foreach ( array(
    'assets',
    'catalogue',
    'product-options',
    'cart',
    'checkout',
    'account',
    'emails',
    'team',
    'collections',
    'recommendations',
    'reviews',
    'preparation-fees',
) as $module ) {
    require_once get_stylesheet_directory() . '/inc/' . $module . '.php';
}
