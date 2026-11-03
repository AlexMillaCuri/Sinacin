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
