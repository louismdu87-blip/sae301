<?php
/* Itération 2. */
defined( 'ABSPATH' ) || exit; ?>
<a class="skip-link" href="#main">Aller au contenu</a>
<div class="brand-header">
    <a class="brand-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Buissière — accueil">
        <?php
        $logo_id = (int) get_theme_mod( 'custom_logo' );
        if ( $logo_id ) {
            echo wp_get_attachment_image( $logo_id, 'full', false, array(
                'class'   => 'brand-header__image',
                'alt'     => 'Chocolaterie Buissière',
                'sizes'   => '6.875rem',
                'loading' => 'eager',
            ) );
        } else {
            echo esc_html( get_bloginfo( 'name' ) );
        }
        ?>
    </a>
    <nav class="brand-header__navigation" aria-label="Navigation principale">
        <ul class="brand-header__links">
            <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a></li>
            <li><a href="<?php echo esc_url( home_url( '/#nos-produits' ) ); ?>">Nos produits</a></li>
            <?php $team_url = get_post_type_archive_link( 'equipe' ); ?>
            <?php if ( $team_url ) : ?>
                <li><a href="<?php echo esc_url( $team_url ); ?>"<?php if ( is_post_type_archive( 'equipe' ) || is_singular( 'equipe' ) ) : ?> aria-current="<?php echo is_post_type_archive( 'equipe' ) ? 'page' : 'location'; ?>"<?php endif; ?>>Équipe</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
        <div class="brand-header__actions">
            <a class="brand-header__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="Voir le panier"<?php if ( is_cart() ) { echo ' aria-current="page"'; } ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M5 8h14l1 13H4L5 8Z" stroke-linejoin="round" />
                    <path d="M8 9V6a4 4 0 0 1 8 0v3" stroke-linecap="round" />
                </svg>
            </a>
            <a class="brand-header__account" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="Mon compte"<?php if ( is_account_page() ) { echo ' aria-current="page"'; } ?>>
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="12" cy="7" r="4" />
                    <path d="M4 22v-2a8 8 0 0 1 16 0v2" stroke-linecap="round" />
                </svg>
            </a>
        </div>
    <?php endif; ?>
</div>
