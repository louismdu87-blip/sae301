<?php
/** Intégration Figma — catalogue et test des produits variables. */
defined( 'ABSPATH' ) || exit;

/** Le parent reste disponible, dans une couche inférieure au design system. */
function buissiere_enqueue_styles() {
    $styles = wp_styles();
    foreach ( array( 'storefront-style' => 'storefront', 'storefront-icons' => 'icons', 'storefront-woocommerce-style' => 'woocommerce', 'storefront-gutenberg-blocks' => 'blocks' ) as $handle => $file ) {
        if ( isset( $styles->registered[ $handle ] ) ) {
            $styles->registered[ $handle ]->src = get_stylesheet_directory_uri() . '/assets/css/' . $file . '.css';
            // Les couleurs du personnalisateur font aussi partie du thème parent.
            $inline = $styles->get_data( $handle, 'after' );
            if ( $inline ) {
                $styles->add_data( $handle, 'after', array_map( function ( $css ) { return '@layer wordpress {' . $css . '}'; }, $inline ) );
            }
        }
    }
    if ( function_exists( 'is_product' ) && is_product() ) {
        $product = wc_get_product( get_queried_object_id() );
        if ( $product && $product->is_type( 'variable' ) ) {
            wp_enqueue_script( 'buissiere-product-options', get_stylesheet_directory_uri() . '/assets/js/product-options.js', array( 'jquery', 'wc-add-to-cart-variation' ), filemtime( get_stylesheet_directory() . '/assets/js/product-options.js' ), true );
            wp_localize_script( 'buissiere-product-options', 'buissierePriceSettings', array(
                'decimals'          => wc_get_price_decimals(),
                'decimalSeparator'  => wc_get_price_decimal_separator(),
                'thousandSeparator' => wc_get_price_thousand_separator(),
                'currencySymbol'    => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
                'priceFormat'       => html_entity_decode( get_woocommerce_price_format(), ENT_QUOTES, 'UTF-8' ),
            ) );
        }
    }
    wp_dequeue_style( 'storefront-fonts' );
    wp_dequeue_style( 'source-sans-pro' );
    wp_enqueue_style( 'storefront-child-style', get_stylesheet_uri(), array( 'storefront-style' ), filemtime( get_stylesheet_directory() . '/style.css' ) );
}
add_action( 'wp_enqueue_scripts', 'buissiere_enqueue_styles', 150 );

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
    // Itération 2 : consultation du catalogue et des fiches uniquement.
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
    // Itération 3 : sélecteur natif et prix des variations, sans achat.
    add_action( 'woocommerce_single_product_summary', 'buissiere_variable_product_options', 30 );
    add_action( 'woocommerce_before_single_product_summary', 'buissiere_variable_product_formats', 21 );
    remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
    add_action( 'woocommerce_single_variation', 'buissiere_variable_product_quantity', 20 );
    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
    remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
    // Itération 4 : informations SCF après la description et avant le prix.
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
/** Informations administrées dans SCF ; seuls les champs renseignés sont affichés. */
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
    unset( $tabs['reviews'] );
    if ( $product && $product->is_type( 'variable' ) ) {
        unset( $tabs['additional_information'] );
    }
    return $tabs;
} );
/** L’accueil est l’unique entrée du catalogue. */
add_filter( 'woocommerce_get_shop_page_permalink', function ( $url ) {
    return home_url( '/' );
} );
add_action( 'template_redirect', function () {
    if ( function_exists( 'is_shop' ) && is_shop() && ! is_front_page() && ! is_search() ) {
        wp_safe_redirect( home_url( '/' ), 302 );
        exit;
    }
}, 0 );

