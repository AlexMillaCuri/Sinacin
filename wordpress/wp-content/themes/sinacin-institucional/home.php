<?php
/**
 * SINACIN - Página de todas las noticias.
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main class="sinacin-archivo-noticias">

    <div class="container">

        <header class="sinacin-archivo-header">

            <span class="sinacin-noticias-etiqueta">
                ACTUALIDAD SINACIN
            </span>

            <h1>
                Todas las <span>Noticias</span>
            </h1>

            <p>
                Conoce nuestras últimas noticias,
                actividades y comunicados.
            </p>

        </header>

        <?php if (have_posts()) : ?>

            <div class="sinacin-archivo-grid">

                <?php while (have_posts()) : the_post(); ?>

                    <article class="sinacin-noticia-card">

                        <!-- IMAGEN -->

                        <div class="sinacin-noticia-imagen-wrap">

                            <a
                                href="<?php the_permalink(); ?>"
                                class="sinacin-noticia-imagen"
                                aria-label="<?php echo esc_attr(get_the_title()); ?>"
                            >

                                <?php if (has_post_thumbnail()) : ?>

                                    <?php the_post_thumbnail(
                                        'sinacin-noticia',
                                        array('loading' => 'lazy')
                                    ); ?>

                                <?php else : ?>

                                    <div class="sinacin-noticia-sin-imagen">
                                        SINACIN
                                    </div>

                                <?php endif; ?>

                            </a>

                            <!-- CATEGORÍA -->

                            <?php
                            $categorias = get_the_category();
                            $categoria = null;

                            foreach ($categorias as $item) {
                                if (!in_array(
                                    $item->slug,
                                    array('uncategorized', 'sin-categoria'),
                                    true
                                )) {
                                    $categoria = $item;
                                    break;
                                }
                            }
                            ?>

                            <?php if ($categoria) : ?>

                                <span class="sinacin-noticia-categoria">
                                    #<?php echo esc_html($categoria->name); ?>
                                </span>

                            <?php endif; ?>

                            <!-- ÍCONO CIRCULAR -->

                            <span
                                class="sinacin-noticia-icono"
                                aria-hidden="true"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                                    <rect x="6" y="7" width="5" height="5" rx="1"/>
                                    <path d="M14 8h4"/>
                                    <path d="M14 11h4"/>
                                    <path d="M6 15h12"/>
                                    <path d="M6 18h12"/>
                                </svg>
                            </span>

                        </div>

                        <!-- CONTENIDO -->

                        <div class="sinacin-noticia-contenido">

                            <span class="sinacin-noticia-tipo">
                                NOTICIAS
                            </span>

                            <!-- FECHA -->

                            <div class="sinacin-noticia-fecha">

                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <rect x="3" y="5" width="18" height="16" rx="2"/>
                                    <path d="M7 3v4M17 3v4M3 10h18"/>
                                </svg>

                                <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                    <?php echo esc_html(
                                        get_the_date('j \d\e F \d\e Y')
                                    ); ?>
                                </time>

                            </div>

                            <!-- TÍTULO -->

                            <h3>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h3>

                            <!-- RESUMEN -->

                            <p class="sinacin-noticia-resumen">
                                <?php
                                echo esc_html(
                                    wp_trim_words(
                                        get_the_excerpt(),
                                        22,
                                        '...'
                                    )
                                );
                                ?>
                            </p>

                        </div>

                    </article>

                <?php endwhile; ?>

            </div>

            <!-- PAGINACIÓN -->

            <nav
                class="sinacin-archivo-paginacion"
                aria-label="Paginación de noticias"
            >
                <?php
                the_posts_pagination(array(
                    'mid_size'  => 2,
                    'prev_text' => '← Anterior',
                    'next_text' => 'Siguiente →',
                ));
                ?>
            </nav>

        <?php else : ?>

            <p class="sinacin-archivo-vacio">
                Todavía no hay noticias publicadas.
            </p>

        <?php endif; ?>

    </div>

</main>

<?php get_footer(); ?>
