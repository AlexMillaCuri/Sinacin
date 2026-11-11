<?php
/**
 * SINACIN - Roles, capacidades y acceso al panel.
 * Archivo del plugin SINACIN. No cargar también desde functions.php.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Identifica al gestor sin privilegios de administrador. */
function sinacin_es_gestor() {
    $usuario = wp_get_current_user();
    return $usuario instanceof WP_User
        && in_array( 'gestor_sinacin', (array) $usuario->roles, true )
        && ! current_user_can( 'manage_options' );
}

/** Sincroniza capacidades al actualizar el plugin. */
function sinacin_registrar_roles() {
    $rol = get_role( 'gestor_sinacin' );
    if ( ! $rol ) {
        $rol = add_role( 'gestor_sinacin', 'Gestor SINACIN', array( 'read' => true ) );
    }

    $capacidades = array(
        'read',
        'sinacin_gestionar_afiliaciones',
        'sinacin_ver_mensajes',
        'upload_files',
        // Noticias, banners e hitos usan capacidades estándar de entradas.
        'edit_posts',
        'edit_others_posts',
        'edit_published_posts',
        'publish_posts',
        'delete_posts',
        'delete_others_posts',
        'delete_published_posts',
    );

    if ( $rol ) {
        foreach ( $capacidades as $capacidad ) {
            if ( ! $rol->has_cap( $capacidad ) ) {
                $rol->add_cap( $capacidad );
            }
        }
    }

    $admin = get_role( 'administrator' );
    if ( $admin ) {
        foreach ( array( 'sinacin_gestionar_afiliaciones', 'sinacin_ver_mensajes' ) as $capacidad ) {
            if ( ! $admin->has_cap( $capacidad ) ) {
                $admin->add_cap( $capacidad );
            }
        }
    }
}

add_action( 'init', 'sinacin_actualizar_roles_si_necesario', 1 );
function sinacin_actualizar_roles_si_necesario() {
    $version = '4';
    if ( get_option( 'sinacin_roles_version' ) !== $version ) {
        sinacin_registrar_roles();
        update_option( 'sinacin_roles_version', $version, false );
    }
}

/** Destino inicial al iniciar sesión. */
add_filter( 'login_redirect', 'sinacin_redirigir_login_gestor', 10, 3 );
function sinacin_redirigir_login_gestor( $destino, $solicitado, $usuario ) {
    if ( $usuario instanceof WP_User
        && in_array( 'gestor_sinacin', (array) $usuario->roles, true )
        && ! user_can( $usuario, 'manage_options' ) ) {
        return admin_url( 'admin.php?page=sinacin-solicitudes' );
    }
    return $destino;
}

/** Menús principales visibles, en el orden solicitado. */
function sinacin_menus_gestor_permitidos() {
    return array(
        'sinacin',
        'edit.php?post_type=sinacin_banner',
        'edit.php?post_type=sinacin_mensaje',
        'edit.php?post_type=sinacin_hito',
        'edit.php',
    );
}

/** Filtra menús y submenús sin modificar los de los administradores. */
add_action( 'admin_menu', 'sinacin_ocultar_menus_gestor', 999 );
function sinacin_ocultar_menus_gestor() {
    if ( ! sinacin_es_gestor() ) {
        return;
    }

    global $menu, $submenu;
    $permitidos = sinacin_menus_gestor_permitidos();
    foreach ( (array) $menu as $entrada ) {
        if ( isset( $entrada[2] ) && ! in_array( $entrada[2], $permitidos, true ) ) {
            remove_menu_page( $entrada[2] );
        }
    }

    if ( isset( $submenu['sinacin'] ) ) {
        $submenus_permitidos = array(
            'sinacin', 'sinacin-afiliados', 'sinacin-empresas',
            'sinacin-solicitudes', 'sinacin-historial',
        );
        foreach ( $submenu['sinacin'] as $entrada ) {
            if ( isset( $entrada[2] ) && ! in_array( $entrada[2], $submenus_permitidos, true ) ) {
                remove_submenu_page( 'sinacin', $entrada[2] );
            }
        }
    }
}

/**
 * Fuerza el orden del menú lateral. Se reindexa el menú real, en vez de
 * depender de los índices originales de WordPress o de Adminify.
 */
add_action( 'admin_menu', 'sinacin_ordenar_menu_principal_gestor', 9999 );
function sinacin_ordenar_menu_principal_gestor() {
    if ( ! sinacin_es_gestor() ) {
        return;
    }

    global $menu;
    $actuales = array();
    foreach ( (array) $menu as $entrada ) {
        if ( isset( $entrada[2] ) ) {
            $actuales[ $entrada[2] ] = $entrada;
        }
    }

    $nuevo = array();
    $posicion = 2;
    foreach ( sinacin_menus_gestor_permitidos() as $slug ) {
        if ( isset( $actuales[ $slug ] ) ) {
            $nuevo[ $posicion++ ] = $actuales[ $slug ];
        }
    }
    $menu = $nuevo;
}

/** Evita que otro filtro de orden revierta la prioridad de SINACIN. */
add_filter( 'custom_menu_order', 'sinacin_habilitar_orden_gestor', PHP_INT_MAX );
function sinacin_habilitar_orden_gestor( $orden ) {
    return sinacin_es_gestor() ? true : $orden;
}

