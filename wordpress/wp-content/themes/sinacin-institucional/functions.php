<?php
/**
 * Configuración del tema SINACIN Institucional.
 */

if (!defined('ABSPATH')) {
    exit;
}

function sinacin_tema_configuracion() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script'
    ));

    register_nav_menus(array(
        'menu-principal' => 'Menú principal SINACIN'
    ));
}
add_action('after_setup_theme', 'sinacin_tema_configuracion');

function sinacin_tema_estilos() {

    $archivo_css = get_stylesheet_directory() . '/style.css';

    wp_enqueue_style(
        'sinacin-estilos',
        get_stylesheet_uri(),
        array(),
        filemtime($archivo_css)
    );

}

add_action('wp_enqueue_scripts', 'sinacin_tema_estilos');

function sinacin_tema_scripts() {
    wp_enqueue_script(
        'sinacin-menu',
        get_template_directory_uri() . '/assets/js/menu.js',
        array(),
        wp_get_theme()->get('Version'),
        true
    );
}
add_action('wp_enqueue_scripts', 'sinacin_tema_scripts');


/**
 * SINACIN - Banners del carrusel principal.
 */
function sinacin_registrar_banners() {
    register_post_type('sinacin_banner', array(
        'labels' => array(
            'name'          => 'Banners',
            'singular_name' => 'Banner',
            'add_new_item'  => 'Agregar nuevo banner',
            'edit_item'     => 'Editar banner',
            'all_items'     => 'Todos los banners',
            'menu_name'     => 'Banners'
        ),
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-images-alt2',
        'supports'           => array('title', 'editor', 'thumbnail', 'page-attributes'),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'rewrite'            => false
    ));
}
add_action('init', 'sinacin_registrar_banners');

/**
 * JavaScript del carrusel principal.
 */
