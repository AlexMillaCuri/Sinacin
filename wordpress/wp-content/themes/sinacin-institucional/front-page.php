<?php
/**
 * Portada institucional SINACIN.
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="contenido-principal">
    
    <?php
    $banners = new WP_Query(array(
        'post_type'      => 'sinacin_banner',
        'posts_per_page' => 10,
        'post_status'    => 'publish',
        'orderby'        => array(
            'menu_order' => 'ASC',
            'date'       => 'DESC'
        )
    ));
    ?>

    <?php if ($banners->have_posts()) : ?>
    <section class="sinacin-slider" aria-label="Destacados SINACIN">

        <div class="sinacin-slider-track">

            <?php
            $numero = 0;
            while ($banners->have_posts()) :
                $banners->the_post();
            ?>

            <article
                class="sinacin-slide <?php echo $numero === 0 ? 'active' : ''; ?>"
                aria-hidden="<?php echo $numero === 0 ? 'false' : 'true'; ?>"
                <?php echo $numero === 0 ? '' : 'inert'; ?>
            >
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('full', array(
                        'class' => 'sinacin-slide-image',
                        'alt'   => ''
                    )); ?>
                <?php endif; ?>

                <div class="sinacin-slide-overlay"></div>

                <div class="container sinacin-slide-content">
                    <span class="sinacin-slide-label">
                        SINACIN · DESTACADOS
                    </span>

                    <h1><?php echo esc_html(get_the_title()); ?></h1>

                    <div class="sinacin-slide-description">
                        <?php the_content(); ?>
                    </div>
                </div>
            </article>

            <?php
                $numero++;
            endwhile;
            wp_reset_postdata();
            ?>

        </div>

        <?php if ($numero > 1) : ?>
            <button class="sinacin-slider-prev"
                    type="button"
                    aria-label="Banner anterior">
                &#10094;
            </button>

            <button class="sinacin-slider-next"
                    type="button"
                    aria-label="Banner siguiente">
                &#10095;
            </button>

            <div class="sinacin-slider-dots">
                <?php for ($i = 0; $i < $numero; $i++) : ?>
                    <button
                        class="sinacin-slider-dot <?php echo $i === 0 ? 'active' : ''; ?>"
                        type="button"
                        aria-label="<?php echo esc_attr('Ir al banner ' . ($i + 1)); ?>"
                    ></button>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    </section>
    <?php else : ?>
    <section class="sinacin-hero">
        <div class="container">
            <h1>Bienvenidos a SINACIN</h1>
            <p>Unidos por los derechos de nuestros trabajadores.</p>
        </div>
    </section>
    <?php endif; ?>

    <!-- SECCIÓN AFILIACIÓN SINACIN -->
    <section class="sinacin-afiliacion">
        <div class="sinacin-afiliacion-contenido">

            <span class="sinacin-afiliacion-etiqueta">
                SÚMATE AL CAMBIO
            </span>

            <h2>
                <span class="sinacin-afiliacion-rojo">
                    AFÍLIATE
                </span>
                <span class="sinacin-afiliacion-negro">
                    CON NOSOTROS
                </span>
            </h2>

            <p>
                Juntos fortalecemos la voz de los trabajadores
                y construimos mejores condiciones laborales
                para todos.
            </p>

            <a
                href="<?php echo esc_url(home_url('/unete-con-nosotros/')); ?>"
                class="sinacin-afiliacion-boton"
            >
                AFÍLIATE AHORA
                <span aria-hidden="true">→</span>
            </a>

        </div>
    </section>


    <!-- =========================================
        SINACIN - ÚLTIMAS NOTICIAS
    ========================================= -->

    <section class="sinacin-noticias" aria-labelledby="sinacin-noticias-titulo">

        <div class="container">

            <div class="sinacin-noticias-header">

                <div>
                    <span class="sinacin-noticias-etiqueta">
                        ACTUALIDAD SINACIN
                    </span>

                    <h2 id="sinacin-noticias-titulo">
                        Últimas <span>Noticias</span>
                    </h2>
                </div>

                <a
                    class="sinacin-noticias-ver-todas"
                    href="<?php echo esc_url(home_url('/noticias/')); ?>"
                >
                    Ver más noticias
                    <span aria-hidden="true">→</span>
                </a>

            </div>

            <?php
            $noticias = new WP_Query(array(
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'posts_per_page'      => 8,
                'orderby'             => 'date',
                'order'               => 'DESC',
                'ignore_sticky_posts' => true
            ));
            ?>

            <?php if ($noticias->have_posts()) : ?>

                <div class="sinacin-noticias-carrusel">

                    <div class="sinacin-noticias-ventana">

                        <div class="sinacin-noticias-track">

                            <?php while ($noticias->have_posts()) :
                                $noticias->the_post();
                            ?>

                                <article class="sinacin-noticia-card">

                                    <!-- IMAGEN Y ELEMENTOS SUPERPUESTOS -->

                                    <div class="sinacin-noticia-imagen-wrap">

                                        <a
                                            href="<?php the_permalink(); ?>"
                                            class="sinacin-noticia-imagen"
                                            aria-label="<?php echo esc_attr(get_the_title()); ?>"
                                        >

                                            <?php if (has_post_thumbnail()) : ?>

                                                <?php
                                                the_post_thumbnail(
                                                    'sinacin-noticia',
                                                    array(
                                                        'loading' => 'lazy'
                                                    )
                                                );
                                                ?>

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

                                        <span class="sinacin-noticia-icono" aria-hidden="true">

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

                                    <!-- CONTENIDO DE LA NOTICIA -->

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
                                                <?php echo esc_html(get_the_date('j \d\e F \d\e Y')); ?>
                                            </time>

                                        </div>

                                        <!-- TÍTULO -->

                                        <h3>
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_title(); ?>
                                            </a>
                                        </h3>

                                        <!-- DESCRIPCIÓN -->

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

                    </div>

                    <!-- CONTROLES DEL CARRUSEL -->

                    <div class="sinacin-noticias-controles">

                        <button
                            class="sinacin-noticias-anterior"
                            type="button"
                            aria-label="Noticia anterior"
                        >
                            &#10094;
                        </button>

                        <button
                            class="sinacin-noticias-siguiente"
                            type="button"
                            aria-label="Siguiente noticia"
                        >
                            &#10095;
                        </button>

                    </div>

                </div>

            <?php else : ?>

                <p>Próximamente publicaremos nuestras noticias.</p>

            <?php endif; ?>

            <?php wp_reset_postdata(); ?>

        </div>

    </section>

    
    <!-- =========================================
        SINACIN - REDES SOCIALES
    ========================================= -->

    <section class="sinacin-redes">

        <div class="container">

            <div class="sinacin-redes-contenedor">

                <div class="sinacin-redes-presentacion">

                    <img
                        src="<?php echo esc_url(
                            get_template_directory_uri() .
                            '/assets/images/logo-sinacin-2.png'
                        ); ?>"
                        alt="Logo SINACIN"
                        class="sinacin-redes-logo"
                        loading="lazy"
                    >

                    <h2>
                        Síguenos en nuestras
                        <span>redes sociales</span>
                    </h2>

                </div>

                <div class="sinacin-redes-enlaces">

                    <a href="https://x.com/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="sinacin-red-social">
                        <span class="sinacin-red-icono">𝕏</span>
                        <span>X</span>
                    </a>

                    <a href="https://www.instagram.com/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="sinacin-red-social">
                        <span class="sinacin-red-icono">◎</span>
                        <span>Instagram</span>
                    </a>

                    <a href="https://www.youtube.com/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="sinacin-red-social">
                        <span class="sinacin-red-icono">▶</span>
                        <span>YouTube</span>
                    </a>

                    <a href="https://www.facebook.com/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="sinacin-red-social">
                        <span class="sinacin-red-icono">f</span>
                        <span>Facebook</span>
                    </a>

                    <a href="https://telegram.org/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="sinacin-red-social">
                        <span class="sinacin-red-icono">➤</span>
                        <span>Telegram</span>
                    </a>

                </div>

            </div>

        </div>

    </section>


    <!-- =========================================
        SINACIN - COLUMNA DEL PRESIDENTE
    ========================================= -->

    <?php
    $columna = get_page_by_path('columna-presidente');
    ?>

    <?php if ($columna && $columna->post_status === 'publish') : ?>

    <section class="sinacin-columna" aria-labelledby="sinacin-columna-titulo">

        <div class="sinacin-columna-contenedor">

            <header class="sinacin-columna-header">

                <span class="sinacin-columna-etiqueta">
                    LA VOZ DE SINACIN
                </span>

                <h2 id="sinacin-columna-titulo">
                    <?php echo esc_html(get_the_title($columna)); ?>
                </h2>

                <div class="sinacin-columna-linea"></div>

                <p class="sinacin-columna-autor">
                    Miguel Barrera
                    <span>Presidente SINACIN</span>
                </p>

            </header>

            <div class="sinacin-columna-texto">
                <?php
                echo apply_filters(
                    'the_content',
                    $columna->post_content
                );
                ?>
            </div>

        </div>

    </section>

    <?php endif; ?>

</main>

<?php get_footer(); ?>
