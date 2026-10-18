<?php
/** Tableau de bord : uniquement les données du client connecté. */
defined( 'ABSPATH' ) || exit;
if ( ! is_user_logged_in() ) { return; }
$user = wp_get_current_user();
$name = $user->first_name ? $user->first_name : $user->display_name;
$orders = wc_get_orders( array( 'customer_id' => $user->ID, 'limit' => 1, 'orderby' => 'date', 'order' => 'DESC', 'status' => array_keys( wc_get_order_statuses() ) ) );
?>
<div class="account-dashboard">
    <h2 class="account-dashboard__greeting">Bonjour <?php echo esc_html( $name ); ?>,</h2>
    <p class="account-dashboard__intro">Bienvenue dans votre espace personnel. Retrouvez vos commandes et les informations de votre compte.</p>
    <p class="account-dashboard__identity">Connecté en tant que <?php echo esc_html( $user->display_name ); ?> · <?php echo esc_html( $user->user_email ); ?></p>
    <section class="account-card account-card--order" aria-labelledby="account-last-order">
        <div class="account-card__heading">
            <h3 class="account-card__title" id="account-last-order">Dernière commande</h3>
            <?php if ( $orders ) : $order = $orders[0]; ?>
                <span class="account-card__status"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span>
            <?php endif; ?>
        </div>
        <?php if ( $orders ) : ?>
            <div class="account-card__order-meta">
                <span>N° <?php echo esc_html( $order->get_order_number() ); ?></span>
                <span><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
            </div>
            <?php $items = $order->get_items(); $item = reset( $items ); ?>
            <?php if ( $item ) : $product = $item->get_product(); ?>
                <div class="account-card__product">
                    <?php if ( $product ) { echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'account-card__image', 'alt' => '' ) ) ); } ?>
                    <p><?php echo esc_html( $item->get_name() ); ?> × <?php echo esc_html( $item->get_quantity() ); ?></p>
                    <span class="account-card__total"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
                </div>
            <?php endif; ?>
            <a class="button account-card__button" href="<?php echo esc_url( $order->get_view_order_url() ); ?>">Voir ma commande</a>
        <?php else : ?>
            <p class="account-card__text">Vous n’avez pas encore passé de commande.</p>
            <a class="account-card__link" href="<?php echo esc_url( home_url( '/#nos-produits' ) ); ?>">Découvrir nos produits →</a>
        <?php endif; ?>
    </section>
    <div class="account-dashboard__cards">
        <?php foreach ( array(
            'orders' => array( 'Mes commandes', 'Consultez vos commandes et leur détail.', 'Voir l’historique →' ),
            'edit-address' => array( 'Mes adresses', 'Vos coordonnées de livraison et de facturation.', 'Gérer mes adresses →' ),
            'edit-account' => array( 'Mon profil', 'Votre identité, votre e-mail et votre mot de passe.', 'Détails du compte →' ),
        ) as $endpoint => $card ) : ?>
            <section class="account-card">
                <h3 class="account-card__title"><?php echo esc_html( $card[0] ); ?></h3>
                <p class="account-card__text"><?php echo esc_html( $card[1] ); ?></p>
                <a class="account-card__link" href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"><?php echo esc_html( $card[2] ); ?></a>
            </section>
        <?php endforeach; ?>
    </div>
    <p class="account-dashboard__return">Envie d’un nouveau moment gourmand ? Nos produits vous attendent sur l’accueil.</p>
    <a class="button account-dashboard__catalogue" href="<?php echo esc_url( home_url( '/#nos-produits' ) ); ?>">Retrouver les produits sur l’accueil</a>
</div>