function sinacin_cargar_carrusel() {
    if (is_front_page()) {
        wp_enqueue_script(
            'sinacin-carrusel',
            get_template_directory_uri() . '/assets/js/carrusel.js',
            array(),
            filemtime(get_template_directory() . '/assets/js/carrusel.js'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'sinacin_cargar_carrusel');


/* =========================================
   SINACIN - SCRIPT CARRUSEL NOTICIAS
========================================= */

function sinacin_cargar_noticias_js() {

    if (!is_front_page()) {
        return;
    }

    $archivo = get_template_directory() . '/assets/js/noticias.js';

    if (!file_exists($archivo)) {
        return;
    }

    wp_enqueue_script(
        'sinacin-noticias',
        get_template_directory_uri() . '/assets/js/noticias.js',
        array(),
        filemtime($archivo),
        true
    );
}

add_action('wp_enqueue_scripts', 'sinacin_cargar_noticias_js');



/* =========================================
   SINACIN - RENOMBRAR ENTRADAS A NOTICIAS
========================================= */

function sinacin_renombrar_entradas_menu() {
    global $menu, $submenu;

    foreach ($menu as &$item) {
        if (isset($item[2]) && $item[2] === 'edit.php') {
            $item[0] = 'Noticias';
            break;
        }
    }
    unset($item);

    if (isset($submenu['edit.php'][5])) {
        $submenu['edit.php'][5][0] = 'Todas las noticias';
    }

    if (isset($submenu['edit.php'][10])) {
        $submenu['edit.php'][10][0] = 'Añadir noticia';
    }
}

add_action('admin_menu', 'sinacin_renombrar_entradas_menu', 999);


function sinacin_renombrar_entradas_labels() {
    global $wp_post_types;

    if (!isset($wp_post_types['post'])) {
        return;
    }

    $labels = $wp_post_types['post']->labels;

    $labels->name               = 'Noticias';
    $labels->singular_name      = 'Noticia';
    $labels->add_new            = 'Añadir noticia';
    $labels->add_new_item       = 'Añadir nueva noticia';
    $labels->edit_item          = 'Editar noticia';
    $labels->new_item           = 'Nueva noticia';
    $labels->view_item          = 'Ver noticia';
    $labels->view_items         = 'Ver noticias';
    $labels->search_items       = 'Buscar noticias';
    $labels->not_found          = 'No se encontraron noticias';
    $labels->not_found_in_trash = 'No hay noticias en la papelera';
    $labels->all_items          = 'Todas las noticias';
    $labels->menu_name          = 'Noticias';
    $labels->name_admin_bar     = 'Noticia';
}

add_action('init', 'sinacin_renombrar_entradas_labels', 20);



/* =========================================
   SINACIN - GUÍA DE IMAGEN PARA NOTICIAS
========================================= */

function sinacin_guia_imagen_noticias() {

    add_meta_box(
        'sinacin_guia_imagen',
        'Imagen destacada - Recomendaciones',
        'sinacin_mostrar_guia_imagen',
        'post',
        'side',
        'high'
    );
}

add_action('add_meta_boxes_post', 'sinacin_guia_imagen_noticias');


function sinacin_mostrar_guia_imagen() {
    ?>
    <div style="font-size:13px;line-height:1.6;">

        <p>
            <strong>Dimensiones recomendadas:</strong>
        </p>

        <p style="
            font-size:20px;
            font-weight:700;
            color:#B30820;
            margin:8px 0;
        ">
            1200 × 750 px
        </p>

        <p>
            <strong>Proporción:</strong> 16:10 (horizontal).
        </p>

        <p>
            <strong>Formatos:</strong> JPG, PNG o WebP.
        </p>

        <p>
            Utiliza imágenes nítidas y procura
            mantener los elementos importantes
            cerca del centro para evitar recortes.
        </p>

        <p style="
            padding:10px;
            background:#f4f4f4;
            border-left:3px solid #B30820;
        ">
            La imagen destacada se utilizará
            automáticamente en el carrusel
            de noticias de SINACIN.
        </p>

    </div>
    <?php
}

/* =========================================
   SINACIN - TAMAÑO DE IMAGEN PARA NOTICIAS
========================================= */

function sinacin_registrar_imagen_noticias() {

    add_image_size(
        'sinacin-noticia',
        1200,
        750,
        true
    );
}

add_action('after_setup_theme', 'sinacin_registrar_imagen_noticias');


/* =========================================
   SINACIN - FONT AWESOME BRANDS
========================================= */

function sinacin_cargar_iconos_redes() {

    wp_enqueue_style(
        'sinacin-fontawesome-brands',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
        array(),
        '6.7.2'
    );

}

add_action('wp_enqueue_scripts', 'sinacin_cargar_iconos_redes');


/* ==========================================
   SINACIN - ADMINISTRACIÓN DE HITOS
========================================== */

/**
 * 1. Registrar el tipo de contenido Hitos SINACIN.
 */
function sinacin_registrar_hitos() {

    register_post_type('sinacin_hito', array(
        'labels' => array(
            'name'          => 'Hitos SINACIN',
            'singular_name' => 'Hito',
            'menu_name'     => 'Hitos SINACIN',
            'add_new'       => 'Agregar hito',
            'add_new_item'  => 'Agregar nuevo hito',
            'edit_item'     => 'Editar hito',
            'all_items'     => 'Todos los hitos',
            'not_found'     => 'No se encontraron hitos'
        ),
        'public'        => false,
        'show_ui'       => true,
        'show_in_menu'  => true,
        'show_in_rest'  => true,
        'menu_icon'     => 'dashicons-calendar-alt',
        'menu_position' => 6,
        'supports'      => array(
            'title',
            'editor',
            'thumbnail'
        ),
        'has_archive'   => false,
        'rewrite'       => false
    ));
}

add_action('init', 'sinacin_registrar_hitos');


/* ==========================================
   2. CAMPO FECHA DEL ACONTECIMIENTO
========================================== */

/**
 * Registrar el recuadro de fecha.
 * Se muestra en el editor de Hitos SINACIN.
 */
function sinacin_hitos_metabox() {

    add_meta_box(
        'sinacin_hito_fecha_box',
        'Fecha del acontecimiento',
        'sinacin_hitos_metabox_html',
        'sinacin_hito',
        'side',
        'high',
        array(
            '__back_compat_meta_box' => false
        )
    );
}

add_action('add_meta_boxes_sinacin_hito', 'sinacin_hitos_metabox');


/**
 * Mostrar el selector de fecha.
 */
function sinacin_hitos_metabox_html($post) {

    wp_nonce_field(
        'sinacin_guardar_hito',
        'sinacin_hito_nonce'
    );

    $fecha = get_post_meta(
        $post->ID,
        '_sinacin_hito_fecha',
        true
    );

    $anio_anterior = get_post_meta(
        $post->ID,
        '_sinacin_hito_anio',
        true
    );

    ?>

    <p>
        <label for="sinacin_hito_fecha">
            <strong>¿Cuándo ocurrió este hito?</strong>
        </label>
    </p>

    <p>
        <input
            type="date"
            id="sinacin_hito_fecha"
            name="sinacin_hito_fecha"
            value="<?php echo esc_attr($fecha); ?>"
            style="width:100%;"
        >
    </p>

    <p class="description">
        Selecciona el día, mes y año.
        El sistema obtendrá automáticamente el año
        para agrupar los hitos.
    </p>

    <?php if (empty($fecha) && !empty($anio_anterior)) : ?>

        <p>
            <strong>Año registrado anteriormente:</strong>
            <?php echo esc_html($anio_anterior); ?>
        </p>

        <p class="description">
            Selecciona la fecha completa para actualizar
            este hito.
        </p>

    <?php endif; ?>

    <?php
}


/* ==========================================
   3. GUARDAR FECHA Y AÑO
========================================== */

function sinacin_guardar_hito_datos($post_id) {

    // Verificar seguridad.
    if (
        !isset($_POST['sinacin_hito_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(
                wp_unslash($_POST['sinacin_hito_nonce'])
            ),
            'sinacin_guardar_hito'
        )
    ) {
        return;
    }

    // Evitar autoguardados.
    if (
        defined('DOING_AUTOSAVE') &&
        DOING_AUTOSAVE
    ) {
        return;
    }

    // Verificar permisos.
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (get_post_type($post_id) !== 'sinacin_hito') {
        return;
    }

    if (!isset($_POST['sinacin_hito_fecha'])) {
        return;
    }

    $fecha = sanitize_text_field(
        wp_unslash($_POST['sinacin_hito_fecha'])
    );

    // Validar fecha real.
    $fecha_objeto = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $fecha
    );

    if (
        $fecha_objeto &&
        $fecha_objeto->format('Y-m-d') === $fecha
    ) {

        $anio = $fecha_objeto->format('Y');

        update_post_meta(
            $post_id,
            '_sinacin_hito_fecha',
            $fecha
        );

        update_post_meta(
            $post_id,
            '_sinacin_hito_anio',
            $anio
        );

    } elseif ($fecha === '') {

        delete_post_meta(
            $post_id,
            '_sinacin_hito_fecha'
        );

        delete_post_meta(
            $post_id,
            '_sinacin_hito_anio'
        );
    }
}

add_action(
    'save_post_sinacin_hito',
    'sinacin_guardar_hito_datos'
);


/* ==========================================
   4. MOSTRAR FECHA EN ADMINISTRACIÓN
========================================== */

/**
 * Agregar columna Fecha del hito.
 */
function sinacin_hitos_columnas($columnas) {

    $nuevas = array();

    foreach ($columnas as $clave => $titulo) {

        if ($clave === 'date') {
            continue;
        }

        $nuevas[$clave] = $titulo;

        if ($clave === 'title') {
            $nuevas['fecha_hito'] = 'Fecha del hito';
        }
    }

    return $nuevas;
}

add_filter(
    'manage_sinacin_hito_posts_columns',
    'sinacin_hitos_columnas'
);


/**
 * Mostrar la fecha de cada acontecimiento.
 */
function sinacin_hitos_columna_fecha($columna, $post_id) {

    if ($columna !== 'fecha_hito') {
        return;
    }

    $fecha = get_post_meta(
        $post_id,
        '_sinacin_hito_fecha',
        true
    );

    if (!empty($fecha)) {

        $timestamp = strtotime($fecha . ' 12:00:00');

        echo esc_html(
            wp_date(
                'd/m/Y',
                $timestamp
            )
        );

    } else {

        $anio = get_post_meta(
            $post_id,
            '_sinacin_hito_anio',
            true
        );

        echo $anio
            ? esc_html($anio . ' (sin fecha completa)')
            : 'Sin fecha';
    }
}

add_action(
    'manage_sinacin_hito_posts_custom_column',
    'sinacin_hitos_columna_fecha',
    10,
    2
);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'sinacin-fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
        array(),
        '6.7.2'
    );
});


