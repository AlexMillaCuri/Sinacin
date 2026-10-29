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
    wp_enqueue_style(
        'sinacin-estilos',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get('Version')
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
