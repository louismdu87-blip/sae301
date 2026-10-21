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
    if ( function_exists( 'is_cart' ) && is_cart() ) {
        wp_enqueue_script( 'buissiere-cart', get_stylesheet_directory_uri() . '/assets/js/cart.js', array( 'jquery', 'wc-cart' ), filemtime( get_stylesheet_directory() . '/assets/js/cart.js' ), true );
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
    // Itération 5 : formulaire et bouton d’achat natifs, avec les contrôles de quantité BEM.
    add_action( 'woocommerce_before_single_product_summary', 'buissiere_variable_product_formats', 21 );
    add_action( 'woocommerce_before_add_to_cart_quantity', 'buissiere_variable_quantity_open' );
    add_action( 'woocommerce_after_add_to_cart_quantity', 'buissiere_variable_quantity_close' );
    remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
    remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
    // US-14.1 : note native conservée ; les avatars ne figurent pas dans la maquette.
    remove_action( 'woocommerce_review_before', 'woocommerce_review_display_gravatar', 10 );
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
    // US-14.1 : les avis sont affichés directement sous la fiche, hors des onglets.
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

/** Encadre la quantité native sans surcharger le modèle WooCommerce. */
function buissiere_variable_quantity_open() {
    global $product;
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return;
    }
    ?>
    <div class="product-options__quantity">
        <div class="product-options__stepper">
            <button class="product-options__quantity-button" type="button" data-quantity-step="-1" aria-label="Diminuer la quantité" disabled>−</button>
    <?php
}

function buissiere_variable_quantity_close() {
    global $product;
    if ( ! $product || ! $product->is_type( 'variable' ) ) {
        return;
    }
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

/** Itération 5 : panier natif WooCommerce, présenté selon les maquettes Figma. */
function buissiere_cart_title( $title, $post_id ) {
    if ( function_exists( 'is_cart' ) && is_cart() && in_the_loop() && is_main_query() && wc_get_page_id( 'cart' ) === (int) $post_id ) {
        return 'Votre panier';
    }
    return $title;
}
add_filter( 'the_title', 'buissiere_cart_title', 10, 2 );

function buissiere_cart_progress() {
    ?>
    <ol class="cart-page__steps" aria-label="Étapes de la commande">
        <li class="cart-page__step cart-page__step--current" aria-current="step"><span class="cart-page__step-number">1</span>Panier</li>
        <li class="cart-page__step"><span class="cart-page__step-number">2</span>Commander</li>
        <li class="cart-page__step"><span class="cart-page__step-number">3</span>Confirmation</li>
    </ol>
    <?php
}
add_action( 'woocommerce_cart_is_empty', 'buissiere_cart_progress', 1 );

function buissiere_cart_open() {
    buissiere_cart_progress();
    echo '<div class="cart-page__layout">';
}
add_action( 'woocommerce_before_cart', 'buissiere_cart_open' );

function buissiere_cart_close() {
    ?>
    </div>
    <p class="cart-page__support">Une question sur votre commande ? <a href="tel:+33555341044">05 55 34 10 44</a></p>
    <?php
}
add_action( 'woocommerce_after_cart', 'buissiere_cart_close' );

function buissiere_cart_return() {
    ?>
    <a class="cart-page__return" href="<?php echo esc_url( home_url( '/#nos-produits' ) ); ?>">← Retour aux produits : Accueil · Nos produits</a>
    <?php
}
add_action( 'woocommerce_after_cart_table', 'buissiere_cart_return' );
add_action( 'woocommerce_after_cart_totals', function () {
    if ( 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) ) {
        echo '<p class="cart-page__guest">Commande possible sans créer de compte.</p>';
    }
} );
add_action( 'woocommerce_proceed_to_checkout', function () {
    echo '<p class="cart-page__delivery">Retrait gratuit en chocolaterie ou livraison à domicile.<br>L’adresse et le mode de réception sont à choisir à l’étape suivante.</p>';
}, 5 );
add_action( 'wp', function () {
    if ( is_cart() ) {
        remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
    }
} );