/**
 * ==========================================
 * SINACIN - MENSAJES DE CONTACTO
 * ==========================================
 */

function sinacin_registrar_mensajes_contacto() {

    register_post_type('sinacin_mensaje', array(

        'labels' => array(
            'name'          => 'Mensajes de contacto',
            'singular_name' => 'Mensaje',
            'menu_name'     => 'Mensajes de contacto',
            'all_items'     => 'Todos los mensajes',
            'edit_item'     => 'Ver mensaje',
            'search_items'  => 'Buscar mensajes',
            'not_found'     => 'No hay mensajes recibidos',
        ),

        'public'              => false,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => false,
        'exclude_from_search' => true,

        'menu_icon'     => 'dashicons-email-alt',
        'menu_position' => 26,

        'supports' => array('title', 'editor'),

        
        'capability_type' => 'post',

        'capabilities' => array(
            'edit_posts'             => 'sinacin_ver_mensajes',
            'edit_others_posts'      => 'sinacin_ver_mensajes',
            'edit_published_posts'   => 'sinacin_ver_mensajes',
            'edit_private_posts'     => 'sinacin_ver_mensajes',
            'read_private_posts'     => 'sinacin_ver_mensajes',

            'publish_posts'          => 'do_not_allow',
            'delete_posts'           => 'do_not_allow',
            'delete_others_posts'    => 'do_not_allow',
            'delete_published_posts' => 'do_not_allow',
            'delete_private_posts'   => 'do_not_allow',
            'create_posts'           => 'do_not_allow',
        ),

        'map_meta_cap' => true,


    ));
}

add_action(
    'init',
    'sinacin_registrar_mensajes_contacto'
);


/**
 * Mostrar datos del remitente.
 */

