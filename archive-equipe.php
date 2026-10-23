<?php
/* Itération 11. */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="site-main team-page">
    <header class="team-page__header">
        <p class="team-page__eyebrow">Chocolaterie Buissière · Limoges</p>
        <h1 class="team-page__title"><?php echo esc_html( post_type_archive_title( '', false ) ); ?></h1>
        <p class="team-page__intro">Découvrez les visages et les savoir-faire de notre chocolaterie.</p>
    </header>
    <?php if ( have_posts() ) : ?>
        <ul class="team-page__list">
            <?php while ( have_posts() ) : the_post(); ?>
                <li class="team-page__item">
                    <?php get_template_part( 'template-parts/team-card' ); ?>
                </li>
            <?php endwhile; ?>
        </ul>
        <?php the_posts_pagination( array( 'prev_text' => 'Page précédente', 'next_text' => 'Page suivante' ) ); ?>
    <?php else : ?>
        <p class="team-page__empty">Aucun membre de l’équipe n’est présenté pour le moment.</p>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
