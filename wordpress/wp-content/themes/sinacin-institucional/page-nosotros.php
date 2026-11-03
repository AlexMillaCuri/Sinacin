
<?php
/**
 * Página Nosotros - SINACIN
 * Línea de tiempo dinámica de hitos.
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

/*
 * Obtener todos los hitos publicados.
 */
$consulta_hitos = new WP_Query(array(
    'post_type'      => 'sinacin_hito',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
));

/*
 * Agrupar hitos por año.
 */
$hitos_por_anio = array();

if ($consulta_hitos->have_posts()) {

    while ($consulta_hitos->have_posts()) {

        $consulta_hitos->the_post();

        $id = get_the_ID();

        $fecha = get_post_meta(
            $id,
            '_sinacin_hito_fecha',
            true
        );

        $anio = get_post_meta(
            $id,
            '_sinacin_hito_anio',
            true
        );

        // Priorizar el año de la fecha completa.
        if (
            !empty($fecha) &&
            preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)
        ) {
            $anio = substr($fecha, 0, 4);
        }

        if (empty($anio)) {
            continue;
        }

        $hitos_por_anio[$anio][] = array(
            'id'          => $id,
            'titulo'      => get_the_title(),
            'descripcion' => get_the_content(),
            'fecha'       => $fecha,
            'imagen'      => get_the_post_thumbnail_url(
                $id,
                'large'
            ),
        );
    }

    wp_reset_postdata();
}

/*
 * Ordenar los años cronológicamente.
 */
ksort($hitos_por_anio, SORT_NUMERIC);

/*
 * Ordenar los acontecimientos dentro de cada año.
 */
foreach ($hitos_por_anio as &$hitos) {

    usort($hitos, function ($a, $b) {

        $fecha_a = !empty($a['fecha'])
            ? $a['fecha']
            : '9999-12-31';

        $fecha_b = !empty($b['fecha'])
            ? $b['fecha']
            : '9999-12-31';

        return strcmp($fecha_a, $fecha_b);
    });
}

unset($hitos);

/*
 * Contar hitos para decidir si mostrar el botón.
 */
$total_hitos = 0;

foreach ($hitos_por_anio as $hitos) {
    $total_hitos += count($hitos);
}

$limite_inicial = 4;
$contador = 0;
?>

<main class="sinacin-nosotros">


<!-- ======================================
     DIRECTORIO SINACIN
====================================== -->

<section class="sinacin-directorio">

    <div class="container">

        <header class="sinacin-seccion-header">

            <h2 class="sinacin-titulo-lineas">
                <span>Directorio SINACIN</span>
            </h2>

            <p>
                Conoce a quienes conforman la dirección
                de nuestro sindicato.
            </p>

        </header>

        <?php
        /*
         * Directorio institucional.
         * Las fotografías se pueden cargar en
         * Medios > Biblioteca de WordPress.
         *
         * Reemplaza cada URL vacía por la URL
         * de la fotografía correspondiente.
         */
        $directorio = array(
            array(
                'nombre' => 'Miguel Barrera',
                'cargo'  => 'Presidente',
                'foto'   => '',
            ),
            array(
                'nombre' => 'Ismael Toro',
                'cargo'  => 'Secretario',
                'foto'   => '',
            ),
            array(
                'nombre' => 'Gonzalo Gutierrez',
                'cargo'  => 'Tesorero',
                'foto'   => '',
            ),
            array(
                'nombre' => 'Valentina Vegas',
                'cargo'  => 'Director',
                'foto'   => '',
            ),
            array(
                'nombre' => 'Luis Burgos',
                'cargo'  => 'Director',
                'foto'   => '',
            ),
        );
        ?>

        <div class="sinacin-directorio-grid">

            <?php foreach ($directorio as $persona) : ?>

                <article class="sinacin-directorio-card">

                    <div class="sinacin-directorio-foto">

                        <?php if (!empty($persona['foto'])) : ?>

                            <img
                                src="<?php echo esc_url($persona['foto']); ?>"
                                alt="<?php echo esc_attr($persona['nombre']); ?>"
                                loading="lazy"
                            >

                        <?php else : ?>

                            <div class="sinacin-directorio-placeholder">
                                <span class="dashicons dashicons-admin-users"></span>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="sinacin-directorio-info">

                        <h3>
                            <?php echo esc_html($persona['nombre']); ?>
                        </h3>

                        <span class="sinacin-directorio-linea"></span>

                        <p>
                            <?php echo esc_html($persona['cargo']); ?>
                        </p>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </div>

