
<?php
/**
 * Plantilla principal del tema SINACIN.
 * Muestra el contenido dinámico de WordPress.
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main class="sinacin-contenido-principal">

    <div class="container">

        <?php if (have_posts()) : ?>

            <?php while (have_posts()) : the_post(); ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

                    <h1 class="sinacin-pagina-titulo">
                        <?php the_title(); ?>
                    </h1>

                    <div class="sinacin-pagina-contenido">

                        <?php the_content(); ?>

                    </div>

                </article>

            <?php endwhile; ?>

        <?php else : ?>

            <h1>Contenido no encontrado</h1>

            <p>
                No encontramos el contenido solicitado.
            </p>

        <?php endif; ?>

    </div>

</main>

<?php get_footer(); ?>
