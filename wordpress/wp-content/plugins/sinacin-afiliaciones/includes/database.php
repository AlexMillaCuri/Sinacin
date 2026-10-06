<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Crea las tablas necesarias para SINACIN.
 */
function sinacin_crear_tablas() {

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $tabla_empresas       = $wpdb->prefix . 'sinacin_empresas';
    $tabla_faenas         = $wpdb->prefix . 'sinacin_faenas';
    $tabla_personas       = $wpdb->prefix . 'sinacin_personas';
    $tabla_solicitudes    = $wpdb->prefix . 'sinacin_solicitudes';
    $tabla_afiliaciones   = $wpdb->prefix . 'sinacin_afiliaciones';
    $tabla_documentos     = $wpdb->prefix . 'sinacin_documentos';
    $tabla_certificados   = $wpdb->prefix . 'sinacin_certificados';
    $tabla_historial      = $wpdb->prefix . 'sinacin_historial';
    $tabla_terminos       = $wpdb->prefix . 'sinacin_terminos';


    require_once ABSPATH . 'wp-admin/includes/upgrade.php';


    /*
     * ==========================================
     * EMPRESAS
     * ==========================================
     */

    $sql_empresas = "CREATE TABLE $tabla_empresas (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        rut_empresa VARCHAR(20) NOT NULL,

        nombre_empresa VARCHAR(200) NOT NULL,

        estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',

        fecha_registro DATETIME NOT NULL,

        fecha_actualizacion DATETIME NOT NULL,

        PRIMARY KEY (id),

        UNIQUE KEY rut_empresa (rut_empresa),

        KEY estado (estado)

    ) $charset_collate;";


    /*
     * ==========================================
     * FAENAS / OBRAS
     * ==========================================
     */

    $sql_faenas = "CREATE TABLE $tabla_faenas (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        empresa_id BIGINT UNSIGNED NOT NULL,

        nombre_faena VARCHAR(200) NOT NULL,

        estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',

        fecha_registro DATETIME NOT NULL,

        fecha_actualizacion DATETIME NOT NULL,

        PRIMARY KEY (id),

        KEY empresa_id (empresa_id),

        KEY estado (estado),

        KEY empresa_estado (empresa_id, estado)

    ) $charset_collate;";


    /*
     * ==========================================
     * PERSONAS
     * ==========================================
     */

    $sql_personas = "CREATE TABLE $tabla_personas (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        nombres VARCHAR(100) NOT NULL,

        apellido_paterno VARCHAR(100) NOT NULL,

        apellido_materno VARCHAR(100) NOT NULL,

        rut VARCHAR(20) NOT NULL,

        celular VARCHAR(30) NOT NULL,

        correo VARCHAR(190) NOT NULL,

        cargo VARCHAR(150) NOT NULL,

        fecha_registro DATETIME NOT NULL,

        fecha_actualizacion DATETIME NOT NULL,

        PRIMARY KEY (id),

        UNIQUE KEY rut (rut),

        KEY correo (correo)

    ) $charset_collate;";


    /*
     * ==========================================
     * SOLICITUDES
     * ==========================================
     */

    $sql_solicitudes = "CREATE TABLE $tabla_solicitudes (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        persona_id BIGINT UNSIGNED NOT NULL,

        empresa_id BIGINT UNSIGNED NOT NULL,

        faena_id BIGINT UNSIGNED NOT NULL,

        estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',

        acepta_terminos TINYINT(1) NOT NULL DEFAULT 0,

        version_terminos VARCHAR(20) NOT NULL,

        fecha_aceptacion DATETIME NOT NULL,

        fecha_solicitud DATETIME NOT NULL,

        fecha_revision DATETIME NULL,

        usuario_revisor BIGINT UNSIGNED NULL,

        motivo_rechazo TEXT NULL,

        PRIMARY KEY (id),

        KEY persona_id (persona_id),

        KEY empresa_id (empresa_id),

        KEY faena_id (faena_id),

        KEY estado (estado),

        KEY fecha_solicitud (fecha_solicitud)

    ) $charset_collate;";


    /*
     * ==========================================
     * AFILIACIONES
     * ==========================================
     */

    $sql_afiliaciones = "CREATE TABLE $tabla_afiliaciones (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        persona_id BIGINT UNSIGNED NOT NULL,

        empresa_id BIGINT UNSIGNED NOT NULL,

        faena_id BIGINT UNSIGNED NOT NULL,

        solicitud_id BIGINT UNSIGNED NOT NULL,

        estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',

        fecha_afiliacion DATETIME NOT NULL,

        fecha_desafiliacion DATETIME NULL,

        PRIMARY KEY (id),

        KEY persona_id (persona_id),

        KEY empresa_id (empresa_id),

        KEY faena_id (faena_id),

        KEY solicitud_id (solicitud_id),

        KEY estado (estado),

        KEY persona_estado (persona_id, estado)

    ) $charset_collate;";


    /*
     * ==========================================
     * DOCUMENTOS
     * ==========================================
     */

    $sql_documentos = "CREATE TABLE $tabla_documentos (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        persona_id BIGINT UNSIGNED NOT NULL,

        tipo_documento VARCHAR(50) NOT NULL,

        archivo_id BIGINT UNSIGNED NOT NULL,

        nombre_archivo VARCHAR(255) NOT NULL,

        estado VARCHAR(20) NOT NULL DEFAULT 'VIGENTE',

        fecha_subida DATETIME NOT NULL,

        PRIMARY KEY (id),

        KEY persona_id (persona_id),

        KEY tipo_documento (tipo_documento),

        KEY estado (estado)

    ) $charset_collate;";


    /*
     * ==========================================
     * CERTIFICADOS
     * ==========================================
     */

    $sql_certificados = "CREATE TABLE $tabla_certificados (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        afiliacion_id BIGINT UNSIGNED NOT NULL,

        numero_certificado VARCHAR(50) NOT NULL,

        archivo_id BIGINT UNSIGNED NULL,

        fecha_emision DATETIME NOT NULL,

        fecha_vencimiento DATETIME NULL,

        estado VARCHAR(20) NOT NULL DEFAULT 'VIGENTE',

        PRIMARY KEY (id),

        UNIQUE KEY numero_certificado (numero_certificado),

        KEY afiliacion_id (afiliacion_id),

        KEY estado (estado)

    ) $charset_collate;";


    /*
     * ==========================================
     * HISTORIAL
     * ==========================================
     */

    $sql_historial = "CREATE TABLE $tabla_historial (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        usuario_id BIGINT UNSIGNED NULL,

        entidad VARCHAR(50) NOT NULL,

        entidad_id BIGINT UNSIGNED NOT NULL,

        accion VARCHAR(50) NOT NULL,

        descripcion TEXT NULL,

        ip VARCHAR(45) NULL,

        fecha DATETIME NOT NULL,

        PRIMARY KEY (id),

        KEY usuario_id (usuario_id),

        KEY entidad (entidad),

        KEY entidad_id (entidad_id),

        KEY fecha (fecha)

    ) $charset_collate;";


    /*
     * ==========================================
     * TERMINOS Y CONDICIONES
     * ==========================================
     */

    $sql_terminos = "CREATE TABLE $tabla_terminos (

        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

        version VARCHAR(20) NOT NULL,

        contenido LONGTEXT NOT NULL,

        estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVA',

        fecha_publicacion DATETIME NOT NULL,

        PRIMARY KEY (id),

        UNIQUE KEY version (version),

        KEY estado (estado)

    ) $charset_collate;";


    /*
     * ==========================================
     * EJECUTAR CREACIÓN
     * ==========================================
     */

    dbDelta( $sql_empresas );

    dbDelta( $sql_faenas );

    dbDelta( $sql_personas );

    dbDelta( $sql_solicitudes );

    dbDelta( $sql_afiliaciones );

    dbDelta( $sql_documentos );

    dbDelta( $sql_certificados );

    dbDelta( $sql_historial );

    dbDelta( $sql_terminos );
}