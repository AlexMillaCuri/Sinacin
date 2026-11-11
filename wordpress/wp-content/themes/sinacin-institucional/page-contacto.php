
<?php
/**
 * Página Contacto - SINACIN
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

/*
 * DATOS INSTITUCIONALES
 *
 * Completar cuando estén confirmados.
 * No utilizar los datos de ejemplo de la imagen.
 */

$contacto = array(
    'direccion' => 'Carrera Pinto #1982, Ñuñoa, Región Metropolitana',
    'telefono'  => '+56 9 4029 4993',
    'correo'    => 'secretaria@sinacin.cl',
    'horario'   => '',

    'mapa' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3328.957933831568!2d-70.61351042359146!3d-33.450402473389666!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9662cfa3db5792eb%3A0xf2247f86d5339593!2sSindicato%20SINACIN!5e0!3m2!1ses-419!2scl!4v1793726596412!5m2!1ses-419!2scl',

    'maps_url' => 'https://www.google.com/maps/search/?api=1&query=Sindicato%20SINACIN',
);

/*
 * REDES SOCIALES
 *
 * Reemplazar las URLs vacías por las oficiales.
 */

$redes = array(
    array(
        'nombre' => 'X',
        'clase'  => 'x',
        'url'    => 'https://x.com/SinacinChile',
    ),
    array(
        'nombre' => 'Instagram',
        'clase'  => 'instagram',
        'url'    => 'https://www.instagram.com/sinacin.chile/',
    ),
    array(
        'nombre' => 'YouTube',
        'clase'  => 'youtube',
        'url'    => 'https://www.youtube.com/@sinacinchile141',
    ),
    array(
        'nombre' => 'Facebook',
        'clase'  => 'facebook',
        'url'    => 'https://www.facebook.com/profile.php?id=100057304974547#',
    ),
    array(
        'nombre' => 'Telegram',
        'clase'  => 'telegram',
        'url'    => 'https://t.me/SinaciNoticias',
    ),
);
?>