add_filter( 'menu_order', 'sinacin_menu_order_gestor', PHP_INT_MAX );
function sinacin_menu_order_gestor( $orden ) {
    if ( ! sinacin_es_gestor() || ! is_array( $orden ) ) {
        return $orden;
    }
    $prioridad = sinacin_menus_gestor_permitidos();
    return array_values( array_unique( array_merge( $prioridad, $orden ) ) );
}

/** Orden de las opciones dentro de SINACIN. */
add_action( 'admin_menu', 'sinacin_ordenar_submenus', 10000 );
function sinacin_ordenar_submenus() {
    global $submenu;
    if ( empty( $submenu['sinacin'] ) ) {
        return;
    }

    // Evita la entrada duplicada de SINACIN, conservando sus módulos.
    remove_submenu_page( 'sinacin', 'sinacin' );
    if ( empty( $submenu['sinacin'] ) ) {
        return;
    }

    $orden = array(
        'sinacin-afiliados' => 0,
        'sinacin-empresas' => 1,
        'sinacin-solicitudes' => 2,
        'sinacin-historial' => 3,
    );
    uasort( $submenu['sinacin'], static function ( $a, $b ) use ( $orden ) {
        return ( $orden[ $a[2] ] ?? 100 ) <=> ( $orden[ $b[2] ] ?? 100 );
    } );
    $submenu['sinacin'] = array_values( $submenu['sinacin'] );
}

/** Limita el acceso por URL a las pantallas de trabajo del gestor. */
add_action( 'admin_init', 'sinacin_restringir_paginas_gestor', 999 );
function sinacin_restringir_paginas_gestor() {
    if ( ! sinacin_es_gestor() || wp_doing_ajax() ) {
        return;
    }

    global $pagenow;
    // Los procesos admin-post.php deben verificar nonce y capacidades en su handler.
    if ( in_array( $pagenow, array( 'admin-ajax.php', 'admin-post.php' ), true ) ) {
        return;
    }

    // Acceso a medios para subir y seleccionar imágenes destacadas.
    if ( in_array( $pagenow, array( 'upload.php', 'media-new.php', 'async-upload.php' ), true ) ) {
        return;
    }

    if ( 'admin.php' === $pagenow ) {
        $pagina = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( in_array( $pagina, array(
            'sinacin', 'sinacin-solicitudes', 'sinacin-afiliados',
            'sinacin-empresas', 'sinacin-historial',
        ), true ) ) {
            return;
        }
    }

    $tipos_permitidos = array( 'sinacin_banner', 'sinacin_mensaje', 'sinacin_hito', 'post' );

    if ( in_array( $pagenow, array( 'edit.php', 'post-new.php' ), true ) ) {
        $tipo = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post';
        // Los mensajes son de consulta: no permitir crearlos desde post-new.php.
        if ( in_array( $tipo, $tipos_permitidos, true )
            && ! ( 'post-new.php' === $pagenow && 'sinacin_mensaje' === $tipo ) ) {
            return;
        }
    }

    if ( 'post.php' === $pagenow ) {
        $post_id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : 0;
        if ( $post_id && in_array( get_post_type( $post_id ), $tipos_permitidos, true ) ) {
            return;
        }
    }

    wp_safe_redirect( admin_url( 'admin.php?page=sinacin-solicitudes' ) );
    exit;
}

/** Oculta la barra de administración al navegar por la web pública. */
add_filter( 'show_admin_bar', 'sinacin_ocultar_barra_gestor' );
function sinacin_ocultar_barra_gestor( $mostrar ) {
    return sinacin_es_gestor() ? false : $mostrar;
}

/** Limpia la barra superior dentro de wp-admin para el gestor. */
add_action( 'admin_bar_menu', 'sinacin_limpiar_barra_gestor', PHP_INT_MAX );
function sinacin_limpiar_barra_gestor( $barra ) {
    if ( ! sinacin_es_gestor() ) {
        return;
    }
    foreach ( array( 'search', 'comments', 'new-content', 'updates' ) as $nodo ) {
        $barra->remove_node( $nodo );
    }
}

/**
 * Refuerzo visual: algunos plugins de personalización reordenan el menú
 * después de los filtros de WordPress. CSS fija el orden para el gestor.
 */
add_action( 'admin_head', 'sinacin_forzar_orden_visual_gestor', 9999 );
function sinacin_forzar_orden_visual_gestor() {
    if ( ! sinacin_es_gestor() ) {
        return;
    }
    ?>
    <style id="sinacin-orden-gestor">
        #adminmenu { display: flex !important; flex-direction: column !important; }
        #adminmenu > li { order: 90; }
        #adminmenu > li#toplevel_page_sinacin { order: 1 !important; }
        #adminmenu > li#menu-posts-sinacin_banner { order: 2 !important; }
        #adminmenu > li#menu-posts-sinacin_mensaje { order: 3 !important; }
        #adminmenu > li#menu-posts-sinacin_hito { order: 4 !important; }
        #adminmenu > li#menu-posts { order: 5 !important; }
        #adminmenu > li.wp-menu-separator { display: none !important; }
        #adminmenu > li#collapse-menu { order: 100 !important; }
    </style>
    <?php
}
