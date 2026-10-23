<?php
/* Itération 2. */
defined( 'ABSPATH' ) || exit;

function buissiere_setup_layout() {
    remove_all_actions( 'storefront_header' );
    add_action( 'storefront_header', 'buissiere_header' );
    remove_all_actions( 'storefront_footer' );
    add_action( 'storefront_footer', 'buissiere_footer' );
    remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
    add_action( 'storefront_before_content', function () {
        if ( ! is_front_page() && ! is_shop() ) { woocommerce_breadcrumb(); }
    }, 10 );
    remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
    remove_action( 'storefront_after_footer', 'storefront_sticky_single_add_to_cart', 999 );
    /* Itération 5. */
    add_action( 'woocommerce_before_single_product_summary', 'buissiere_variable_product_formats', 21 );
    add_action( 'woocommerce_before_add_to_cart_quantity', 'buissiere_variable_quantity_open' );
    add_action( 'woocommerce_after_add_to_cart_quantity', 'buissiere_variable_quantity_close' );
    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
    remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
    /* Itération 14. */
    remove_action( 'woocommerce_review_before', 'woocommerce_review_display_gravatar', 10 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
    /* Itération 4. */
    add_action( 'woocommerce_single_product_summary', 'buissiere_product_information', 22 );
    add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 25 );
    add_action( 'woocommerce_single_product_summary', 'buissiere_product_category', 4 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
    remove_action( 'woocommerce_after_single_product_summary', 'storefront_upsell_display', 15 );
    remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
    remove_action( 'woocommerce_after_single_product_summary', 'storefront_single_product_pagination', 30 );
}
add_action( 'after_setup_theme', 'buissiere_setup_layout', 100 );

function buissiere_header() {
    get_template_part( 'template-parts/brand-header' );
}
function buissiere_footer() {
    get_template_part( 'template-parts/brand-footer' );
}
function buissiere_product_category() {
    global $product;
    echo '<p class="product-view__category">' . wp_kses_post( wc_get_product_category_list( $product->get_id(), ', ' ) ) . '</p>';
}

function buissiere_product_information() {
    global $product;
    if ( ! $product || ! function_exists( 'get_field' ) ) {
        return;
    }
    $fields = array(
        'compositions'            => 'Composition',
        'allergenes'              => 'Allergènes',
        'valeurs_nutritionnelles' => 'Valeurs nutritionnelles moyennes pour 100 g',
    );
    $items = array();
    foreach ( $fields as $name => $label ) {
        $value = get_field( $name, $product->get_id() );
        if ( is_string( $value ) && '' !== trim( $value ) ) {
            $items[] = array( 'label' => $label, 'value' => $value );
        }
    }
    if ( ! $items ) {
        return;
    }
    ?>
    <ul class="product-information" aria-label="Informations du produit">
        <?php foreach ( $items as $item ) : ?>
            <li class="product-information__item">
                <h2 class="product-information__title"><?php echo esc_html( $item['label'] ); ?></h2>
                <p class="product-information__text"><?php echo nl2br( esc_html( $item['value'] ) ); ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
    global $product;
    /* Itération 14. */
    unset( $tabs['reviews'] );
    if ( $product && $product->is_type( 'variable' ) ) {
        unset( $tabs['additional_information'] );
    }
    return $tabs;
} );

add_filter( 'woocommerce_get_shop_page_permalink', function ( $url ) {
    return home_url( '/' );
} );
add_action( 'template_redirect', function () {
    if ( function_exists( 'is_shop' ) && is_shop() && ! is_front_page() && ! is_search() ) {
        wp_safe_redirect( home_url( '/' ), 302 );
        exit;
    }
}, 0 );

function buissiere_setup_product_cards() {
    remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
    remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
    remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );

    remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 6 );
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
    add_action( 'woocommerce_before_shop_loop_item_title', 'buissiere_product_card_image', 10 );
    add_action( 'woocommerce_shop_loop_item_title', 'buissiere_product_card_title', 10 );
    add_action( 'woocommerce_after_shop_loop_item', 'buissiere_product_card_button', 10 );
}
add_action( 'after_setup_theme', 'buissiere_setup_product_cards', 110 );

function buissiere_product_card_classes( $classes, $product ) {
    if ( 'cross-sells' === wc_get_loop_prop( 'name' ) ) {
        $classes[] = 'product-recommendations__item';
    } elseif ( ! is_product() ) {
        $classes[] = 'product-card';
    } elseif ( $product->is_type( 'variable' ) ) {
        $classes[] = 'product-view--variable';
    }
    return $classes;
}
add_filter( 'woocommerce_post_class', 'buissiere_product_card_classes', 10, 2 );

function buissiere_product_card_image() {
    global $product;
    ?>
    <a class="product-card__visual" href="<?php echo esc_url( $product->get_permalink() ); ?>" tabindex="-1" aria-hidden="true">
        <?php echo $product->get_image( 'woocommerce_single', array( 'class' => 'product-card__image', 'alt' => '', 'loading' => 'lazy' ) ); ?>
    </a>
    <?php
}

function buissiere_product_card_title() {
    global $product;
    $heading = is_product_taxonomy() || is_search() ? 'h2' : 'h3';
    ?>
    <<?php echo $heading; ?> class="product-card__title"><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></<?php echo $heading; ?>>
    <?php
}

function buissiere_product_card_button() {
    global $product;
    ?>
    <a class="button product-card__button" href="<?php echo esc_url( $product->get_permalink() ); ?>" aria-label="<?php echo esc_attr( 'Découvrir : ' . $product->get_name() ); ?>">Découvrir</a>
    <?php
}