<main class="sinacin-contacto">

    <div class="container">
        
        <header class="sinacin-contacto-encabezado">

            <h1 class="sinacin-contacto-titulo-principal">
                <span>Contacto</span>
            </h1>

            <p>¿Tienes una consulta? Escríbenos.</p>

        </header>

        <div class="sinacin-contacto-layout">

            <!-- =========================
                 FORMULARIO DE CONTACTO
            ========================== -->

            <section class="sinacin-contacto-panel sinacin-contacto-formulario">

                <header class="sinacin-contacto-titulo">
                    <h2>Formulario de contacto</h2>
                    <span class="sinacin-contacto-linea"></span>
                </header>

                
                <?php if (
                    isset($_GET['estado']) &&
                    sanitize_key(wp_unslash($_GET['estado'])) === 'enviado'
                ) : ?>

                    <div class="sinacin-contacto-exito">
                        <i class="fa-solid fa-circle-check"></i>

                        <h2>¡Mensaje enviado con éxito!</h2>

                        <p>
                            Tu consulta fue recibida correctamente por SINACIN.
                            Nos pondremos en contacto contigo a la brevedad.
                        </p>
                    </div>

                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const url = new URL(window.location.href);
                        if (url.searchParams.get('estado') === 'enviado') {
                            url.searchParams.delete('estado');
                            window.history.replaceState({}, '', url.pathname + url.search + url.hash);
                        }
                    });
                    </script>

                <?php else : ?>


                
                <form
                    id="sinacin-contacto-form"
                    class="sinacin-contacto-form"
                    method="post"
                    action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                >

                    <input type="hidden" name="action" value="sinacin_enviar_contacto">

                    <?php wp_nonce_field('sinacin_contacto', 'sinacin_contacto_nonce'); ?>

                    <div style="position:absolute;left:-9999px;" aria-hidden="true">
                        <label for="contacto_web">Sitio web</label>
                        <input type="text" id="contacto_web" name="contacto_web" tabindex="-1" autocomplete="off">
                    </div>


                    <?php
                    $estado = isset($_GET['estado'])
                        ? sanitize_key(wp_unslash($_GET['estado']))
                        : '';

                    $avisos = array(
                        'enviado' => 'Tu mensaje fue recibido correctamente. Nos pondremos en contacto contigo.',
                        'error'   => 'No fue posible recibir tu mensaje. Revisa los datos e inténtalo nuevamente.',
                        'limite'  => 'Espera un minuto antes de enviar otro mensaje.',
                        'captcha' => 'No pudimos verificar que eres una persona. Inténtalo nuevamente.',
                    );
                    ?>

                    <?php if (isset($avisos[$estado])) : ?>
                        <div
                            id="sinacin-contacto-resultado"
                            class="sinacin-contacto-resultado <?php echo esc_attr($estado === 'enviado' ? 'exito' : 'error'); ?>"
                            role="status"
                            aria-live="polite"
                        >
                            <?php echo esc_html($avisos[$estado]); ?>
                        </div>
                    <?php endif; ?>

                    <div class="sinacin-contacto-campos-dobles">

                        <div class="sinacin-contacto-campo">

                            <label for="contacto_nombre">
                                Nombre completo
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                id="contacto_nombre"
                                name="contacto_nombre"
                                placeholder="Ingresa tu nombre"
                                autocomplete="name"
                                maxlength="120"
                                required
                            >

                        </div>

                        <div class="sinacin-contacto-campo">

                            <label for="contacto_correo">
                                Correo electrónico
                                <span>*</span>
                            </label>

                            <input
                                type="email"
                                id="contacto_correo"
                                name="contacto_correo"
                                placeholder="tu correo@ejemplo.cl"
                                autocomplete="email"
                                maxlength="190"
                                required
                            >

                        </div>

                    </div>

                    <!-- Datos adicionales opcionales -->
                    <div class="sinacin-contacto-campos-dobles">
                        <div class="sinacin-contacto-campo">
                            <label for="contacto_telefono">Teléfono <small>(opcional)</small></label>
                            <input type="tel" id="contacto_telefono" name="contacto_telefono"
                                   placeholder="+56 9 1234 5678" autocomplete="tel"
                                   inputmode="tel" maxlength="20"
                                   pattern="(?:\+?56[ ]?)?9[ ]?[0-9]{4}[ ]?[0-9]{4}"
                                   title="Ingresa un celular chileno, por ejemplo +56 9 1234 5678">
                            <small>Celular chileno, con o sin +56.</small>
                        </div>
                        <div class="sinacin-contacto-campo">
                            <label for="contacto_rut">RUT <small>(opcional)</small></label>
                            <input type="text" id="contacto_rut" name="contacto_rut"
                                   placeholder="12.345.678-9" maxlength="12"
                                   autocomplete="off" inputmode="text"
                                   pattern="[0-9]{1,2}\.?[0-9]{3}\.?[0-9]{3}-?[0-9kK]"
                                   title="Ingresa un RUT chileno válido, por ejemplo 12.345.678-9">
                            <small>Opcional. Se verificará el dígito verificador al enviar.</small>
                        </div>
                    </div>

                    <div class="sinacin-contacto-campo">

                        <label for="contacto_asunto">
                            Asunto
                            <span>*</span>
                        </label>

                        <select
                            id="contacto_asunto"
                            name="contacto_asunto"
                            required
                        >
                            <option value="">
                                Selecciona una opción
                            </option>

                            <option value="Consulta general">
                                Consulta general
                            </option>

                            <option value="Afiliación sindical">
                                Afiliación sindical
                            </option>

                            <option value="Beneficios">
                                Beneficios
                            </option>

                            <option value="Asesoría laboral">
                                Asesoría laboral
                            </option>

                            <option value="Reclamos y sugerencias">
                                Reclamos y sugerencias
                            </option>

                            <option value="Otro">
                                Otro
                            </option>

                        </select>

                    </div>

                    <div class="sinacin-contacto-campo">

                        <label for="contacto_mensaje">
                            Mensaje
                            <span>*</span>
                        </label>

                        <textarea
                            id="contacto_mensaje"
                            name="contacto_mensaje"
                            rows="6"
                            maxlength="5000"
                            placeholder="Escribe tu mensaje aquí..."
                            required
                        ></textarea>

                    </div>

                    <?php $turnstile_site_key = function_exists('sinacin_turnstile_site_key') ? sinacin_turnstile_site_key() : ''; ?>
                    <?php if ($turnstile_site_key !== '') : ?>
                        <div class="sinacin-contacto-turnstile" style="margin:20px 0;">
                            <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($turnstile_site_key); ?>"></div>
                        </div>
                    <?php else : ?>
                        <p role="alert">La verificación antispam no está configurada. Inténtalo más tarde.</p>
                    <?php endif; ?>

                    <div class="sinacin-contacto-form-footer">

                        <p>
                            * Campos obligatorios
                        </p>

                        <button type="submit" class="sinacin-contacto-enviar">
                            <span aria-hidden="true">➤</span> Enviar mensaje
                        </button>

                    </div>

                </form>

                <?php endif; ?>

            </section>

            <!-- =========================
                 INFORMACIÓN Y REDES
            ========================== -->

            <aside class="sinacin-contacto-lateral">

                <section class="sinacin-contacto-panel">

                    <header class="sinacin-contacto-titulo">
                        <h2>Información de contacto</h2>
                        <span class="sinacin-contacto-linea"></span>
                    </header>

                    <div class="sinacin-contacto-datos">

                        <div class="sinacin-contacto-dato">

                            <span class="sinacin-contacto-dato-icono">
                                <span aria-hidden="true">⌖</span>
                            </span>

                            <div>
                                <h3>Dirección</h3>
                                <p>
                                    <?php echo esc_html(
                                        $contacto['direccion']
                                        ?: ''
                                    ); ?>
                                </p>
                            </div>

                        </div>

                        <div class="sinacin-contacto-dato">

                            <span class="sinacin-contacto-dato-icono">
                                <span aria-hidden="true">☎</span>
                            </span>

                            <div>
                                <h3>Teléfono</h3>
                                <p>
                                    <?php echo esc_html(
                                        $contacto['telefono']
                                        ?: ''
                                    ); ?>
                                </p>
                            </div>

                        </div>

                        <div class="sinacin-contacto-dato">

                            <span class="sinacin-contacto-dato-icono">
                                <span aria-hidden="true">✉</span>
                            </span>

                            <div>
                                <h3>Correo electrónico</h3>
                                <p>
                                    <?php echo esc_html(
                                        $contacto['correo']
                                        ?: ''
                                    ); ?>
                                </p>
                            </div>
                        </div>

                    </div>

                    <div class="sinacin-contacto-ilustracion">
                        <img
                            src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo-sinacin-2.png'); ?>"
                            alt="Casco institucional SINACIN"
                            loading="lazy"
                        >
                    </div>

                </section>

                <!-- REDES SOCIALES -->

                <section class="sinacin-contacto-panel">

                    <header class="sinacin-contacto-titulo">
                        <h2>Síguenos en nuestras redes</h2>
                        <span class="sinacin-contacto-linea"></span>
                    </header>

                    
                   
                <div class="sinacin-contacto-redes">

                    <?php
                    $iconos = array(
                        'x' => 'fa-x-twitter',
                        'instagram' => 'fa-instagram',
                        'youtube' => 'fa-youtube',
                        'facebook' => 'fa-facebook-f',
                        'telegram' => 'fa-telegram',
                    );
                    ?>
                    <?php foreach ($redes as $red) : ?>
                        <?php $clase = $iconos[$red['clase']] ?? ''; ?>

                        <?php if (!empty($red['url'])) : ?>
                            <a
                                href="<?php echo esc_url($red['url']); ?>"
                                class="sinacin-contacto-red sinacin-red-<?php echo esc_attr($red['clase']); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="<?php echo esc_attr($red['nombre']); ?>"
                            >
                                <i class="fa-brands <?php echo esc_attr($clase); ?>" aria-hidden="true"></i>
                            </a>
                        <?php else : ?>
                            <span
                                class="sinacin-contacto-red sinacin-red-<?php echo esc_attr($red['clase']); ?>"
                                title="<?php echo esc_attr($red['nombre']); ?> - enlace pendiente"
                            >
                                <i class="fa-brands <?php echo esc_attr($clase); ?>" aria-hidden="true"></i>
                            </span>
                        <?php endif; ?>

                    <?php endforeach; ?>

                </div>



                </section>

            </aside>

        </div>

        <!-- =========================
             NUESTRA UBICACIÓN
        ========================== -->

        <section class="sinacin-contacto-ubicacion">

            <div class="sinacin-contacto-mapa">

                <?php if (!empty($contacto['mapa'])) : ?>

                    <iframe
                        src="<?php echo esc_url($contacto['mapa']); ?>"
                        title="Ubicación de SINACIN"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                    ></iframe>

                <?php else : ?>

                    <div class="sinacin-contacto-mapa-pendiente">

                        <span aria-hidden="true">⌖</span>

                        <p>
                            Mapa de ubicación de SINACIN
                        </p>

                        <small>
                            Se mostrará cuando confirmemos
                            la dirección institucional.
                        </small>

                    </div>

                <?php endif; ?>

            </div>

            <div class="sinacin-contacto-ubicacion-info">

                <header class="sinacin-contacto-titulo">
                    <h2>Nuestra ubicación</h2>
                    <span class="sinacin-contacto-linea"></span>
                </header>

                <p>
                    <?php echo esc_html(
                        $contacto['direccion']
                        ?: 'Dirección institucional por confirmar.'
                    ); ?>
                </p>

                <?php if (!empty($contacto['maps_url'])) : ?>

                    <a
                        class="sinacin-contacto-maps-btn"
                        href="<?php echo esc_url($contacto['maps_url']); ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span aria-hidden="true">⌖</span>
                        Ver en Google Maps ↗
                    </a>

                <?php else : ?>

                    <span class="sinacin-contacto-maps-btn sinacin-contacto-maps-inactivo">
                        <span aria-hidden="true">⌖</span>
                        Ver en Google Maps ↗
                    </span>

                <?php endif; ?>

            </div>

        </section>

    </div>