</section>


    <section class="sinacin-hitos">

        <div class="container">

            <header class="sinacin-hitos-header">

                <h1 class="sinacin-titulo-lineas">
                    <span>Hitos de SINACIN</span>
                </h1>

                <p>
                    Conoce los acontecimientos que han marcado
                    nuestra historia y trayectoria sindical.
                </p>

            </header>

            <?php if (!empty($hitos_por_anio)) : ?>

                <div
                    class="sinacin-timeline"
                    id="sinacin-timeline"
                >

                    <?php foreach ($hitos_por_anio as $anio => $hitos) : ?>

                        <?php
                        // Ocultar el año si todos sus hitos
                        // están inicialmente ocultos.
                        $ocultar_anio = (
                            $contador >= $limite_inicial
                        );
                        ?>

                        <div
                            class="sinacin-timeline-year <?php
                                echo $ocultar_anio
                                    ? 'sinacin-timeline-extra'
                                    : '';
                            ?>"
                        >
                            <span>
                                <?php echo esc_html($anio); ?>
                            </span>
                        </div>

                        <div class="sinacin-hitos-grid">

                        <?php foreach ($hitos as $hito) : ?>

                            <?php

                            $ocultar_hito = (
                                $contador >= $limite_inicial
                            );

                            $contador++;
                            ?>

                            <article
                                class="sinacin-hito <?php
                                    echo $ocultar_hito
                                        ? 'sinacin-timeline-extra'
                                        : '';
                                ?>"
                            >

                                <div class="sinacin-hito-card">

                                    <?php if (!empty($hito['imagen'])) : ?>

                                        <div class="sinacin-hito-imagen">

                                            <img
                                                src="<?php
                                                    echo esc_url(
                                                        $hito['imagen']
                                                    );
                                                ?>"
                                                alt="<?php
                                                    echo esc_attr(
                                                        $hito['titulo']
                                                    );
                                                ?>"
                                                loading="lazy"
                                            >

                                        </div>

                                    <?php endif; ?>

                                    <div class="sinacin-hito-info">
                                        <h2>
                                            <?php
                                            echo esc_html(
                                                $hito['titulo']
                                            );
                                            ?>
                                        </h2>

                                        <?php if (!empty($hito['descripcion'])) : ?>

                                            <div class="sinacin-hito-descripcion">
                                                <?php
                                                echo apply_filters(
                                                    'the_content',
                                                    $hito['descripcion']
                                                );
                                                ?>
                                            </div>

                                        <?php endif; ?>

                                    </div>

                                </div>

                                <span
                                    class="sinacin-hito-punto"
                                    aria-hidden="true"
                                ></span>

                            </article>

                        <?php endforeach; ?>
                        </div>

                    <?php endforeach; ?>

                </div>

                <?php if ($total_hitos > $limite_inicial) : ?>

                    <div class="sinacin-hitos-acciones">

                        <button
                            id="sinacin-ver-mas-hitos"
                            class="sinacin-hitos-mas"
                            type="button"
                            aria-controls="sinacin-timeline"
                            aria-expanded="false"
                        >
                            Ver más hitos ↓
                        </button>

                    </div>

                <?php endif; ?>

            <?php else : ?>

                <p class="sinacin-hitos-vacio">
                    Todavía no hay hitos publicados.
                </p>

            <?php endif; ?>

        </div>

    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const boton = document.getElementById(
        'sinacin-ver-mas-hitos'
    );

    const timeline = document.getElementById(
        'sinacin-timeline'
    );

    if (!boton || !timeline) {
        return;
    }

    boton.addEventListener('click', function () {

        const expandido = boton.getAttribute(
            'aria-expanded'
        ) === 'true';

        timeline.classList.toggle(
            'sinacin-timeline-expandida',
            !expandido
        );

        boton.setAttribute(
            'aria-expanded',
            String(!expandido)
        );

        boton.textContent = expandido
            ? 'Ver más hitos ↓'
            : 'Ver menos hitos ↑';

    });

});
</script>

<?php get_footer(); ?>