/** Réutilise l’input natif : nom, quantité et limites de stock restent gérés par WooCommerce. */
add_filter( 'woocommerce_cart_item_quantity', function ( $html, $key, $item ) {
    if ( ! is_cart() || $item['data']->is_sold_individually() ) {
        return $html;
    }
    $name = $item['data']->get_name();
    return '<div class="cart-quantity">'
        . '<button class="cart-quantity__button" type="button" data-cart-step="-1" aria-label="' . esc_attr( 'Diminuer la quantité de ' . $name ) . '">−</button>'
        . $html
        . '<button class="cart-quantity__button" type="button" data-cart-step="1" aria-label="' . esc_attr( 'Augmenter la quantité de ' . $name ) . '">+</button>'
        . '</div>';
}, 10, 3 );
add_filter( 'woocommerce_cart_item_remove_link', function ( $html, $key ) {
    if ( ! is_cart() ) {
        return $html;
    }
    $item = WC()->cart->get_cart_item( $key );
    return '<a class="remove cart-page__remove" role="button" href="' . esc_url( wc_get_cart_remove_url( $key ) ) . '" aria-label="' . esc_attr( 'Supprimer ' . $item['data']->get_name() . ' du panier' ) . '">× Supprimer</a>';
}, 10, 2 );

add_action( 'woocommerce_cart_coupon', function () {
    echo '<span class="cart-page__coupon-label" aria-hidden="true">Code promo</span>';
} );

/** US-10.2 : libellés du formulaire natif, limités au panier. */
add_filter( 'gettext_woocommerce', function ( $translation, $text ) {
    if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
        return $translation;
    }
    if ( 'Coupon code' === $text ) {
        return 'Saisir votre code';
    }
    if ( 'Coupon:' === $text ) {
        return 'Code promo';
    }
    return $translation;
}, 10, 2 );

add_filter( 'woocommerce_cart_totals_coupon_label', function ( $label, $coupon ) {
    if ( ! is_cart() ) {
        return $label;
    }
    return '<span class="cart-page__discount-label">Code promo : <span class="cart-page__discount-code">' . esc_html( $coupon->get_code() ) . '</span></span>';
}, 10, 2 );

function buissiere_cart_checkout_button() {
    echo '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="checkout-button button alt wc-forward">Passer commande</a>';
}
add_action( 'after_setup_theme', function () {
    remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
    add_action( 'woocommerce_proceed_to_checkout', 'buissiere_cart_checkout_button', 20 );
}, 100 );

add_filter( 'woocommerce_cart_item_name', function ( $name, $item ) {
    if ( ! is_cart() || ! $item['data']->is_type( 'variation' ) ) {
        return $name;
    }
    $product = wc_get_product( $item['product_id'] );
    $format = $item['data']->get_attribute( 'format' );
    if ( false === strpos( $name, '<a ' ) ) {
        return esc_html( $product->get_name() );
    }
    $html = '<a href="' . esc_url( $item['data']->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a>';
    if ( $format ) {
        $html .= '<ul class="cart-page__options"><li>Format : ' . esc_html( $format ) . '</li></ul>';
    }
    return $html;
}, 10, 2 );

/** Itération 6 : instructions SCF dans la section retrait du checkout en blocs. */
function buissiere_enqueue_pickup_instructions() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() || ! function_exists( 'get_field' ) ) {
        return;
    }
    $instructions = get_field( 'instructions_retrait', wc_get_page_id( 'checkout' ) );
    if ( ! is_string( $instructions ) || '' === trim( $instructions ) ) {
        return;
    }
    wp_enqueue_script(
        'buissiere-pickup-instructions',
        get_stylesheet_directory_uri() . '/assets/js/pickup-instructions.js',
        array( 'wp-element', 'wp-plugins', 'wc-blocks-checkout' ),
        filemtime( get_stylesheet_directory() . '/assets/js/pickup-instructions.js' ),
        true
    );
    wp_localize_script( 'buissiere-pickup-instructions', 'buissierePickupInstructions', array(
        'title' => 'Instructions pour le retrait',
        'text'  => $instructions,
    ) );
}
add_action( 'wp_enqueue_scripts', 'buissiere_enqueue_pickup_instructions', 150 );

/** Itération 7 : espace client natif WooCommerce aux couleurs de la maquette. */
add_filter( 'body_class', function ( $classes ) {
    if ( function_exists( 'is_account_page' ) && is_account_page() ) {
        $classes[] = 'account-page';
        $classes[] = is_user_logged_in() ? 'account-page--signed-in' : 'account-page--signed-out';
    }
    return $classes;
} );