function sinacin_mensaje_contacto_datos() {

    global $post;

    if (!$post || $post->post_type !== 'sinacin_mensaje') {
        return;
    }

    $nombre = get_post_meta(
        $post->ID,
        '_sinacin_contacto_nombre',
        true
    );

    $correo = get_post_meta(
        $post->ID,
        '_sinacin_contacto_correo',
        true
    );

    $telefono = get_post_meta($post->ID, '_sinacin_contacto_telefono', true);
    $rut = get_post_meta($post->ID, '_sinacin_contacto_rut', true);

    $asunto = get_post_meta(
        $post->ID,
        '_sinacin_contacto_asunto',
        true
    );

    echo '<p><strong>Nombre:</strong> '
        . esc_html($nombre)
        . '</p>';

    echo '<p><strong>Correo:</strong> '
        . esc_html($correo)
        . '</p>';

    echo '<p><strong>Teléfono:</strong> '
        . esc_html($telefono !== '' ? $telefono : 'No informado')
        . '</p>';

    echo '<p><strong>RUT:</strong> '
        . esc_html($rut !== '' ? $rut : 'No informado')
        . '</p>';

    echo '<p><strong>Asunto:</strong> '
        . esc_html($asunto)
        . '</p>';
}


/**
 * Agregar panel informativo al mensaje.
 */

function sinacin_mensaje_contacto_metabox() {

    add_meta_box(
        'sinacin_mensaje_datos',
        'Datos del remitente',
        'sinacin_mensaje_contacto_datos',
        'sinacin_mensaje',
        'normal',
        'high'
    );
}

add_action(
    'add_meta_boxes',
    'sinacin_mensaje_contacto_metabox'
);


/**
 * ==========================================
 * SINACIN - CLOUDFLARE TURNSTILE
 * ==========================================
 */
function sinacin_turnstile_site_key() {
    return trim((string) getenv('SINACIN_TURNSTILE_SITE_KEY'));
}

function sinacin_turnstile_verificar() {
    $secret = trim((string) getenv('SINACIN_TURNSTILE_SECRET_KEY'));
    $token = isset($_POST['cf-turnstile-response']) && is_string($_POST['cf-turnstile-response'])
        ? sanitize_text_field(wp_unslash($_POST['cf-turnstile-response']))
        : '';

    // Sin configuración o sin token, no se aceptan envíos.
    if ($secret === '' || $token === '' || strlen($token) > 2048) {
        return false;
    }

    $respuesta = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
        'timeout' => 10,
        'body' => array(
            'secret'   => $secret,
            'response' => $token,
        ),
    ));

    if (is_wp_error($respuesta) || wp_remote_retrieve_response_code($respuesta) !== 200) {
        return false;
    }

    $datos = json_decode(wp_remote_retrieve_body($respuesta), true);
    return is_array($datos) && !empty($datos['success']);
}

add_action('wp_enqueue_scripts', function () {
    if (is_page('contacto') && sinacin_turnstile_site_key() !== '') {
        wp_enqueue_script(
            'sinacin-cloudflare-turnstile',
            'https://challenges.cloudflare.com/turnstile/v0/api.js',
            array(),
            null,
            true
        );
    }
});

/**
 * ==========================================
 * SINACIN - RECIBIR FORMULARIO CONTACTO
 * ==========================================
 */

add_action('admin_post_nopriv_sinacin_enviar_contacto', 'sinacin_procesar_contacto');
add_action('admin_post_sinacin_enviar_contacto', 'sinacin_procesar_contacto');

/**
 * Validar RUT chileno (campo opcional del formulario de contacto).
 */
function sinacin_contacto_rut_valido($rut) {
    $rut = strtoupper(preg_replace('/[.\s-]/', '', $rut));
    if (!preg_match('/^([0-9]{7,8})([0-9K])$/', $rut, $partes)) {
        return false;
    }

    $numero = $partes[1];
    $suma = 0;
    $factor = 2;
    for ($i = strlen($numero) - 1; $i >= 0; $i--) {
        $suma += (int) $numero[$i] * $factor;
        $factor = $factor === 7 ? 2 : $factor + 1;
    }

    $dv = 11 - ($suma % 11);
    $esperado = $dv === 11 ? '0' : ($dv === 10 ? 'K' : (string) $dv);
    return $partes[2] === $esperado;
}

