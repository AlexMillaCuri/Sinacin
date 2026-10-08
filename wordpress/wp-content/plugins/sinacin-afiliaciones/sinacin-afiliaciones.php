<?php
/**
 * Plugin Name: SINACIN Afiliaciones
 * Plugin URI: http://localhost:8080
 * Description: Sistema de gestión de afiliaciones para SINACIN.
 * Version: 1.0.0
 * Author: SINACIN
 * License: GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * ==========================================
 * CARGAR ARCHIVOS DEL PLUGIN
 * ==========================================
 */

require_once plugin_dir_path( __FILE__ ) . 'includes/roles.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/database.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/certificados.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/correo.php';

require_once plugin_dir_path( __FILE__ ) . 'admin/empresas.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/solicitudes.php';
require_once plugin_dir_path( __FILE__ ) . 'public/formulario.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/afiliados.php';

require_once plugin_dir_path( __FILE__ ) . 'admin/prueba-certificado.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/prueba-correo.php';


/**
 * ==========================================
 * ACTIVACIÓN
 * ==========================================
 */

register_activation_hook(
    __FILE__,
    'sinacin_activar_plugin'
);


/**
 * Activación del plugin.
 */
function sinacin_activar_plugin() {

    sinacin_crear_tablas();
    sinacin_registrar_roles();

}