function buissiere_account_title( $title, $post_id ) {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! in_the_loop() || ! is_main_query() || wc_get_page_id( 'myaccount' ) !== (int) $post_id ) {
        return $title;
    }
    if ( is_wc_endpoint_url( 'lost-password' ) ) { return 'Compte — Mot de passe oublié'; }
    if ( ! is_user_logged_in() ) { return 'Compte — Connexion et inscription'; }
    foreach ( array(
        'orders' => 'Historique des commandes',
        'view-order' => 'Détail de la commande',
        'edit-address' => 'Mes adresses',
        'edit-account' => 'Détails du compte',
    ) as $endpoint => $label ) {
        if ( is_wc_endpoint_url( $endpoint ) ) { return 'Compte — ' . $label; }
    }
    return 'Compte — Tableau de bord';
}
add_filter( 'the_title', 'buissiere_account_title', 10, 2 );

function buissiere_account_progress() {
    $current = ! is_user_logged_in() ? 1 : ( is_wc_endpoint_url( 'orders' ) || is_wc_endpoint_url( 'view-order' ) ? 3 : 2 );
    $steps = array( 1 => 'Compte', 2 => 'Mon compte', 3 => 'Historique des commandes' );
    echo '<ol class="account-page__steps" aria-label="Parcours du compte client">';
    foreach ( $steps as $index => $label ) {
        echo '<li class="account-page__step' . ( $index === $current ? ' account-page__step--current' : '' ) . '"' . ( $index === $current ? ' aria-current="step"' : '' ) . '><span class="account-page__step-number">' . esc_html( $index ) . '</span>' . esc_html( $label ) . '</li>';
    }
    echo '</ol>';
}
add_action( 'woocommerce_before_customer_login_form', 'buissiere_account_progress' );

function buissiere_account_sidebar_open() {
    buissiere_account_progress();
    $user = wp_get_current_user();
    echo '<aside class="account-page__sidebar" aria-label="Espace client"><div class="account-page__identity"><p class="account-page__name">' . esc_html( $user->display_name ) . '</p><p class="account-page__caption">Compte client</p></div>';
}
add_action( 'woocommerce_before_account_navigation', 'buissiere_account_sidebar_open' );
add_action( 'woocommerce_after_account_navigation', function () { echo '</aside>'; } );

add_filter( 'woocommerce_account_menu_items', function ( $items ) {
    return array_intersect_key( $items, array_flip( array( 'dashboard', 'orders', 'edit-address', 'edit-account', 'customer-logout' ) ) );
} );

function buissiere_account_required_hint() {
    echo '<p class="account-page__required">Les champs marqués * sont obligatoires.</p>';
}
add_action( 'woocommerce_login_form_start', 'buissiere_account_required_hint' );
add_action( 'woocommerce_register_form_start', 'buissiere_account_required_hint' );

function buissiere_account_content() {
    if ( ! is_wc_endpoint_url() ) {
        get_template_part( 'template-parts/account-dashboard' );
    } else {
        // Les formulaires, contrôles d’accès et nonces restent ceux de WooCommerce.
        woocommerce_account_content();
    }
}
add_action( 'after_setup_theme', function () {
    remove_action( 'woocommerce_account_content', 'woocommerce_account_content' );
    add_action( 'woocommerce_account_content', 'buissiere_account_content' );
}, 100 );

/** US-8.3 : instructions administrées dans SCF, pour les e-mails clients de retrait. */
function buissiere_email_pickup_instructions( $order, $sent_to_admin, $plain_text, $email ) {
    if ( $sent_to_admin || ! $order instanceof WC_Order || ! $email instanceof WC_Email || ! function_exists( 'get_field' ) ) {
        return;
    }
    $email_ids = array( 'customer_on_hold_order', 'customer_processing_order', 'customer_completed_order', 'customer_invoice' );
    if ( ! $email->is_customer_email() || ! in_array( $email->id, $email_ids, true ) ) {
        return;
    }
    $has_pickup = false;
    foreach ( $order->get_shipping_methods() as $shipping ) {
        if ( in_array( $shipping->get_method_id(), array( 'pickup_location', 'local_pickup' ), true ) ) {
            $has_pickup = true;
            break;
        }
    }
    if ( ! $has_pickup ) {
        return;
    }
    $instructions = get_field( 'instructions_retrait', wc_get_page_id( 'checkout' ) );
    if ( ! is_string( $instructions ) || '' === trim( $instructions ) ) {
        return;
    }
    if ( $plain_text ) {
        echo "\nInstructions pour le retrait\n" . wp_strip_all_tags( $instructions ) . "\n\n";
    } else {
        echo '<div class="email-pickup"><h2>Instructions pour le retrait</h2><p>' . nl2br( esc_html( $instructions ) ) . '</p></div>';
    }
}
add_action( 'woocommerce_email_after_order_table', 'buissiere_email_pickup_instructions', 20, 4 );