function sinacin_procesar_contacto() {

    $volver = home_url('/contacto/');

    // Verificar seguridad del formulario.
    if (
        !isset($_POST['sinacin_contacto_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['sinacin_contacto_nonce'])),
            'sinacin_contacto'
        )
    ) {
        wp_die('No se pudo verificar el formulario.');
    }

    // Campo oculto antispam.
    if (!empty($_POST['contacto_web'])) {
        wp_safe_redirect(add_query_arg('estado', 'enviado', $volver));
        exit;
    }

    // Recibir y limpiar los datos.
    $nombre = sanitize_text_field(
        wp_unslash($_POST['contacto_nombre'] ?? '')
    );

    $correo = sanitize_email(
        wp_unslash($_POST['contacto_correo'] ?? '')
    );

    $telefono = sanitize_text_field(
        wp_unslash($_POST['contacto_telefono'] ?? '')
    );

    $rut = sanitize_text_field(
        wp_unslash($_POST['contacto_rut'] ?? '')
    );

    $asunto = sanitize_text_field(
        wp_unslash($_POST['contacto_asunto'] ?? '')
    );

    $mensaje = sanitize_textarea_field(
        wp_unslash($_POST['contacto_mensaje'] ?? '')
    );

    $asuntos_permitidos = array(
        'Consulta general',
        'Afiliación sindical',
        'Beneficios',
        'Asesoría laboral',
        'Reclamos y sugerencias',
        'Otro'
    );

    // Validar campos obligatorios.
    if (
        empty($nombre) ||
        strlen($nombre) > 120 ||
        !is_email($correo) ||
        strlen($correo) > 190 ||
        ($telefono !== '' && (
            strlen($telefono) > 20 ||
            !preg_match('/^(?:\+?56[ ]?)?9[ ]?[0-9]{4}[ ]?[0-9]{4}$/', $telefono)
        )) ||
        ($rut !== '' && (strlen($rut) > 12 || !sinacin_contacto_rut_valido($rut))) ||
        !in_array($asunto, $asuntos_permitidos, true) ||
        empty($mensaje) ||
        strlen($mensaje) > 5000
    ) {
        wp_safe_redirect(add_query_arg('estado', 'error', $volver));
        exit;
    }

    // Limitar envíos repetidos.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $limite = 'sinacin_contacto_' . md5($ip);

    if (get_transient($limite)) {
        wp_safe_redirect(add_query_arg('estado', 'limite', $volver));
        exit;
    }

    // Validación obligatoria de Cloudflare Turnstile.
    if (!sinacin_turnstile_verificar()) {
        wp_safe_redirect(add_query_arg('estado', 'captcha', $volver));
        exit;
    }

    // Guardar mensaje en WordPress.
    $mensaje_id = wp_insert_post(array(
        'post_type'    => 'sinacin_mensaje',
        'post_status'  => 'private',
        'post_title'   => $asunto . ' - ' . $nombre,
        'post_content' => $mensaje,
        'meta_input'   => array(
            '_sinacin_contacto_nombre' => $nombre,
            '_sinacin_contacto_correo' => $correo,
            '_sinacin_contacto_telefono' => $telefono,
            '_sinacin_contacto_rut' => $rut,
            '_sinacin_contacto_asunto' => $asunto,
        ),
    ), true);

    if (is_wp_error($mensaje_id) || !$mensaje_id) {
        wp_safe_redirect(add_query_arg('estado', 'error', $volver));
        exit;
    }

    
    /**
     * NOTIFICACIÓN POR CORREO
     */

    $destinatario = 'support@sinacin.cl';

    $titulo_correo = 'SINACIN - Nuevo mensaje de contacto';

    $cuerpo_correo =
        "Se ha recibido un nuevo mensaje desde SINACIN.\n\n" .
        "Nombre: {$nombre}\n" .
        "Correo: {$correo}\n" .
        "Teléfono: " . ($telefono !== '' ? $telefono : 'No informado') . "\n" .
        "RUT: " . ($rut !== '' ? $rut : 'No informado') . "\n" .
        "Asunto: {$asunto}\n\n" .
        "Mensaje:\n{$mensaje}\n\n" .
        "Puedes revisar el mensaje en WordPress.";

    $encabezados = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $correo
    );

    wp_mail(
        $destinatario,
        $titulo_correo,
        $cuerpo_correo,
        $encabezados
    );


    // Evitar envíos consecutivos durante 60 segundos.
    set_transient($limite, true, MINUTE_IN_SECONDS);

    wp_safe_redirect(add_query_arg('estado', 'enviado', $volver));
    exit;
}


/**
 * ==========================================
 * SINACIN - MENSAJES SOLO LECTURA
 * ==========================================
 */

// Quitar edición y edición rápida del listado.
add_filter('post_row_actions', function ($acciones, $post) {

    if ($post->post_type === 'sinacin_mensaje') {
        unset($acciones['edit']);
        unset($acciones['inline hide-if-no-js']);
    }

    return $acciones;

}, 10, 2);


// Evitar cambios mediante acciones masivas.
add_filter('bulk_actions-edit-sinacin_mensaje', function ($acciones) {

    unset($acciones['edit']);

    return $acciones;
});


// Evitar edición rápida desde el administrador.
add_filter('quick_edit_enabled_for_post_type', function ($habilitado, $tipo) {

    if ($tipo === 'sinacin_mensaje') {
        return false;
    }

    return $habilitado;

}, 10, 2);


/**
 * ==========================================
 * SINACIN - VISUALIZAR MENSAJES RECIBIDOS
 * SOLO LECTURA
 * ==========================================
 */

