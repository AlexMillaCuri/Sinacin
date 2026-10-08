<?php
/** Control de acceso y rol Gestor SINACIN. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Sincroniza rol y capacidades, incluso al actualizar un plugin ya activo. */
function sinacin_registrar_roles() {
    $capacidad = 'sinacin_gestionar_afiliaciones';
    $rol = get_role( 'gestor_sinacin' );
    if ( ! $rol ) {
        $rol = add_role( 'gestor_sinacin', 'Gestor SINACIN', array(
            'read' => true,
            $capacidad => true,
        ) );
    }
    if ( $rol && ! $rol->has_cap( $capacidad ) ) {
        $rol->add_cap( $capacidad );
    }
    $admin = get_role( 'administrator' );
    if ( $admin && ! $admin->has_cap( $capacidad ) ) {
        $admin->add_cap( $capacidad );
    }
}
add_action( 'init', 'sinacin_actualizar_roles_si_necesario', 1 );
function sinacin_actualizar_roles_si_necesario() {
    if ( get_option( 'sinacin_roles_version' ) !== '1' ) {
        sinacin_registrar_roles();
        update_option( 'sinacin_roles_version', '1', false );
    }
}

/** Restricciones exclusivas para cuentas con el rol Gestor SINACIN. */
function sinacin_es_gestor() {
    $usuario = wp_get_current_user();
    return $usuario && in_array( 'gestor_sinacin', (array) $usuario->roles, true )
        && ! current_user_can( 'manage_options' );
}

add_filter( 'login_redirect', 'sinacin_redirigir_login_gestor', 10, 3 );
function sinacin_redirigir_login_gestor( $destino, $solicitado, $usuario ) {
    if ( $usuario instanceof WP_User && in_array( 'gestor_sinacin', (array) $usuario->roles, true ) ) {
        return admin_url( 'admin.php?page=sinacin-solicitudes' );
    }
    return $destino;
}

add_action( 'admin_menu', 'sinacin_ocultar_menus_gestor', 999 );
function sinacin_ocultar_menus_gestor() {
    if ( ! sinacin_es_gestor() ) { return; }
    global $menu, $submenu;
    foreach ( (array) $menu as $entrada ) {
        if ( isset( $entrada[2] ) && $entrada[2] !== 'sinacin' ) {
            remove_menu_page( $entrada[2] );
        }
    }
    if ( isset( $submenu['sinacin'] ) ) {
        foreach ( $submenu['sinacin'] as $entrada ) {
            if ( ! in_array( $entrada[2], array( 'sinacin', 'sinacin-solicitudes', 'sinacin-afiliados', 'sinacin-empresas' ), true ) ) {
                remove_submenu_page( 'sinacin', $entrada[2] );
            }
        }
    }
}

/** Impide abrir páginas administrativas ajenas a SINACIN mediante URL directa. */
add_action( 'admin_init', 'sinacin_restringir_paginas_gestor', 999 );
function sinacin_restringir_paginas_gestor() {
    if ( ! sinacin_es_gestor() || wp_doing_ajax() ) { return; }
    global $pagenow;
    // No interferir con procesos internos ni envíos admin-post; sus acciones
    // deben comprobar su propia capacidad y nonce.
    if ( in_array( $pagenow, array( 'admin-ajax.php', 'admin-post.php' ), true ) ) { return; }
    if ( $pagenow === 'admin.php' ) {
        $pagina = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( in_array( $pagina, array( 'sinacin', 'sinacin-solicitudes', 'sinacin-afiliados', 'sinacin-empresas' ), true ) ) {
            return;
        }
    }
    wp_safe_redirect( admin_url( 'admin.php?page=sinacin-solicitudes' ) );
    exit;
}

add_filter( 'show_admin_bar', 'sinacin_ocultar_barra_gestor' );
function sinacin_ocultar_barra_gestor( $mostrar ) {
    return sinacin_es_gestor() ? false : $mostrar;
}

/** Presenta los módulos SINACIN en el orden acordado, sin cambiar sus permisos. */
add_action( 'admin_menu', 'sinacin_ordenar_submenus', 1000 );
function sinacin_ordenar_submenus() {
    global $submenu;
    if ( empty( $submenu['sinacin'] ) ) {
        return;
    }
    // El menú superior SINACIN continúa funcionando; no necesita duplicado como submenú.
    remove_submenu_page( 'sinacin', 'sinacin' );
    if ( empty( $submenu['sinacin'] ) ) {
        return;
    }
    $orden = array( 'sinacin-afiliados' => 0, 'sinacin-empresas' => 1, 'sinacin-solicitudes' => 2 );
    uasort( $submenu['sinacin'], static function ( $a, $b ) use ( $orden ) {
        $prioridad_a = isset( $orden[ $a[2] ] ) ? $orden[ $a[2] ] : 100;
        $prioridad_b = isset( $orden[ $b[2] ] ) ? $orden[ $b[2] ] : 100;
        return $prioridad_a <=> $prioridad_b;
    } );
    $submenu['sinacin'] = array_values( $submenu['sinacin'] );
}