/** US-9.2 : une commande retirée en boutique ne doit pas être annoncée comme expédiée. */
function buissiere_completed_pickup_email_subject( $subject, $order ) {
    if ( ! $order instanceof WC_Order ) {
        return $subject;
    }
    foreach ( $order->get_shipping_methods() as $shipping ) {
        if ( in_array( $shipping->get_method_id(), array( 'pickup_location', 'local_pickup' ), true ) ) {
            return sprintf( 'Votre commande n°%s a été retirée en boutique — Buissière', $order->get_order_number() );
        }
    }
    return $subject;
}
add_filter( 'woocommerce_email_subject_customer_completed_order', 'buissiere_completed_pickup_email_subject', 10, 2 );

/** US-11.3 : ordre administrable, limité à l’archive principale de l’équipe. */
function buissiere_team_archive_order( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'equipe' ) ) {
        $query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
    }
}
add_action( 'pre_get_posts', 'buissiere_team_archive_order' );

/** US-12.3 : archives WooCommerce natives, avec accès aux autres collections. */
function buissiere_collection_navigation() {
    if ( is_tax( 'product_collection' ) ) {
        get_template_part( 'template-parts/collections-navigation' );
    }
}
add_action( 'woocommerce_archive_description', 'buissiere_collection_navigation', 20 );

/** US-13.2 : les associations administrées dans WooCommerce restent la source unique. */
function buissiere_product_recommendations( $ids, $context ) {
    $items = array();
    $ids = wp_parse_id_list( $ids );
    if ( $ids ) {
        _prime_post_caches( $ids );
    }
    foreach ( $ids as $id ) {
        $item = wc_get_product( $id );
        if ( $item && $item->is_visible() && $item->is_in_stock() && $item->is_purchasable() ) {
            $items[] = $item;
            if ( 2 === count( $items ) ) {
                break;
            }
        }
    }
    // Le conteneur stable permet d’actualiser les suggestions du panier sans requête supplémentaire.
    echo '<div class="product-recommendations product-recommendations--' . esc_attr( $context ) . '">';
    if ( $items ) {
        $original_product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
        $original_loop = isset( $GLOBALS['woocommerce_loop'] ) ? $GLOBALS['woocommerce_loop'] : null;
        wc_setup_loop( array( 'name' => 'cross-sells', 'columns' => 2 ) );
        ?>
        <section class="product-recommendations__section" aria-labelledby="recommendations-<?php echo esc_attr( $context ); ?>">
            <h2 class="product-recommendations__title" id="recommendations-<?php echo esc_attr( $context ); ?>">Pour prolonger le plaisir</h2>
            <p class="product-recommendations__intro">Des gourmandises à découvrir en complément de votre sélection.</p>
            <?php
            add_filter( 'woocommerce_product_cross_sells_products_heading', '__return_false', 999 );
            wc_get_template( 'cart/cross-sells.php', array( 'cross_sells' => $items ) );
            remove_filter( 'woocommerce_product_cross_sells_products_heading', '__return_false', 999 );
            if ( 'product' === $context ) {
                echo '<p class="product-recommendations__return"><a class="back-link" href="' . esc_url( home_url( '/#nos-produits' ) ) . '"><span aria-hidden="true">←</span> Retour aux produits</a></p>';
            }
            ?>
        </section>
        <?php
        $GLOBALS['product'] = $original_product;
        if ( null === $original_loop ) {
            wc_reset_loop();
        } else {
            $GLOBALS['woocommerce_loop'] = $original_loop;
        }
    }
    echo '</div>';
}

function buissiere_single_product_recommendations() {
    global $product;
    if ( ! $product instanceof WC_Product || post_password_required() ) {
        return;
    }
    buissiere_product_recommendations( array_diff( $product->get_cross_sell_ids(), array( $product->get_id() ) ), 'product' );
}
add_action( 'woocommerce_after_single_product_summary', 'buissiere_single_product_recommendations', 25 );