// Reemplazar el editor por una vista de mensaje.
add_action('add_meta_boxes_sinacin_mensaje', function () {

    remove_post_type_support('sinacin_mensaje', 'editor');

    remove_meta_box(
        'submitdiv',
        'sinacin_mensaje',
        'side'
    );

    remove_meta_box(
        'sinacin_mensaje_datos',
        'sinacin_mensaje',
        'normal'
    );

    add_meta_box(
        'sinacin_mensaje_lectura',
        'Mensaje recibido',
        'sinacin_mostrar_mensaje_lectura',
        'sinacin_mensaje',
        'normal',
        'high'
    );
}, 99);


// Mostrar la información sin campos editables.
function sinacin_mostrar_mensaje_lectura($post) {

    $nombre = get_post_meta(
        $post->ID,
        '_sinacin_contacto_nombre',
        true
    );

    $correo = get_post_meta(
        $post->ID,
        '_sinacin_contacto_correo',
        true
    );

    $telefono = get_post_meta($post->ID, '_sinacin_contacto_telefono', true);
    $rut = get_post_meta($post->ID, '_sinacin_contacto_rut', true);

    $asunto = get_post_meta(
        $post->ID,
        '_sinacin_contacto_asunto',
        true
    );

    $fecha = get_the_date(
        'd/m/Y H:i',
        $post
    );

    echo '<div style="padding:15px;font-size:15px;line-height:1.8;">';

    echo '<p><strong>Nombre:</strong> '
        . esc_html($nombre) . '</p>';

    echo '<p><strong>Correo:</strong> '
        . esc_html($correo) . '</p>';

    echo '<p><strong>Teléfono:</strong> '
        . esc_html($telefono !== '' ? $telefono : 'No informado') . '</p>';

    echo '<p><strong>RUT:</strong> '
        . esc_html($rut !== '' ? $rut : 'No informado') . '</p>';

    echo '<p><strong>Asunto:</strong> '
        . esc_html($asunto) . '</p>';

    echo '<p><strong>Fecha:</strong> '
        . esc_html($fecha) . '</p>';

    echo '<hr style="margin:20px 0;">';

    echo '<h3>Contenido del mensaje</h3>';

    echo '<div style="
        background:#f8f9fb;
        padding:20px;
        border-radius:8px;
        white-space:pre-wrap;
    ">' . esc_html($post->post_content) . '</div>';

    echo '</div>';
}


// Ocultar título editable y controles de publicación.
add_action('admin_head-post.php', function () {

    $pantalla = get_current_screen();

    if (
        !$pantalla ||
        $pantalla->post_type !== 'sinacin_mensaje'
    ) {
        return;
    }

    echo '<style>
        #titlediv,
        #submitdiv,
        #post-body-content,
        #edit-slug-box {
            display:none !important;
        }

        #post-body.columns-2 {
            margin-right:0 !important;
        }

        #postbox-container-1 {
            display:none !important;
        }

        #postbox-container-2 {
            width:100% !important;
        }
    </style>';
});


// Bloquear guardados desde el editor de WordPress.
add_filter('wp_insert_post_data', function ($datos, $postarr) {

    if (
        is_admin() &&
        ($datos['post_type'] ?? '') === 'sinacin_mensaje' &&
        !empty($postarr['ID']) &&
        !wp_doing_ajax() &&
        isset($_POST['action']) &&
        $_POST['action'] === 'editpost'
    ) {
        $original = get_post(
            (int) $postarr['ID'],
            ARRAY_A
        );

        if ($original) {
            $datos['post_title']   = $original['post_title'];
            $datos['post_content'] = $original['post_content'];
            $datos['post_status']  = $original['post_status'];
        }
    }

    return $datos;

}, 10, 2);


/**
 * ==========================================
 * SINACIN - ESTADO DE LECTURA DE MENSAJES
 * ==========================================
 */

// Agregar columna "Estado".
add_filter('manage_sinacin_mensaje_posts_columns', function ($columnas) {

    $nuevas = array();

    foreach ($columnas as $clave => $titulo) {
        $nuevas[$clave] = $titulo;

        if ($clave === 'title') {
            $nuevas['sinacin_estado'] = 'Estado';
        }
    }

    return $nuevas;
});


// Mostrar Nuevo o Leído.
add_action('manage_sinacin_mensaje_posts_custom_column', function ($columna, $post_id) {

    if ($columna !== 'sinacin_estado') {
        return;
    }

    $leido = get_post_meta(
        $post_id,
        '_sinacin_mensaje_leido',
        true
    );

    if ($leido === '1') {
        echo '<span style="
            background:#e9edf2;
            color:#465466;
            padding:5px 12px;
            border-radius:20px;
            font-weight:600;
        ">Leído</span>';
    } else {
        echo '<span style="
            background:#fff1d6;
            color:#975b00;
            padding:5px 12px;
            border-radius:20px;
            font-weight:700;
        ">● Nuevo</span>';
    }

}, 10, 2);