</main>

<script>
/** SINACIN: formato automático del RUT en el formulario de contacto. */
document.addEventListener('DOMContentLoaded', function () {
    const rut = document.getElementById('contacto_rut');
    if (!rut) return;

    function formatearRut(valor) {
        const limpio = valor.toUpperCase().replace(/[^0-9K]/g, '').slice(0, 9);
        if (limpio.length <= 1) return limpio;

        const cuerpo = limpio.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        const dv = limpio.slice(-1);
        return cuerpo + '-' + dv;
    }

    rut.addEventListener('input', function () {
        const inicio = rut.selectionStart;
        const anterior = rut.value;
        const nuevo = formatearRut(anterior);
        if (nuevo === anterior) return;

        const antesDelCursor = anterior.slice(0, inicio).replace(/[^0-9Kk]/g, '').length;
        rut.value = nuevo;
        let posicion = 0;
        let caracteres = 0;
        while (posicion < nuevo.length && caracteres < antesDelCursor) {
            if (/[0-9K]/.test(nuevo.charAt(posicion))) caracteres++;
            posicion++;
        }
        rut.setSelectionRange(posicion, posicion);
    });

    // También normaliza valores pegados o autocompletados.
    rut.addEventListener('blur', function () {
        rut.value = formatearRut(rut.value);
    });
});
</script>

<?php get_footer(); ?>