function buissiere_cart_product_recommendations() {
    if ( WC()->cart && ! WC()->cart->is_empty() ) {
        buissiere_product_recommendations( WC()->cart->get_cross_sells(), 'cart' );
    }
}
add_action( 'woocommerce_after_cart', 'buissiere_cart_product_recommendations', 20 );

/** Encadre les cartes natives et place leur vrai prix sous le fond gris, comme dans Figma. */
function buissiere_recommendation_card_open() {
    if ( 'cross-sells' === wc_get_loop_prop( 'name' ) ) {
        echo '<div class="product-card">';
    }
}
add_action( 'woocommerce_before_shop_loop_item', 'buissiere_recommendation_card_open', 5 );

function buissiere_recommendation_card_close() {
    global $product;
    if ( 'cross-sells' === wc_get_loop_prop( 'name' ) ) {
        echo '</div><p class="product-recommendations__price">' . wp_kses_post( $product->get_price_html() ) . '</p>';
    }
}
add_action( 'woocommerce_after_shop_loop_item', 'buissiere_recommendation_card_close', 20 );

/** US-14.1 : présentation native des avis, sans surcharge des modèles WooCommerce. */
function buissiere_product_reviews() {
    global $product;
    if ( ! $product instanceof WC_Product || ! comments_open() || post_password_required() || 'yes' !== get_option( 'woocommerce_enable_reviews' ) ) {
        return;
    }
    echo '<section class="product-reviews" aria-label="Avis clients">';
    add_filter( 'gettext_woocommerce', 'buissiere_product_review_labels', 10, 2 );
    comments_template();
    remove_filter( 'gettext_woocommerce', 'buissiere_product_review_labels', 10 );
    echo '</section>';
}
add_action( 'woocommerce_after_single_product_summary', 'buissiere_product_reviews', 20 );

function buissiere_product_review_labels( $translation, $text ) {
    $labels = array(
        'Reviews' => 'Avis clients',
        'Your rating' => 'Votre note',
        'Your review' => 'Votre commentaire',
        'There are no reviews yet.' => 'Aucun avis pour le moment.',
    );
    return isset( $labels[ $text ] ) ? $labels[ $text ] : $translation;
}

function buissiere_product_reviews_title( $title, $count, $product ) {
    $summary = sprintf( _n( '%s avis', '%s avis', $count, 'buissiere' ), number_format_i18n( $count ) );
    if ( $product->get_rating_count() ) {
        $summary = sprintf( '%s sur 5 · %s', wc_format_localized_decimal( $product->get_average_rating() ), $summary );
    }
    return 'Avis clients<span class="product-reviews__summary">' . esc_html( $summary ) . '</span>';
}
add_filter( 'woocommerce_reviews_title', 'buissiere_product_reviews_title', 10, 3 );

function buissiere_product_review_form( $args ) {
    $args['title_reply'] = 'Votre avis compte';
    $args['title_reply_before'] = '<h2 id="reply-title" class="comment-reply-title product-reviews__form-title">';
    $args['title_reply_after'] = '</h2>';
    $args['label_submit'] = 'Publier mon avis';
    $args['class_submit'] = 'submit button button--filled product-reviews__submit';
    return $args;
}
add_filter( 'woocommerce_product_review_comment_form_args', 'buissiere_product_review_form' );

/** US-15.2 : frais fixes sous le seuil SCF, recalculés par WooCommerce. */
function buissiere_cart_preparation_fee( $cart ) {
    if ( ( is_admin() && ! wp_doing_ajax() ) || ! $cart instanceof WC_Cart || $cart->is_empty() || ! function_exists( 'get_field' ) ) {
        return;
    }

    $threshold = get_field( 'seuil_frais_preparation', 'option' );
    $amount    = get_field( 'montant_frais_preparation', 'option' );
    if ( ! is_numeric( $threshold ) || ! is_numeric( $amount ) ) {
        return;
    }

    $decimals  = wc_get_price_decimals();
    $threshold = round( (float) $threshold, $decimals );
    $amount    = round( (float) $amount, $decimals );
    if ( $threshold <= 0 || $amount <= 0 ) {
        return;
    }

    // Produits après réductions, hors livraison, taxes et autres frais.
    $products_total = round( (float) $cart->get_cart_contents_total(), $decimals );
    if ( $products_total < $threshold ) {
        $cart->add_fee( __( 'Frais de préparation', 'buissiere' ), $amount, false );
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'buissiere_cart_preparation_fee' );