// Marcar como leído cuando se abre el mensaje.
add_action('load-post.php', function () {

    if (!current_user_can('sinacin_ver_mensajes')) {
        return;
    }

    $post_id = isset($_GET['post'])
        ? absint($_GET['post'])
        : 0;

    if (!$post_id || get_post_type($post_id) !== 'sinacin_mensaje') {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    update_post_meta(
        $post_id,
        '_sinacin_mensaje_leido',
        '1'
    );
});


// Resaltar mensajes sin leer.
add_action('admin_head-edit.php', function () {

    $pantalla = get_current_screen();

    if (!$pantalla || $pantalla->post_type !== 'sinacin_mensaje') {
        return;
    }

    echo '<style>
        .column-sinacin_estado {
            width:120px;
        }
    </style>';
});


/**
 * ==========================================
 * SINACIN - BOTÓN VOLVER A MENSAJES
 * ==========================================
 */

add_action('edit_form_top', function ($post) {

    if ($post->post_type !== 'sinacin_mensaje') {
        return;
    }

    $url = admin_url(
        'edit.php?post_type=sinacin_mensaje'
    );

    echo '<div style="margin:0 0 20px;">';

    echo '<a href="' . esc_url($url) . '"
        class="button button-secondary"
        style="
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:6px 16px;
            height:auto;
            font-weight:600;
        ">
        <span class="dashicons dashicons-arrow-left-alt"
              style="line-height:24px;"></span>
        Volver a mensajes
    </a>';

    echo '</div>';

});

/**
 * ==========================================
 * SINACIN - TIPOGRAFÍA INSTITUCIONAL
 * ==========================================
 */

add_action('wp_enqueue_scripts', 'sinacin_cargar_tipografia');

function sinacin_cargar_tipografia() {

    wp_enqueue_style(
        'sinacin-google-fonts',
        'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );
}

/**
 * SINACIN - Tipografía del panel administrativo.
 */
add_action('admin_enqueue_scripts', function () {

    wp_enqueue_style(
        'sinacin-admin-font',
        'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );

    wp_add_inline_style(
        'sinacin-admin-font',
        '
        body.wp-admin,
        body.wp-admin input,
        body.wp-admin button,
        body.wp-admin select,
        body.wp-admin textarea {
            font-family: "Manrope", sans-serif;
        }
        '
    );
});

/**
 * ==========================================
 * SINACIN - LOGO PERSONALIZADO LOGIN
 * ==========================================
 */

add_action('login_enqueue_scripts', 'sinacin_personalizar_logo_login');

function sinacin_personalizar_logo_login() {

    $logo = get_template_directory_uri() . '/assets/images/logo-sinacin-2.png';

    ?>
    <style>
        body.login #login h1 a {
            background-image: url('<?php echo esc_url($logo); ?>');
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;

            width: 220px;
            height: 110px;

            margin: 0 auto 25px;
        }
    </style>
    <?php
}

/**
 * Cambiar el enlace del logo al sitio SINACIN.
 */
add_filter('login_headerurl', function () {
    return home_url('/');
});

/**
 * Cambiar el texto descriptivo del logo.
 */
add_filter('login_headertext', function () {
    return 'SINACIN - Sindicato Nacional';
});

/**
 * SINACIN - Video de YouTube opcional por banner.
 * Campos disponibles en el editor de cada banner.
 */
function sinacin_banner_video_metabox() {
    add_meta_box(
        'sinacin_banner_video',
        'Video de YouTube del banner',
        'sinacin_banner_video_metabox_html',
        'sinacin_banner',
        'side',
        'high'
    );
}
add_action('add_meta_boxes_sinacin_banner', 'sinacin_banner_video_metabox');

function sinacin_banner_video_metabox_html($post) {
    $url = (string) get_post_meta($post->ID, '_sinacin_banner_video_url', true);
    $posicion = get_post_meta($post->ID, '_sinacin_banner_video_posicion', true);
    if (!in_array($posicion, array('izquierda', 'derecha'), true)) {
        $posicion = 'derecha';
    }
    wp_nonce_field('sinacin_banner_video_guardar', 'sinacin_banner_video_nonce');
    ?>
    <p><label for="sinacin_banner_video_url"><strong>Enlace de YouTube</strong></label></p>
    <p><input type="url" id="sinacin_banner_video_url" name="sinacin_banner_video_url"
        value="<?php echo esc_attr($url); ?>" placeholder="https://youtu.be/..."
        style="width:100%;" /></p>
    <p class="description">Opcional. Acepta enlaces de YouTube y YouTube Shorts. Si está vacío, el banner no mostrará video.</p>
    <p><strong>Posición del reproductor</strong></p>
    <p><label><input type="radio" name="sinacin_banner_video_posicion" value="izquierda"
        <?php checked($posicion, 'izquierda'); ?> /> Izquierda</label></p>
    <p><label><input type="radio" name="sinacin_banner_video_posicion" value="derecha"
        <?php checked($posicion, 'derecha'); ?> /> Derecha</label></p>
    <?php
}

/** Devuelve el ID de un video YouTube a partir de una URL permitida. */
function sinacin_banner_youtube_id($url) {
    $url = trim((string) $url);
    if ($url === '' || strlen($url) > 2048) {
        return '';
    }
    $partes = wp_parse_url($url);
    if (!is_array($partes) || !in_array(strtolower($partes['scheme'] ?? ''), array('http', 'https'), true)) {
        return '';
    }
    $host = strtolower($partes['host'] ?? '');
    $host = preg_replace('/^www\\./', '', $host);
    $ruta = trim($partes['path'] ?? '', '/');
    $id = '';
    if ($host === 'youtu.be') {
        $id = explode('/', $ruta)[0];
    } elseif (in_array($host, array('youtube.com', 'm.youtube.com', 'youtube-nocookie.com'), true)) {
        if ($ruta === 'watch') {
            parse_str($partes['query'] ?? '', $parametros);
            $id = is_string($parametros['v'] ?? null) ? $parametros['v'] : '';
        } elseif (preg_match('~^(?:embed|shorts|live)/([^/]+)~', $ruta, $coincidencias)) {
            $id = $coincidencias[1];
        }
    }
    return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : '';
}

function sinacin_banner_video_guardar($post_id) {
    if (!isset($_POST['sinacin_banner_video_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sinacin_banner_video_nonce'])), 'sinacin_banner_video_guardar') ||
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        wp_is_post_revision($post_id) ||
        !current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['sinacin_banner_video_url'])) {
        $url = esc_url_raw(trim((string) wp_unslash($_POST['sinacin_banner_video_url'])));
        if ($url === '') {
            delete_post_meta($post_id, '_sinacin_banner_video_url');
        } elseif (sinacin_banner_youtube_id($url) !== '') {
            update_post_meta($post_id, '_sinacin_banner_video_url', $url);
        }
    }
    if (isset($_POST['sinacin_banner_video_posicion'])) {
        $posicion = sanitize_key(wp_unslash($_POST['sinacin_banner_video_posicion']));
        if (in_array($posicion, array('izquierda', 'derecha'), true)) {
            update_post_meta($post_id, '_sinacin_banner_video_posicion', $posicion);
        }
    }
}
add_action('save_post_sinacin_banner', 'sinacin_banner_video_guardar');