/** Personnalise les cartes par les hooks du modèle natif content-product.php. */
function buissiere_setup_product_cards() {
    remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
    remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
    remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
    // La carte Figma affiche uniquement l’image, le titre et le lien Découvrir.
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

/** Ajoute la classe BEM aux cartes sans modifier celle de la fiche produit. */
function buissiere_product_card_classes( $classes, $product ) {
    if ( ! is_product() ) {
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

/** Affiche le formulaire natif uniquement pour les produits variables. */
function buissiere_variable_product_options() {
    global $product;
    if ( $product && $product->is_type( 'variable' ) ) {
        woocommerce_variable_add_to_cart();
    }
}

/** Quantité native WooCommerce, séparée du bouton d’achat des étapes suivantes. */
function buissiere_variable_product_quantity() {
    global $product;
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return;
    }
    $input_id = 'product-quantity-' . $product->get_id();
    ?>
    <div class="product-options__quantity">
        <label class="product-options__quantity-label" for="<?php echo esc_attr( $input_id ); ?>">Quantité</label>
        <div class="product-options__stepper">
            <button class="product-options__quantity-button" type="button" data-quantity-step="-1" aria-label="Diminuer la quantité" disabled>−</button>
        <?php
        woocommerce_quantity_input( array(
            'input_id'    => $input_id,
            'input_value' => $product->get_min_purchase_quantity(),
            'min_value'   => $product->get_min_purchase_quantity(),
            'max_value'   => $product->get_max_purchase_quantity(),
        ), $product );
        ?>
            <button class="product-options__quantity-button" type="button" data-quantity-step="1" aria-label="Augmenter la quantité">+</button>
        </div>
    </div>
    <?php
}

/** Formats sous l’image ; les valeurs restent celles administrées dans WooCommerce. */
function buissiere_variable_product_formats() {
    global $product;
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return;
    }
    echo '<div class="product-options">';
    foreach ( $product->get_variation_attributes() as $attribute => $options ) {
        $key = sanitize_title( $attribute );
        $label_id = 'product-options-' . $product->get_id() . '-' . $key;
        $label = 'format' === $key ? 'Taille' : wc_attribute_label( $attribute, $product );
        ?>
        <div class="product-options__formats" data-attribute="<?php echo esc_attr( wc_variation_attribute_name( $attribute ) ); ?>" hidden>
            <p class="product-options__label" id="<?php echo esc_attr( $label_id ); ?>"><?php echo esc_html( $label ); ?></p>
            <ul class="product-options__choices" role="radiogroup" aria-labelledby="<?php echo esc_attr( $label_id ); ?>">
                <?php foreach ( $options as $option ) :
                    $option_label = $option;
                    if ( taxonomy_exists( $attribute ) ) {
                        $term = get_term_by( 'slug', $option, $attribute );
                        if ( $term ) { $option_label = $term->name; }
                    }
                    ?>
                    <li class="product-options__item">
                        <label class="product-options__option">
                            <input class="product-options__radio" type="radio" name="buissiere-<?php echo esc_attr( $product->get_id() . '-' . $key ); ?>" value="<?php echo esc_attr( $option ); ?>">
                            <span class="product-options__choice"><?php echo esc_html( $option_label ); ?></span>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
    echo '</div>';
}

/** Prix et disponibilité réels de la variation, présentés comme sur la maquette. */
add_filter( 'woocommerce_available_variation', function ( $data, $product, $variation ) {
    $format = $variation->get_attribute( 'format' );
    $data['price_html'] = $variation->get_price_html();
    if ( $format ) {
        $data['price_html'] .= '<span class="product-options__price-meta">TTC · ' . esc_html( $format ) . '</span>';
    }
    $quantity = $variation->get_stock_quantity();
    if ( ! $variation->is_in_stock() ) {
        $state = 'out';
        $message = 'Épuisé';
    } elseif ( $variation->managing_stock() && null !== $quantity && $quantity <= wc_get_low_stock_amount( $variation ) ) {
        $state = 'low';
        $message = sprintf( 'Stock faible · %s restant(s)', wc_format_stock_quantity_for_display( $quantity, $variation ) );
    } else {
        $state = 'available';
        $message = 'Disponible';
    }
    $data['availability_html'] = '<p class="product-options__availability product-options__availability--' . esc_attr( $state ) . '">' . esc_html( $message ) . '</p>';
    return $data;
}, 10, 3 );