/** Estilos exclusivos de la portada, sin modificar style.css. */
function sinacin_banner_video_estilos() {
    if (!is_front_page()) {
        return;
    }
    wp_add_inline_style('sinacin-estilos', '
        .sinacin-slide { position: relative; }
        .sinacin-slide.sinacin-slide-con-video .sinacin-slide-content {
            position: relative;
            z-index: 2;
            pointer-events: none;
        }
        .sinacin-slide-video {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: min(34vw, 560px);
            max-width: calc(50% - 24px);
            z-index: 5;
            aspect-ratio: 16 / 9;
            border-radius: 16px;
            overflow: hidden;
            background: #080808;
            box-shadow: 0 12px 36px rgba(0,0,0,.35);
        }
        .sinacin-slide-video--izquierda { left: clamp(20px, 4vw, 80px); }
        .sinacin-slide-video--derecha { right: clamp(20px, 4vw, 80px); }
        .sinacin-slide-video iframe { display: block; width: 100%; height: 100%; border: 0; }
        @media (max-width: 767px) {
            .sinacin-slide.sinacin-slide-con-video { min-height: 380px; }
            .sinacin-slide-video {
                top: 50%; left: 50%; right: auto;
                transform: translate(-50%, -50%);
                width: calc(100% - 32px);
                max-width: 520px;
                border-radius: 10px;
            }
            .sinacin-slide.sinacin-slide-con-video .sinacin-slide-content { display: none; }
        }
    ');
}
add_action('wp_enqueue_scripts', 'sinacin_banner_video_estilos', 20);

/** Detener reproducción si el usuario cambia de banner. */
function sinacin_banner_video_pausar_script() {
    if (!is_front_page()) {
        return;
    }
    wp_add_inline_script('sinacin-carrusel', '
        document.addEventListener("DOMContentLoaded", function () {
            var track = document.querySelector(".sinacin-slider-track");
            if (!track) return;
            var observar = function () {
                track.querySelectorAll(".sinacin-slide:not(.active) .sinacin-slide-video iframe").forEach(function (iframe) {
                    if (iframe.contentWindow) iframe.contentWindow.postMessage(JSON.stringify({event:"command",func:"pauseVideo",args:[]}), "https://www.youtube-nocookie.com");
                });
            };
            new MutationObserver(observar).observe(track, {attributes:true, attributeFilter:["class"], subtree:true});
        });
    ');
}
add_action('wp_enqueue_scripts', 'sinacin_banner_video_pausar_script', 20);
