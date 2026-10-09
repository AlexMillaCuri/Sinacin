<?php
/**
 * Módulo de Afiliados - SINACIN
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* =========================================================
 * MENÚ
 * ========================================================= */

add_action(
    'admin_menu',
    'sinacin_menu_afiliados'
);

function sinacin_menu_afiliados() {

    add_submenu_page(
        'sinacin',
        'Afiliados',
        'Afiliados',
        'sinacin_gestionar_afiliaciones',
        'sinacin-afiliados',
        'sinacin_pagina_afiliados'
    );
}


/* =========================================================
 * NORMALIZAR RUT
 * ========================================================= */

if ( ! function_exists( 'sinacin_afiliados_normalizar_rut' ) ) {

    function sinacin_afiliados_normalizar_rut( $rut ) {

        $rut = strtoupper( trim( $rut ) );

        $rut = str_replace(
            array( '.', '-', ' ' ),
            '',
            $rut
        );

        return $rut;
    }
}


/* =========================================================
 * FORMATEAR RUT
 * ========================================================= */

if ( ! function_exists( 'sinacin_afiliados_formatear_rut' ) ) {

    function sinacin_afiliados_formatear_rut( $rut ) {

        $rut = sinacin_afiliados_normalizar_rut( $rut );

        if ( strlen( $rut ) < 2 ) {
            return $rut;
        }

        $dv = substr( $rut, -1 );

        $numero = substr(
            $rut,
            0,
            -1
        );

        $numero_formateado = '';

        while ( strlen( $numero ) > 3 ) {

            $numero_formateado =
                '.' .
                substr( $numero, -3 ) .
                $numero_formateado;

            $numero = substr(
                $numero,
                0,
                -3
            );
        }

        $numero_formateado =
            $numero .
            $numero_formateado;

        return $numero_formateado . '-' . $dv;
    }
}


/* =========================================================
 * PROCESAR DESAFILIACIÓN
 * ========================================================= */

add_action(
    'admin_init',
    'sinacin_procesar_desafiliacion'
);

function sinacin_procesar_desafiliacion() {

    if (
        ! isset( $_POST['sinacin_desafiliar'] )
    ) {
        return;
    }

    if (
        ! current_user_can( 'sinacin_gestionar_afiliaciones' )
    ) {
        wp_die(
            'No tienes permisos para realizar esta acción.'
        );
    }

    if (
        ! isset( $_POST['sinacin_desafiliar_nonce'] )
        ||
        ! wp_verify_nonce(
            $_POST['sinacin_desafiliar_nonce'],
            'sinacin_desafiliar_afiliado'
        )
    ) {
        wp_die(
            'La solicitud de seguridad no es válida.'
        );
    }

    global $wpdb;

    $tabla_afiliaciones =
        $wpdb->prefix . 'sinacin_afiliaciones';

    $tabla_historial =
        $wpdb->prefix . 'sinacin_historial';

    $afiliacion_id =
        isset( $_POST['afiliacion_id'] )
            ? absint( $_POST['afiliacion_id'] )
            : 0;

    if ( ! $afiliacion_id ) {
        wp_die(
            'Afiliación no válida.'
        );
    }

    $afiliacion = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT *
            FROM {$tabla_afiliaciones}
            WHERE id = %d
            LIMIT 1
            ",
            $afiliacion_id
        )
    );

    if ( ! $afiliacion ) {
        wp_die(
            'La afiliación no existe.'
        );
    }

    if (
        strtoupper( $afiliacion->estado ) !== 'ACTIVA'
    ) {
        wp_die(
            'La afiliación ya no se encuentra activa.'
        );
    }

    $fecha_actual = current_time( 'mysql' );

    $actualizado = $wpdb->update(
        $tabla_afiliaciones,
        array(
            'estado'              => 'DESAFILIADA',
            'fecha_desafiliacion' => $fecha_actual,
        ),
        array(
            'id' => $afiliacion_id,
        ),
        array(
            '%s',
            '%s',
        ),
        array(
            '%d',
        )
    );

    if ( $actualizado === false ) {
        wp_die(
            'No fue posible realizar la desafiliación.'
        );
    }

    /*
     * Registrar historial.
     */

    $usuario_id = get_current_user_id();

    $wpdb->insert(
        $tabla_historial,
        array(
            'usuario_id'  => $usuario_id,
            'entidad'     => 'AFILIACION',
            'entidad_id'  => $afiliacion_id,
            'accion'      => 'DESAFILIAR',
            'descripcion' => 'Afiliación marcada como DESAFILIADA.',
            'ip'          => isset( $_SERVER['REMOTE_ADDR'] )
                ? sanitize_text_field(
                    wp_unslash( $_SERVER['REMOTE_ADDR'] )
                )
                : null,
            'fecha'       => $fecha_actual,
        ),
        array(
            '%d',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
        )
    );

    $url = add_query_arg(
        array(
            'page'            => 'sinacin-afiliados',
            'sinacin_mensaje' => 'actualizado',
        ),
        admin_url( 'admin.php' )
    );

    wp_redirect( $url );
    exit;
}


/* =========================================================
 * PROCESAR EDICIÓN
 * ========================================================= */

add_action(
    'admin_init',
    'sinacin_procesar_edicion_afiliado'
);


function sinacin_procesar_edicion_afiliado() {

    if (
        ! isset( $_POST['sinacin_guardar_afiliado'] )
    ) {
        return;
    }

    if (
        ! current_user_can( 'sinacin_gestionar_afiliaciones' )
    ) {
        wp_die(
            'No tienes permisos para realizar esta acción.'
        );
    }

    if (
        ! isset( $_POST['sinacin_editar_afiliado_nonce'] )
        ||
        ! wp_verify_nonce(
            $_POST['sinacin_editar_afiliado_nonce'],
            'sinacin_editar_afiliado'
        )
    ) {
        wp_die(
            'La solicitud de seguridad no es válida.'
        );
    }

    global $wpdb;

    $tabla_personas =
        $wpdb->prefix . 'sinacin_personas';

    $tabla_afiliaciones =
        $wpdb->prefix . 'sinacin_afiliaciones';

    $tabla_faenas =
        $wpdb->prefix . 'sinacin_faenas';

    $tabla_historial =
        $wpdb->prefix . 'sinacin_historial';


    /*
     * Obtener ID de persona.
     */

    $persona_id =
        isset( $_POST['persona_id'] )
            ? absint( $_POST['persona_id'] )
            : 0;


    if ( ! $persona_id ) {

        wp_die(
            'Persona no válida.'
        );
    }


    /*
     * Obtener persona.
     */

    $persona = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT *
            FROM {$tabla_personas}
            WHERE id = %d
            LIMIT 1
            ",
            $persona_id
        )
    );


    if ( ! $persona ) {

        wp_die(
            'La persona no existe.'
        );
    }


    /*
     * Obtener afiliación activa.
     *
     * La empresa NO se modifica desde esta pantalla.
     * La empresa se obtiene directamente desde la afiliación.
     */

    $afiliacion = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT *
            FROM {$tabla_afiliaciones}
            WHERE persona_id = %d
              AND estado = 'ACTIVA'
            ORDER BY id DESC
            LIMIT 1
            ",
            $persona_id
        )
    );


    if ( ! $afiliacion ) {

        wp_die(
            'No se encontró una afiliación activa para esta persona.'
        );
    }


    /*
     * Obtener nueva faena.
     */

    $faena_id =
        isset( $_POST['faena_id'] )
            ? absint( $_POST['faena_id'] )
            : 0;


    if ( ! $faena_id ) {

        wp_die(
            'Debe seleccionar una faena u obra.'
        );
    }


    /*
     * Verificar que la faena exista,
     * esté activa y pertenezca a la misma empresa
     * de la afiliación.
     */

    $faena = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT *
            FROM {$tabla_faenas}
            WHERE id = %d
              AND empresa_id = %d
              AND estado = 'ACTIVA'
            LIMIT 1
            ",
            $faena_id,
            $afiliacion->empresa_id
        )
    );


    if ( ! $faena ) {

        wp_die(
            'La faena seleccionada no es válida o no pertenece a la empresa del afiliado.'
        );
    }


    /*
     * Guardar faena anterior.
     */

    $faena_anterior_id =
        absint(
            $afiliacion->faena_id
        );


    $faena_anterior =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT nombre_faena
                FROM {$tabla_faenas}
                WHERE id = %d
                LIMIT 1
                ",
                $faena_anterior_id
            )
        );


    $nombre_faena_anterior =
        $faena_anterior
            ? $faena_anterior->nombre_faena
            : 'Sin faena';


    /*
     * Datos personales.
     */

    $nombres =
        isset( $_POST['nombres'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['nombres'] )
            )
            : '';

    $apellido_paterno =
        isset( $_POST['apellido_paterno'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['apellido_paterno'] )
            )
            : '';

    $apellido_materno =
        isset( $_POST['apellido_materno'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['apellido_materno'] )
            )
            : '';

    $celular =
        isset( $_POST['celular'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['celular'] )
            )
            : '';

    $correo =
        isset( $_POST['correo'] )
            ? sanitize_email(
                wp_unslash( $_POST['correo'] )
            )
            : '';

    $cargo =
        isset( $_POST['cargo'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['cargo'] )
            )
            : '';


    /*
     * Validar campos personales.
     */

    if (
        $nombres === ''
        ||
        $apellido_paterno === ''
        ||
        $apellido_materno === ''
        ||
        $celular === ''
        ||
        $correo === ''
        ||
        $cargo === ''
    ) {

        wp_die(
            'Todos los campos son obligatorios.'
        );
    }


    if (
        ! is_email( $correo )
    ) {

        wp_die(
            'El correo electrónico no es válido.'
        );
    }


    /*
     * Actualizar persona.
     */

    $actualizado_persona = $wpdb->update(
        $tabla_personas,
        array(
            'nombres'             => $nombres,
            'apellido_paterno'   => $apellido_paterno,
            'apellido_materno'   => $apellido_materno,
            'celular'            => $celular,
            'correo'             => $correo,
            'cargo'              => $cargo,
            'fecha_actualizacion' => current_time( 'mysql' ),
        ),
        array(
            'id' => $persona_id,
        ),
        array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        ),
        array(
            '%d',
        )
    );


    if ( $actualizado_persona === false ) {

        wp_die(
            'No fue posible actualizar los datos del afiliado.'
        );
    }


    /*
     * Actualizar faena de la afiliación.
     */

    $faena_cambio =
        $faena_anterior_id !== $faena_id;


    if ( $faena_cambio ) {

        $actualizado_faena = $wpdb->update(
            $tabla_afiliaciones,
            array(
                'faena_id' => $faena_id,
            ),
            array(
                'id' => $afiliacion->id,
            ),
            array(
                '%d',
            ),
            array(
                '%d',
            )
        );


        if ( $actualizado_faena === false ) {

            wp_die(
                'Los datos personales fueron actualizados, pero no fue posible actualizar la faena.'
            );
        }
    }


    /*
     * Registrar historial de edición de persona.
     */

    $wpdb->insert(
        $tabla_historial,
        array(
            'usuario_id'  => get_current_user_id(),
            'entidad'     => 'PERSONA',
            'entidad_id'  => $persona_id,
            'accion'      => 'EDITAR_AFILIADO',
            'descripcion' => 'Datos del afiliado actualizados desde el módulo de afiliados.',
            'ip'          => isset( $_SERVER['REMOTE_ADDR'] )
                ? sanitize_text_field(
                    wp_unslash(
                        $_SERVER['REMOTE_ADDR']
                    )
                )
                : null,
            'fecha'       => current_time( 'mysql' ),
        ),
        array(
            '%d',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
        )
    );


    /*
     * Registrar cambio de faena solamente
     * cuando realmente cambió.
     */

    if ( $faena_cambio ) {

        $wpdb->insert(
            $tabla_historial,
            array(
                'usuario_id'  => get_current_user_id(),
                'entidad'     => 'AFILIACION',
                'entidad_id'  => $afiliacion->id,
                'accion'      => 'CAMBIAR_FAENA',
                'descripcion' =>
                    'Cambio de faena desde "' .
                    $nombre_faena_anterior .
                    '" a "' .
                    $faena->nombre_faena .
                    '".',
                'ip'          => isset( $_SERVER['REMOTE_ADDR'] )
                    ? sanitize_text_field(
                        wp_unslash(
                            $_SERVER['REMOTE_ADDR']
                        )
                    )
                    : null,
                'fecha'       => current_time( 'mysql' ),
            ),
            array(
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );
    }

    wp_safe_redirect(
        add_query_arg(
            array(
                'page'            => 'sinacin-afiliados',
                'sinacin_mensaje' => 'actualizado',
                'afiliado'        => $persona_id,
            ),
            admin_url( 'admin.php' )
        )
    );
    exit;
}



/* =========================================================
 * DESCARGAR CERTIFICADO
 * ========================================================= */

add_action(
    'admin_init',
    'sinacin_descargar_certificado'
);

function sinacin_descargar_certificado() {
    if ( ! isset( $_GET['sinacin_descargar_certificado'] ) ) {
        return;
    }

    if ( ! current_user_can( 'sinacin_gestionar_afiliaciones' ) ) {
        wp_die( 'No tienes permisos para descargar este certificado.' );
    }

    $nonce = isset( $_GET['sinacin_certificado_nonce'] )
        ? sanitize_text_field( wp_unslash( $_GET['sinacin_certificado_nonce'] ) )
        : '';

    if ( ! wp_verify_nonce( $nonce, 'sinacin_descargar_certificado' ) ) {
        wp_die( 'La solicitud de seguridad no es válida.' );
    }

    $afiliacion_id = isset( $_GET['afiliacion_id'] )
        ? absint( $_GET['afiliacion_id'] )
        : 0;

    if ( ! $afiliacion_id ) {
        wp_die( 'Afiliación no válida.' );
    }

    // Cada solicitud de descarga emite un certificado nuevo.
    $resultado = sinacin_generar_certificado( $afiliacion_id );

    if ( ! is_array( $resultado ) || empty( $resultado['success'] ) ) {
        $mensaje = is_array( $resultado ) && ! empty( $resultado['message'] )
            ? $resultado['message']
            : 'No fue posible generar el certificado.';
        wp_die( esc_html( $mensaje ) );
    }

    $ruta_pdf = isset( $resultado['ruta_archivo'] )
        ? $resultado['ruta_archivo']
        : '';

    if ( ! is_string( $ruta_pdf ) || ! is_file( $ruta_pdf ) || ! is_readable( $ruta_pdf ) ) {
        wp_die( 'No fue posible acceder al PDF temporal del certificado.' );
    }

    $numero = $resultado['numero_certificado'];
    $certificado_id = absint( $resultado['certificado_id'] );

    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'sinacin_historial',
        array(
            'usuario_id'  => get_current_user_id(),
            'entidad'     => 'CERTIFICADO',
            'entidad_id'  => $certificado_id,
            'accion'      => 'DESCARGAR_CERTIFICADO',
            'descripcion' => 'Emisión y descarga del certificado ' . $numero . '.',
            'ip'          => isset( $_SERVER['REMOTE_ADDR'] )
                ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
                : null,
            'fecha'       => current_time( 'mysql' ),
        ),
        array( '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
    );

    $nombre_archivo = sanitize_file_name( $numero . '.pdf' );
    $tamano = filesize( $ruta_pdf );
    if ( false === $tamano || headers_sent() ) {
        @unlink( $ruta_pdf );
        wp_die( 'No fue posible iniciar la descarga del certificado.' );
    }

    while ( ob_get_level() ) {
        ob_end_clean();
    }

    header( 'Content-Type: application/pdf' );
    header( 'Content-Disposition: attachment; filename="' . $nombre_archivo . '"' );
    header( 'Content-Length: ' . $tamano );
    header( 'Cache-Control: private, no-store, no-cache, must-revalidate' );
    header( 'Pragma: no-cache' );
    header( 'X-Content-Type-Options: nosniff' );

    // Enviar el PDF y borrar el temporal, sin tocar el registro en BD.
    readfile( $ruta_pdf );
    @unlink( $ruta_pdf );
    exit;
}

/* =========================================================
 * ETAPA 3: OPERACIONES MASIVAS (solo afiliaciones activas)
 * ========================================================= */
add_action( 'admin_init', 'sinacin_procesar_operacion_masiva_afiliados' );

function sinacin_procesar_operacion_masiva_afiliados() {
    if ( ! isset( $_POST['sinacin_operacion_masiva'] ) ) {
        return;
    }
    if ( ! current_user_can( 'sinacin_gestionar_afiliaciones' ) ) {
        wp_die( 'No tienes permisos para realizar operaciones masivas.' );
    }
    check_admin_referer( 'sinacin_operacion_masiva_afiliados', 'sinacin_masiva_nonce' );

    global $wpdb;
    $tabla_afiliaciones = $wpdb->prefix . 'sinacin_afiliaciones';
    $tabla_empresas     = $wpdb->prefix . 'sinacin_empresas';
    $tabla_faenas       = $wpdb->prefix . 'sinacin_faenas';
    $tabla_historial    = $wpdb->prefix . 'sinacin_historial';
    $accion = isset( $_POST['sinacin_operacion_masiva'] )
        ? sanitize_key( wp_unslash( $_POST['sinacin_operacion_masiva'] ) ) : '';
    if ( ! in_array( $accion, array( 'desafiliar', 'cambiar_faena' ), true ) ) {
        wp_die( 'Operación masiva no válida.' );
    }
    $ids = isset( $_POST['sinacin_afiliaciones_seleccionadas'] ) && is_array( $_POST['sinacin_afiliaciones_seleccionadas'] )
        ? array_unique( array_filter( array_map( 'absint', wp_unslash( $_POST['sinacin_afiliaciones_seleccionadas'] ) ) ) )
        : array();
    // Máximo una página de resultados por operación. Nunca confiar en la selección del navegador.
    if ( count( $ids ) < 2 || count( $ids ) > 20 ) {
        wp_die( 'Selecciona entre 2 y 20 afiliaciones para esta operación.' );
    }
    $destino_id = isset( $_POST['sinacin_faena_destino'] ) ? absint( $_POST['sinacin_faena_destino'] ) : 0;
    $destino = null;
    if ( $accion === 'cambiar_faena' ) {
        $destino = $wpdb->get_row( $wpdb->prepare(
            "SELECT f.id, f.empresa_id, f.nombre_faena FROM {$tabla_faenas} f
             INNER JOIN {$tabla_empresas} e ON e.id = f.empresa_id
             WHERE f.id = %d AND f.estado = 'ACTIVA' AND e.estado = 'ACTIVA' LIMIT 1",
            $destino_id
        ) );
        if ( ! $destino ) {
            wp_die( 'La faena de destino no está activa o su empresa no está activa.' );
        }
    }
    $correctos = 0;
    $omitidos = 0;
    $fecha = current_time( 'mysql' );
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null;
    foreach ( $ids as $id ) {
        // Bloqueo por registro: evita cambios simultáneos entre validación y actualización.
        $wpdb->query( 'START TRANSACTION' );
        $afiliacion = $wpdb->get_row( $wpdb->prepare(
            "SELECT a.id, a.empresa_id, a.faena_id, a.estado, f.nombre_faena AS faena_anterior,
                    f.estado AS estado_faena, e.estado AS estado_empresa
             FROM {$tabla_afiliaciones} a
             INNER JOIN {$tabla_faenas} f ON f.id = a.faena_id
             INNER JOIN {$tabla_empresas} e ON e.id = a.empresa_id
             WHERE a.id = %d LIMIT 1 FOR UPDATE", $id
        ) );
        if ( ! $afiliacion || $afiliacion->estado !== 'ACTIVA' ) {
            $wpdb->query( 'ROLLBACK' );
            ++$omitidos;
            continue;
        }
        if ( $accion === 'cambiar_faena' && (
            (int) $afiliacion->empresa_id !== (int) $destino->empresa_id ||
            (int) $afiliacion->faena_id === (int) $destino->id ||
            $afiliacion->estado_empresa !== 'ACTIVA' || $afiliacion->estado_faena !== 'ACTIVA'
        ) ) {
            $wpdb->query( 'ROLLBACK' );
            ++$omitidos;
            continue;
        }
        if ( $accion === 'desafiliar' ) {
            $actualizado = $wpdb->update( $tabla_afiliaciones,
                array( 'estado' => 'DESAFILIADA', 'fecha_desafiliacion' => $fecha ),
                array( 'id' => $id, 'estado' => 'ACTIVA' ),
                array( '%s', '%s' ), array( '%d', '%s' )
            );
            $descripcion = 'Desafiliación masiva: afiliación marcada como DESAFILIADA.';
            $accion_historial = 'DESAFILIAR';
        } else {
            $actualizado = $wpdb->update( $tabla_afiliaciones,
                array( 'faena_id' => $destino_id ),
                array( 'id' => $id, 'estado' => 'ACTIVA', 'empresa_id' => $destino->empresa_id ),
                array( '%d' ), array( '%d', '%s', '%d' )
            );
            $descripcion = 'Cambio masivo de faena desde "' . $afiliacion->faena_anterior . '" a "' . $destino->nombre_faena . '".';
            $accion_historial = 'CAMBIAR_FAENA';
        }
        if ( 1 !== $actualizado ) {
            $wpdb->query( 'ROLLBACK' );
            ++$omitidos;
            continue;
        }
        $registrado = $wpdb->insert( $tabla_historial, array(
            'usuario_id' => get_current_user_id(), 'entidad' => 'AFILIACION',
            'entidad_id' => $id, 'accion' => $accion_historial,
            'descripcion' => $descripcion, 'ip' => $ip, 'fecha' => $fecha,
        ), array( '%d', '%s', '%d', '%s', '%s', '%s', '%s' ) );
        if ( false === $registrado ) {
            $wpdb->query( 'ROLLBACK' );
            ++$omitidos;
            continue;
        }
        $wpdb->query( 'COMMIT' );
        ++$correctos;
    }
    $url = add_query_arg( array(
        'page' => 'sinacin-afiliados', 'sinacin_masiva_resultado' => $accion,
        'sinacin_masiva_ok' => $correctos, 'sinacin_masiva_omitidos' => $omitidos,
    ), admin_url( 'admin.php' ) );
    wp_safe_redirect( $url );
    exit;
}

/* =========================================================
 * PÁGINA PRINCIPAL
 * ========================================================= */

function sinacin_pagina_afiliados() {

    if (
        ! current_user_can( 'sinacin_gestionar_afiliaciones' )
    ) {
        wp_die(
            'No tienes permisos para acceder a esta página.'
        );
    }

    /*
     * Si se solicita un detalle.
     */

    if (
        isset( $_GET['detalle'] )
    ) {

        $persona_id =
            absint(
                $_GET['detalle']
            );

        if ( $persona_id ) {

            sinacin_mostrar_detalle_afiliado(
                $persona_id
            );

            return;
        }
    }

    /*
     * Si se solicita edición.
     */

    if (
        isset( $_GET['editar'] )
    ) {

        $persona_id =
            absint(
                $_GET['editar']
            );

        if ( $persona_id ) {

            sinacin_mostrar_editar_afiliado(
                $persona_id
            );

            return;
        }
    }

    global $wpdb;

    $tabla_personas =
        $wpdb->prefix . 'sinacin_personas';

    $tabla_afiliaciones =
        $wpdb->prefix . 'sinacin_afiliaciones';

    $tabla_empresas =
        $wpdb->prefix . 'sinacin_empresas';

    $tabla_faenas =
        $wpdb->prefix . 'sinacin_faenas';

    $tabla_certificados =
        $wpdb->prefix . 'sinacin_certificados';


    /* =====================================================
     * MENSAJES
     * ===================================================== */

    if (
        isset( $_GET['sinacin_mensaje'] )
    ) {

        $mensaje =
            sanitize_text_field(
                wp_unslash(
                    $_GET['sinacin_mensaje']
                )
            );

        if ( $mensaje === 'desafiliado' ) {

            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>El afiliado fue desafiliado correctamente.</p>';
            echo '</div>';

        } elseif ( $mensaje === 'actualizado' ) {

            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>Los datos del afiliado fueron actualizados correctamente.</p>';
            echo '</div>';
        }
    }


    if ( isset( $_GET['sinacin_masiva_resultado'], $_GET['sinacin_masiva_ok'], $_GET['sinacin_masiva_omitidos'] ) ) {
        $tipo = sanitize_key( wp_unslash( $_GET['sinacin_masiva_resultado'] ) );
        if ( in_array( $tipo, array( 'desafiliar', 'cambiar_faena' ), true ) ) {
            $ok = absint( $_GET['sinacin_masiva_ok'] );
            $omitidos = absint( $_GET['sinacin_masiva_omitidos'] );
            echo '<div class="notice notice-info is-dismissible"><p>' . esc_html(
                sprintf( 'Operación masiva finalizada: %d actualizados, %d omitidos o no procesados.', $ok, $omitidos )
            ) . '</p></div>';
        }
    }

    /* =====================================================
     * BÚSQUEDA
     * ===================================================== */

    $buscar =
        isset( $_GET['buscar'] )
            ? sanitize_text_field(
                wp_unslash(
                    $_GET['buscar']
                )
            )
            : '';

    $estado =
        isset( $_GET['estado'] )
            ? sanitize_text_field(
                wp_unslash(
                    $_GET['estado']
                )
            )
            : 'ACTIVA';

    // Filtros por empresa y faena, siempre validados como identificadores.
    $empresa_filtro = isset( $_GET['empresa_id'] ) ? absint( $_GET['empresa_id'] ) : 0;
    $faena_filtro   = isset( $_GET['faena_id'] ) ? absint( $_GET['faena_id'] ) : 0;

    // Lista blanca de columnas SQL para impedir ORDER BY arbitrarios.
    $columnas_orden = array(
        'afiliado' => 'p.apellido_paterno, p.apellido_materno, p.nombres',
        'rut'      => 'p.rut',
        'empresa'  => 'e.nombre_empresa',
        'faena'    => 'f.nombre_faena',
        'estado'   => 'a.estado',
        'fecha'    => 'a.fecha_afiliacion',
    );
    $ordenar = isset( $_GET['ordenar'] ) ? sanitize_key( wp_unslash( $_GET['ordenar'] ) ) : 'fecha';
    if ( ! isset( $columnas_orden[ $ordenar ] ) ) {
        $ordenar = 'fecha';
    }
    $direccion = isset( $_GET['direccion'] ) ? strtoupper( sanitize_key( wp_unslash( $_GET['direccion'] ) ) ) : 'DESC';
    if ( ! in_array( $direccion, array( 'ASC', 'DESC' ), true ) ) {
        $direccion = 'DESC';
    }
    $sql_orden = implode( ' ' . $direccion . ', ', explode( ', ', $columnas_orden[ $ordenar ] ) ) . ' ' . $direccion . ', a.id DESC';

    // En afiliaciones activas solo se ofrecen empresas y faenas activas.
    // En desafiliadas o todos los estados se conserva el historial completo.
    $solo_activas = ( $estado === 'ACTIVA' );
    $condicion_empresa = $solo_activas ? " WHERE estado = 'ACTIVA'" : '';
    $empresas_filtro = $wpdb->get_results(
        "SELECT id, nombre_empresa FROM {$tabla_empresas}{$condicion_empresa} ORDER BY nombre_empresa ASC"
    );

    // Una empresa inactiva previamente seleccionada deja de ser válida al cambiar a activos.
    $ids_empresas = array_map( 'intval', wp_list_pluck( $empresas_filtro, 'id' ) );
    if ( $empresa_filtro > 0 && ! in_array( $empresa_filtro, $ids_empresas, true ) ) {
        $empresa_filtro = 0;
        $faena_filtro = 0;
    }

    $faenas_filtro = array();
    if ( $empresa_filtro > 0 ) {
        $condicion_faena = $solo_activas ? " AND estado = 'ACTIVA'" : '';
        $faenas_filtro = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, nombre_faena FROM {$tabla_faenas} WHERE empresa_id = %d{$condicion_faena} ORDER BY nombre_faena ASC",
            $empresa_filtro
        ) );
    }

    // Invalidar una faena que no pertenece a la empresa o que dejó de estar disponible.
    $ids_faenas = array_map( 'intval', wp_list_pluck( $faenas_filtro, 'id' ) );
    if ( $faena_filtro > 0 && ! in_array( $faena_filtro, $ids_faenas, true ) ) {
        $faena_filtro = 0;
    }

    /* =====================================================
     * PAGINACIÓN
     * ===================================================== */

    $por_pagina = 20;

    $pagina_actual =
        isset( $_GET['paged'] )
            ? max(
                1,
                absint(
                    $_GET['paged']
                )
            )
            : 1;

    $offset =
        ( $pagina_actual - 1 ) *
        $por_pagina;


    /* =====================================================
     * WHERE
     * ===================================================== */

    $where = 'WHERE 1=1';

    $params = array();


    if (
        $estado !== ''
        &&
        in_array(
            $estado,
            array(
                'ACTIVA',
                'DESAFILIADA',
            ),
            true
        )
    ) {

        $where .=
            ' AND a.estado = %s';

        $params[] = $estado;
    }


    if ( $empresa_filtro > 0 ) {
        $where .= ' AND a.empresa_id = %d';
        $params[] = $empresa_filtro;
    }
    if ( $faena_filtro > 0 ) {
        $where .= ' AND a.faena_id = %d';
        $params[] = $faena_filtro;
    }

    if ( $buscar !== '' ) {
        // Buscar únicamente por nombre completo o RUT, con o sin puntuación.
        $rut_buscar = strtoupper( preg_replace( '/[^0-9K]/i', '', $buscar ) );
        $buscar_like = '%' . $wpdb->esc_like( $buscar ) . '%';
        $where .= " AND (CONCAT_WS(' ', p.nombres, p.apellido_paterno, p.apellido_materno) LIKE %s";
        $params[] = $buscar_like;
        if ( $rut_buscar !== '' ) {
            $where .= " OR UPPER(REPLACE(REPLACE(REPLACE(p.rut, '.', ''), '-', ''), ' ', '')) LIKE %s";
            $params[] = '%' . $wpdb->esc_like( $rut_buscar ) . '%';
        }
        $where .= ' )';
    }


    /* =====================================================
     * TOTAL
     * ===================================================== */

    $sql_total = "
        SELECT COUNT(*)
        FROM {$tabla_afiliaciones} a

        INNER JOIN {$tabla_personas} p
            ON p.id = a.persona_id

        INNER JOIN {$tabla_empresas} e
            ON e.id = a.empresa_id

        INNER JOIN {$tabla_faenas} f
            ON f.id = a.faena_id

        {$where}
    ";

    if ( ! empty( $params ) ) {

        $total =
            (int) $wpdb->get_var(
                $wpdb->prepare(
                    $sql_total,
                    $params
                )
            );

    } else {

        $total =
            (int) $wpdb->get_var(
                $sql_total
            );
    }


    /* =====================================================
     * CONSULTA PRINCIPAL
     * ===================================================== */

    $sql = "
        SELECT

            a.id AS afiliacion_id,
            a.persona_id,
            a.empresa_id,
            a.faena_id,
            a.solicitud_id,
            a.estado,
            a.fecha_afiliacion,
            a.fecha_desafiliacion,

            p.nombres,
            p.apellido_paterno,
            p.apellido_materno,
            p.rut,
            p.celular,
            p.correo,
            p.cargo,

            e.nombre_empresa,
            e.rut_empresa,

            f.nombre_faena,

            c.id AS certificado_id,
            c.numero_certificado,
            c.archivo_id,
            c.estado AS estado_certificado

        FROM {$tabla_afiliaciones} a

        INNER JOIN {$tabla_personas} p
            ON p.id = a.persona_id

        INNER JOIN {$tabla_empresas} e
            ON e.id = a.empresa_id

        INNER JOIN {$tabla_faenas} f
            ON f.id = a.faena_id

        LEFT JOIN {$tabla_certificados} c
            ON c.id = (
                SELECT MAX(c2.id)
                FROM {$tabla_certificados} c2
                WHERE c2.afiliacion_id = a.id
            )

        {$where}

        ORDER BY {$sql_orden}

        LIMIT %d OFFSET %d
    ";


    $params_consulta = $params;

    $params_consulta[] = $por_pagina;
    $params_consulta[] = $offset;


    $afiliados =
        $wpdb->get_results(
            $wpdb->prepare(
                $sql,
                $params_consulta
            )
        );


    $total_paginas =
        $total > 0
            ? ceil(
                $total / $por_pagina
            )
            : 1;


    ?>

    <div class="wrap sinacin-admin">

        <h1 class="wp-heading-inline">
            Afiliados
        </h1>

        <hr class="wp-header-end">


        <!-- ==========================================
             FILTROS
             ========================================== -->

        <form
            method="get"
            class="sinacin-filtros"
        >

            <input
                type="hidden"
                name="page"
                value="sinacin-afiliados"
            >

            <input
                type="search"
                name="buscar"
                value="<?php echo esc_attr( $buscar ); ?>"
                placeholder="Buscar por nombre o RUT (ej.: 01.234.567-8)"
                class="regular-text"
            >

            <label for="sinacin-filtro-empresa">Empresa</label>
            <select name="empresa_id" id="sinacin-filtro-empresa">
                <option value="0">Todas las empresas</option>
                <?php foreach ( $empresas_filtro as $empresa_opcion ) : ?>
                    <option value="<?php echo esc_attr( $empresa_opcion->id ); ?>" <?php selected( $empresa_filtro, (int) $empresa_opcion->id ); ?>>
                        <?php echo esc_html( $empresa_opcion->nombre_empresa ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="sinacin-filtro-faena">Faena / Obra</label>
            <select name="faena_id" id="sinacin-filtro-faena" <?php disabled( $empresa_filtro === 0 ); ?>>
                <option value="0">Todas las faenas</option>
                <?php foreach ( $faenas_filtro as $faena_opcion ) : ?>
                    <option value="<?php echo esc_attr( $faena_opcion->id ); ?>" <?php selected( $faena_filtro, (int) $faena_opcion->id ); ?>>
                        <?php echo esc_html( $faena_opcion->nombre_faena ); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="sinacin-filtro-estado">Estado</label>
            <select name="estado" id="sinacin-filtro-estado">
                <option value="" <?php selected( $estado, '' ); ?>>Todos los estados</option>

                <option
                    value="ACTIVA"
                    <?php selected(
                        $estado,
                        'ACTIVA'
                    ); ?>
                >
                    Afiliados activos
                </option>

                <option
                    value="DESAFILIADA"
                    <?php selected(
                        $estado,
                        'DESAFILIADA'
                    ); ?>
                >
                    Desafiliados
                </option>

            </select>

            <input type="hidden" name="ordenar" value="<?php echo esc_attr( $ordenar ); ?>">
            <input type="hidden" name="direccion" value="<?php echo esc_attr( $direccion ); ?>">

            <span class="description" id="sinacin-busqueda-estado" role="status" aria-live="polite">Búsqueda automática</span>

            <a
                href="<?php echo esc_url(
                    admin_url(
                        'admin.php?page=sinacin-afiliados'
                    )
                ); ?>"
                class="button"
            >
                Limpiar
            </a>

        </form>


        <!-- ==========================================
             RESUMEN
             ========================================== -->

        <div class="sinacin-resumen-afiliados">

            <strong>
                <?php echo esc_html( $total ); ?>
            </strong>

            afiliado(s) encontrado(s)

        </div>


         <?php
         $url_ordenar = static function ( $columna ) use ( $buscar, $estado, $empresa_filtro, $faena_filtro, $ordenar, $direccion ) {
             $nueva_direccion = ( $ordenar === $columna && $direccion === 'ASC' ) ? 'DESC' : 'ASC';
             return add_query_arg(
                 array(
                     'page'      => 'sinacin-afiliados',
                     'buscar'    => $buscar,
                     'estado'    => $estado,
                     'empresa_id'=> $empresa_filtro,
                     'faena_id'  => $faena_filtro,
                     'ordenar'   => $columna,
                     'direccion' => $nueva_direccion,
                 ),
                 admin_url( 'admin.php' )
             );
         };
         $encabezado_orden = static function ( $etiqueta, $columna ) use ( $url_ordenar, $ordenar, $direccion ) {
             $indicador = $ordenar === $columna ? ( $direccion === 'ASC' ? ' ↑' : ' ↓' ) : ' ↕';
             echo '<a class="sinacin-orden-enlace" href="' . esc_url( $url_ordenar( $columna ) ) . '">'
                 . esc_html( $etiqueta . $indicador ) . '</a>';
         };
         ?>

        <!-- ==========================================
             TABLA
             ========================================== -->

        <?php if ( $estado === 'ACTIVA' ) : ?>
        <form id="sinacin-form-masivo" method="post" class="sinacin-masivo-controles">
            <?php wp_nonce_field( 'sinacin_operacion_masiva_afiliados', 'sinacin_masiva_nonce' ); ?>
            <label for="sinacin-operacion-masiva">Acción masiva</label>
            <select name="sinacin_operacion_masiva" id="sinacin-operacion-masiva" required>
                <option value="">Seleccionar acción</option>
                <option value="desafiliar">Desafiliar seleccionados</option>
                <option value="cambiar_faena">Cambiar faena de seleccionados</option>
            </select>
            <label for="sinacin-faena-destino" id="sinacin-destino-etiqueta" hidden>Faena de destino</label>
            <select name="sinacin_faena_destino" id="sinacin-faena-destino" hidden disabled>
                <option value="">Seleccionar faena activa</option>
                <?php
                // Opciones con empresa para validar coincidencia entre todos los seleccionados.
                $destinos = $wpdb->get_results(
                    "SELECT f.id, f.empresa_id, f.nombre_faena, e.nombre_empresa
                     FROM {$tabla_faenas} f INNER JOIN {$tabla_empresas} e ON e.id = f.empresa_id
                     WHERE f.estado = 'ACTIVA' AND e.estado = 'ACTIVA'
                     ORDER BY e.nombre_empresa ASC, f.nombre_faena ASC"
                );
                foreach ( $destinos as $opcion_destino ) : ?>
                    <option value="<?php echo esc_attr( $opcion_destino->id ); ?>"
                            data-empresa="<?php echo esc_attr( $opcion_destino->empresa_id ); ?>">
                        <?php echo esc_html( $opcion_destino->nombre_empresa . ' — ' . $opcion_destino->nombre_faena ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button button-secondary" id="sinacin-aplicar-masiva" disabled>Aplicar a seleccionados</button>
            <span id="sinacin-seleccion-contador" class="description">0 seleccionados (máximo 20 por página)</span>
        </form>
        <?php endif; ?>

        <div class="sinacin-tabla-wrapper">

            <table
                class="widefat fixed striped sinacin-tabla-afiliados"
            >

                <thead>

                    <tr>
                        <?php if ( $estado === 'ACTIVA' ) : ?>
                        <th class="sinacin-columna-seleccion"><input type="checkbox" id="sinacin-seleccionar-todos" aria-label="Seleccionar todos los afiliados de esta página"></th>
                        <?php endif; ?>

                        <th>
                            <?php $encabezado_orden( 'Afiliado', 'afiliado' ); ?>
                        </th>

                        <th>
                            <?php $encabezado_orden( 'RUT', 'rut' ); ?>
                        </th>

                        <th>
                            <?php $encabezado_orden( 'Empresa', 'empresa' ); ?>
                        </th>

                        <th>
                            <?php $encabezado_orden( 'Faena / Obra', 'faena' ); ?>
                        </th>

                        <th>
                            <?php $encabezado_orden( 'Estado', 'estado' ); ?>
                        </th>

                        <th>
                            <?php $encabezado_orden( 'Fecha afiliación', 'fecha' ); ?>
                        </th>

                        <th>
                            Acciones
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if ( empty( $afiliados ) ) : ?>

                        <tr>

                            <td
                                colspan="<?php echo $estado === 'ACTIVA' ? 8 : 7; ?>"
                            >

                                No se encontraron afiliados.

                            </td>

                        </tr>

                    <?php else : ?>


                        <?php foreach ( $afiliados as $afiliado ) : ?>

                            <?php

                            $nombre_completo =
                                trim(
                                    $afiliado->nombres .
                                    ' ' .
                                    $afiliado->apellido_paterno .
                                    ' ' .
                                    $afiliado->apellido_materno
                                );


                            $url_detalle =
                                add_query_arg(
                                    array(
                                        'page' =>
                                            'sinacin-afiliados',
                                        'detalle' =>
                                            $afiliado->persona_id,
                                    ),
                                    admin_url(
                                        'admin.php'
                                    )
                                );


                            $url_editar =
                                add_query_arg(
                                    array(
                                        'page' =>
                                            'sinacin-afiliados',
                                        'editar' =>
                                            $afiliado->persona_id,
                                    ),
                                    admin_url(
                                        'admin.php'
                                    )
                                );


                            /*
                             * URL segura para descargar
                             * el certificado.
                             */

                            $url_descargar_certificado = '';

                            if ( $afiliado->estado === 'ACTIVA' ) {

                                $certificado_nonce =
                                    wp_create_nonce(
                                        'sinacin_descargar_certificado'
                                    );

                                $url_descargar_certificado =
                                    add_query_arg(
                                        array(
                                            'sinacin_descargar_certificado' => 1,
                                            'afiliacion_id' =>
                                                $afiliado->afiliacion_id,
                                            'sinacin_certificado_nonce' =>
                                                $certificado_nonce,
                                        ),
                                        admin_url(
                                            'admin.php'
                                        )
                                    );
                            }

                            ?>

                            <tr>
                                <?php if ( $estado === 'ACTIVA' ) : ?>
                                <td class="sinacin-columna-seleccion">
                                    <?php if ( $afiliado->estado === 'ACTIVA' ) : ?>
                                    <input type="checkbox" class="sinacin-seleccionar-afiliado"
                                           name="sinacin_afiliaciones_seleccionadas[]"
                                           value="<?php echo esc_attr( $afiliado->afiliacion_id ); ?>"
                                           data-empresa="<?php echo esc_attr( $afiliado->empresa_id ); ?>"
                                           data-faena="<?php echo esc_attr( $afiliado->faena_id ); ?>"
                                           form="sinacin-form-masivo"
                                           aria-label="Seleccionar afiliación de <?php echo esc_attr( $nombre_completo ); ?>">
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>

                                <td>

                                    <strong>
                                        <?php echo esc_html(
                                            $nombre_completo
                                        ); ?>
                                    </strong>

                                    <br>

                                    <span class="sinacin-texto-secundario">

                                        <?php echo esc_html(
                                            $afiliado->correo
                                        ); ?>

                                    </span>

                                </td>


                                <td>

                                    <?php echo esc_html(
                                        sinacin_afiliados_formatear_rut(
                                            $afiliado->rut
                                        )
                                    ); ?>

                                </td>


                                <td>

                                    <strong>
                                        <?php echo esc_html(
                                            $afiliado->nombre_empresa
                                        ); ?>
                                    </strong>

                                    <br>

                                    <span class="sinacin-texto-secundario">

                                        <?php echo esc_html(
                                            sinacin_afiliados_formatear_rut(
                                                $afiliado->rut_empresa
                                            )
                                        ); ?>

                                    </span>

                                </td>


                                <td>

                                    <strong>
                                        <?php echo esc_html(
                                            $afiliado->nombre_faena
                                        ); ?>
                                    </strong>

                                </td>


                                <td>

                                    <?php if (
                                        $afiliado->estado === 'ACTIVA'
                                    ) : ?>

                                        <span class="sinacin-estado-activa">
                                            ACTIVA
                                        </span>

                                    <?php else : ?>

                                        <span class="sinacin-estado-desafiliada">
                                            DESAFILIADA
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php echo esc_html(
                                        $afiliado->fecha_afiliacion
                                    ); ?>

                                </td>


                                <td>

                                    <a
                                        href="<?php echo esc_url(
                                            $url_detalle
                                        ); ?>"
                                        class="button button-small"
                                    >
                                        Ver
                                    </a>


                                    <?php if (
                                        $afiliado->estado === 'ACTIVA'
                                    ) : ?>

                                        <a
                                            href="<?php echo esc_url(
                                                $url_editar
                                            ); ?>"
                                            class="button button-small"
                                        >
                                            Editar
                                        </a>

                                    <?php endif; ?>


                                    <?php if ( $afiliado->estado === 'ACTIVA' ) : ?>

                                        <a
                                            href="<?php echo esc_url(
                                                $url_descargar_certificado
                                            ); ?>"
                                            class="button button-small"
                                        >
                                            Descargar certificado
                                        </a>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>


        <!-- ==========================================
             PAGINACIÓN
             ========================================== -->

        <div id="sinacin-paginacion-dinamica">
        <?php if (
            $total_paginas > 1
        ) : ?>

            <div class="tablenav">

                <div class="tablenav-pages">

                    <?php

                    echo paginate_links(
                        array(
                            'base' => add_query_arg(
                                'paged',
                                '%#%'
                            ),
                            'format' =>
                                '',
                            'current' =>
                                $pagina_actual,
                            'total' =>
                                $total_paginas,
                            'prev_text' =>
                                '&laquo;',
                            'next_text' =>
                                '&raquo;',
                            'add_args' =>
                                array(
                                    'page' =>
                                        'sinacin-afiliados',
                                    'buscar' =>
                                        $buscar,
                                     'estado' => $estado,
                                     'empresa_id' => $empresa_filtro,
                                     'faena_id' => $faena_filtro,
                                     'ordenar' => $ordenar,
                                     'direccion' => $direccion,
                                ),
                        )
                    );

                    ?>

                </div>

            </div>

        <?php endif; ?>
        </div>

    </div>


    <style>

        .sinacin-filtros {
            display: flex;
            gap: 8px;
            align-items: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }


        .sinacin-masivo-controles { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin:18px 0 10px; }
        .sinacin-masivo-controles label { font-weight:600; }
        .sinacin-columna-seleccion { width:38px; text-align:center; }
        .sinacin-masivo-controles [hidden] { display:none !important; }

        .sinacin-resumen-afiliados {
            margin: 15px 0;
            color: #50575e;
        }


        .sinacin-tabla-wrapper {
            overflow-x: auto;
            margin-top: 15px;
        }


         .sinacin-tabla-afiliados th {
             font-weight: 600;
        }
        .sinacin-orden-enlace { color: inherit; text-decoration: none; }
        .sinacin-orden-enlace:hover { color: #2271b1; text-decoration: underline; }
        .sinacin-filtros label { font-weight: 600; }


        .sinacin-texto-secundario {
            color: #646970;
            font-size: 12px;
        }


        .sinacin-estado-activa,
        .sinacin-estado-desafiliada {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
        }


        .sinacin-estado-activa {
            background: #d1e7dd;
            color: #0f5132;
        }


        .sinacin-estado-desafiliada {
            background: #f8d7da;
            color: #842029;
        }


        @media screen and (max-width: 782px) {

            .sinacin-filtros {
                align-items: stretch;
                flex-direction: column;
            }


            .sinacin-filtros input,
            .sinacin-filtros select,
            .sinacin-filtros button,
            .sinacin-filtros a {
                width: 100%;
                box-sizing: border-box;
            }

        }

    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('.sinacin-filtros');
        if (!form) return;
        var campo = form.querySelector('[name="buscar"]');
        var empresa = document.getElementById('sinacin-filtro-empresa');
        var faena = document.getElementById('sinacin-filtro-faena');
        var estado = document.getElementById('sinacin-filtro-estado');
        var aviso = document.getElementById('sinacin-busqueda-estado');
        var temporizador = null;
        var controlador = null;
        var secuencia = 0;

        function pareceRut(valor) {
            return /^[0-9.\-\sKk]+$/.test(valor) && /[0-9]/.test(valor);
        }
        function formatoRut(valor) {
            var limpio = valor.toUpperCase().replace(/[^0-9K]/g, '');
            if (limpio.length < 2) return limpio;
            var cuerpo = limpio.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return cuerpo + '-' + limpio.slice(-1);
        }
        function urlFiltros() {
            var url = new URL(form.action || window.location.href, window.location.href);
            url.search = new URLSearchParams(new FormData(form)).toString();
            url.searchParams.delete('paged');
            return url;
        }
        function actualizar(url, guardarHistorial) {
            var turno = ++secuencia;
            if (controlador) controlador.abort();
            controlador = new AbortController();
            aviso.textContent = 'Buscando…';
            fetch(url.toString(), { credentials: 'same-origin', signal: controlador.signal })
                .then(function (respuesta) {
                    if (!respuesta.ok) throw new Error('Error de conexión');
                    return respuesta.text();
                })
                .then(function (html) {
                    if (turno !== secuencia) return;
                    var documento = new DOMParser().parseFromString(html, 'text/html');
                    ['.sinacin-resumen-afiliados', '.sinacin-tabla-wrapper', '#sinacin-paginacion-dinamica'].forEach(function (selector) {
                        var nuevo = documento.querySelector(selector);
                        var actual = document.querySelector(selector);
                        if (nuevo && actual) actual.replaceWith(nuevo);
                    });
                    if (window.sinacinRefrescarMasivo) window.sinacinRefrescarMasivo();
                    // Sincronizar ambos filtros con las opciones validadas en PHP.
                    // Al pasar a activos, desaparecen empresas/faenas inactivas.
                    var nuevasEmpresas = documento.getElementById('sinacin-filtro-empresa');
                    if (nuevasEmpresas) {
                        empresa.innerHTML = nuevasEmpresas.innerHTML;
                        empresa.value = nuevasEmpresas.value;
                    }
                    var nuevasFaenas = documento.getElementById('sinacin-filtro-faena');
                    if (nuevasFaenas) {
                        faena.innerHTML = nuevasFaenas.innerHTML;
                        faena.disabled = nuevasFaenas.disabled;
                        faena.value = nuevasFaenas.value;
                    }
                    // Reflejar la selección efectiva en la URL para evitar filtros obsoletos.
                    url.searchParams.set('empresa_id', empresa.value);
                    url.searchParams.set('faena_id', faena.value);
                    var nuevoOrden = documento.querySelector('.sinacin-filtros [name="ordenar"]');
                    var nuevaDireccion = documento.querySelector('.sinacin-filtros [name="direccion"]');
                    if (nuevoOrden) form.querySelector('[name="ordenar"]').value = nuevoOrden.value;
                    if (nuevaDireccion) form.querySelector('[name="direccion"]').value = nuevaDireccion.value;
                    if (masivo) {
                        masivo.hidden = estado.value !== 'ACTIVA';
                        if (window.sinacinRefrescarMasivo) window.sinacinRefrescarMasivo();
                    }
                    if (guardarHistorial) window.history.replaceState({}, '', url.toString());
                    aviso.textContent = 'Resultados actualizados';
                })
                .catch(function (error) {
                    if (error.name !== 'AbortError' && turno === secuencia) {
                        aviso.textContent = 'No se pudo actualizar. Presiona Enter para buscar.';
                    }
                });
        }
        function programar() {
            clearTimeout(temporizador);
            temporizador = setTimeout(function () { actualizar(urlFiltros(), true); }, 300);
        }
        campo.addEventListener('input', function () {
            var inicio = campo.selectionStart;
            var antes = campo.value;
            if (pareceRut(antes) && /[.\-]/.test(antes)) {
                var formateado = formatoRut(antes);
                if (formateado !== antes) {
                    campo.value = formateado;
                    if (inicio === antes.length) campo.setSelectionRange(formateado.length, formateado.length);
                }
            }
            programar();
        });
        empresa.addEventListener('change', function () { faena.value = '0'; programar(); });
        faena.addEventListener('change', programar);
        estado.addEventListener('change', programar);
        form.addEventListener('submit', function (evento) {
            evento.preventDefault();
            clearTimeout(temporizador);
            actualizar(urlFiltros(), true);
        });
        var masivo = document.getElementById('sinacin-form-masivo');
        if (masivo) {
            var operacion = document.getElementById('sinacin-operacion-masiva');
            var destino = document.getElementById('sinacin-faena-destino');
            var destinoEtiqueta = document.getElementById('sinacin-destino-etiqueta');
            var aplicar = document.getElementById('sinacin-aplicar-masiva');
            var contador = document.getElementById('sinacin-seleccion-contador');
            function seleccionados() {
                return Array.from(document.querySelectorAll('.sinacin-seleccionar-afiliado:checked'));
            }
            function refrescarMasivo() {
                var seleccion = seleccionados();
                var cambio = operacion.value === 'cambiar_faena';
                destino.hidden = !cambio;
                destinoEtiqueta.hidden = !cambio;
                destino.disabled = !cambio;
                var empresas = Array.from(new Set(seleccion.map(function (item) { return item.dataset.empresa; })));
                Array.from(destino.options).forEach(function (opcion) {
                    if (!opcion.value) return;
                    opcion.disabled = empresas.length !== 1 || opcion.dataset.empresa !== empresas[0];
                });
                if (destino.selectedOptions.length && destino.selectedOptions[0].disabled) destino.value = '';
                var valido = seleccion.length >= 2 && seleccion.length <= 20 && operacion.value &&
                    (!cambio || (empresas.length === 1 && destino.value));
                aplicar.disabled = !valido;
                contador.textContent = seleccion.length + ' seleccionados (máximo 20 por página)' +
                    (cambio && empresas.length > 1 ? ' — selecciona afiliados de una sola empresa' : '');
                var todos = document.getElementById('sinacin-seleccionar-todos');
                var casillas = document.querySelectorAll('.sinacin-seleccionar-afiliado');
                if (todos) {
                    todos.checked = casillas.length > 0 && seleccion.length === casillas.length;
                    todos.indeterminate = seleccion.length > 0 && seleccion.length < casillas.length;
                }
            }
            document.addEventListener('change', function (evento) {
                if (evento.target.id === 'sinacin-seleccionar-todos') {
                    document.querySelectorAll('.sinacin-seleccionar-afiliado').forEach(function (casilla) {
                        casilla.checked = evento.target.checked;
                    });
                }
                if (evento.target.matches('.sinacin-seleccionar-afiliado, #sinacin-seleccionar-todos, #sinacin-operacion-masiva, #sinacin-faena-destino')) {
                    refrescarMasivo();
                }
            });
            masivo.addEventListener('submit', function (evento) {
                if (estado.value !== 'ACTIVA') { evento.preventDefault(); return; }
                var seleccion = seleccionados();
                if (seleccion.length < 2 || seleccion.length > 20 || aplicar.disabled) {
                    evento.preventDefault();
                    return;
                }
                var mensaje = operacion.value === 'desafiliar'
                    ? '¿Confirmas la desafiliación de ' + seleccion.length + ' afiliaciones? Esta acción quedará registrada en el historial.'
                    : '¿Confirmas el cambio de faena de ' + seleccion.length + ' afiliaciones? Cada cambio quedará registrado en el historial.';
                if (!window.confirm(mensaje)) evento.preventDefault();
            });
            // Los resultados AJAX reemplazan las filas: la selección es exclusivamente de la página visible.
            var observer = new MutationObserver(refrescarMasivo);
            observer.observe(document.querySelector('.sinacin-admin'), { childList:true, subtree:false });
            refrescarMasivo();
            window.sinacinRefrescarMasivo = refrescarMasivo;
        }
        document.addEventListener('click', function (evento) {
            var enlace = evento.target.closest('.sinacin-orden-enlace, #sinacin-paginacion-dinamica a');
            if (!enlace) return;
            evento.preventDefault();
            clearTimeout(temporizador);
            var url = new URL(enlace.href);
            if (url.searchParams.has('ordenar')) {
                form.querySelector('[name="ordenar"]').value = url.searchParams.get('ordenar');
                form.querySelector('[name="direccion"]').value = url.searchParams.get('direccion');
            }
            actualizar(url, true);
        });
    });
    </script>

    <?php
}


/* =========================================================
 * DETALLE
 * ========================================================= */

function sinacin_mostrar_detalle_afiliado(
    $persona_id
) {

    global $wpdb;

    $tabla_personas =
        $wpdb->prefix . 'sinacin_personas';

    $tabla_afiliaciones =
        $wpdb->prefix . 'sinacin_afiliaciones';

    $tabla_empresas =
        $wpdb->prefix . 'sinacin_empresas';

    $tabla_faenas =
        $wpdb->prefix . 'sinacin_faenas';

    $tabla_historial =
        $wpdb->prefix . 'sinacin_historial';

    $tabla_certificados =
        $wpdb->prefix . 'sinacin_certificados';


    $afiliado =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT
                    a.*,

                    p.nombres,
                    p.apellido_paterno,
                    p.apellido_materno,
                    p.rut,
                    p.celular,
                    p.correo,
                    p.cargo,

                    e.nombre_empresa,
                    e.rut_empresa,

                    f.nombre_faena

                FROM {$tabla_afiliaciones} a

                INNER JOIN {$tabla_personas} p
                    ON p.id = a.persona_id

                INNER JOIN {$tabla_empresas} e
                    ON e.id = a.empresa_id

                INNER JOIN {$tabla_faenas} f
                    ON f.id = a.faena_id

                WHERE a.persona_id = %d

                ORDER BY
                    a.id DESC

                LIMIT 1
                ",
                $persona_id
            )
        );


    if ( ! $afiliado ) {

        echo '<div class="wrap">';
        echo '<h1>Afiliado no encontrado</h1>';

        echo '<a href="' .
            esc_url(
                admin_url(
                    'admin.php?page=sinacin-afiliados'
                )
            ) .
            '" class="button">';

        echo 'Volver a afiliados';

        echo '</a>';
        echo '</div>';

        return;
    }


    $nombre_completo =
        trim(
            $afiliado->nombres .
            ' ' .
            $afiliado->apellido_paterno .
            ' ' .
            $afiliado->apellido_materno
        );


    $url_volver =
        admin_url(
            'admin.php?page=sinacin-afiliados'
        );


    $url_editar =
        add_query_arg(
            array(
                'page' =>
                    'sinacin-afiliados',
                'editar' =>
                    $persona_id,
            ),
            admin_url(
                'admin.php'
            )
        );


    ?>

    <div class="wrap sinacin-detalle-afiliado">

        <h1>
            <?php echo esc_html(
                $nombre_completo
            ); ?>
        </h1>


        <p>

            <a
                href="<?php echo esc_url(
                    $url_volver
                ); ?>"
                class="button"
            >
                &laquo; Volver a afiliados
            </a>


            <?php if (
                $afiliado->estado === 'ACTIVA'
            ) : ?>

                <a
                    href="<?php echo esc_url(
                        $url_editar
                    ); ?>"
                    class="button button-primary"
                >
                    Editar afiliado
                </a>

            <?php endif; ?>

        </p>


        <!-- ==========================================
             ESTADO
             ========================================== -->

        <div class="sinacin-card">

            <h2>
                Estado de la afiliación
            </h2>

            <p>

                <strong>
                    Estado:
                </strong>

                <?php if (
                    $afiliado->estado === 'ACTIVA'
                ) : ?>

                    <span class="sinacin-estado-activa">
                        ACTIVA
                    </span>

                <?php else : ?>

                    <span class="sinacin-estado-desafiliada">
                        DESAFILIADA
                    </span>

                <?php endif; ?>

            </p>


            <p>

                <strong>
                    Fecha de afiliación:
                </strong>

                <?php echo esc_html(
                    $afiliado->fecha_afiliacion
                ); ?>

            </p>


            <?php if (
                ! empty(
                    $afiliado->fecha_desafiliacion
                )
            ) : ?>

                <p>

                    <strong>
                        Fecha de desafiliación:
                    </strong>

                    <?php echo esc_html(
                        $afiliado->fecha_desafiliacion
                    ); ?>

                </p>

            <?php endif; ?>

        </div>


        <!-- ==========================================
             DATOS PERSONALES
             ========================================== -->

        <div class="sinacin-card">

            <h2>
                Datos del afiliado
            </h2>

            <table class="sinacin-detalle-tabla">

                <tr>

                    <th>
                        Nombre completo
                    </th>

                    <td>
                        <?php echo esc_html(
                            $nombre_completo
                        ); ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        RUT
                    </th>

                    <td>
                        <?php echo esc_html(
                            sinacin_afiliados_formatear_rut(
                                $afiliado->rut
                            )
                        ); ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        Celular
                    </th>

                    <td>
                        <?php echo esc_html(
                            $afiliado->celular
                        ); ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        Correo electrónico
                    </th>

                    <td>
                        <?php echo esc_html(
                            $afiliado->correo
                        ); ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        Cargo
                    </th>

                    <td>
                        <?php echo esc_html(
                            $afiliado->cargo
                        ); ?>
                    </td>

                </tr>

            </table>

        </div>


        <!-- ==========================================
             EMPRESA
             ========================================== -->

        <div class="sinacin-card">

            <h2>
                Empresa
            </h2>

            <table class="sinacin-detalle-tabla">

                <tr>

                    <th>
                        Empresa
                    </th>

                    <td>
                        <?php echo esc_html(
                            $afiliado->nombre_empresa
                        ); ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        RUT empresa
                    </th>

                    <td>
                        <?php echo esc_html(
                            sinacin_afiliados_formatear_rut(
                                $afiliado->rut_empresa
                            )
                        ); ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        Faena / Obra
                    </th>

                    <td>
                        <?php echo esc_html(
                            $afiliado->nombre_faena
                        ); ?>
                    </td>

                </tr>

            </table>

        </div>


        <!-- ==========================================
             SOLICITUD
             ========================================== -->

        <div class="sinacin-card">

            <h2>
                Solicitud de origen
            </h2>

            <p>

                <strong>
                    Solicitud:
                </strong>

                #<?php echo esc_html(
                    $afiliado->solicitud_id
                ); ?>

            </p>

        </div>


        <!-- ==========================================
             DESAFILIAR
             ========================================== -->

        <?php if (
            $afiliado->estado === 'ACTIVA'
        ) : ?>

            <div class="sinacin-card sinacin-card-peligro">

                <h2>
                    Desafiliar
                </h2>

                <p>
                    Esta acción cerrará la afiliación actual,
                    pero <strong>no eliminará los registros
                    históricos</strong>.
                </p>


                <form
                    method="post"
                    onsubmit="return confirm('¿Está seguro de que desea desafiliar a este afiliado?');"
                >

                    <?php

                    wp_nonce_field(
                        'sinacin_desafiliar_afiliado',
                        'sinacin_desafiliar_nonce'
                    );

                    ?>


                    <input
                        type="hidden"
                        name="afiliacion_id"
                        value="<?php echo esc_attr(
                            $afiliado->id
                        ); ?>"
                    >


                    <button
                        type="submit"
                        name="sinacin_desafiliar"
                        class="button button-secondary"
                    >
                        Desafiliar afiliado
                    </button>

                </form>

            </div>

        <?php endif; ?>

    </div>


    <style>

        .sinacin-detalle-afiliado {
            max-width: 1000px;
        }


        .sinacin-card {
            background: #ffffff;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            padding: 24px;
            margin: 20px 0;
            box-sizing: border-box;
        }


        .sinacin-card h2 {
            margin-top: 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #eeeeee;
        }


        .sinacin-detalle-tabla {
            width: 100%;
            border-collapse: collapse;
        }


        .sinacin-detalle-tabla th,
        .sinacin-detalle-tabla td {
            padding: 13px 12px;
            border-bottom: 1px solid #eeeeee;
            text-align: left;
        }


        .sinacin-detalle-tabla th {
            width: 30%;
            background: #f6f7f7;
        }


        .sinacin-detalle-tabla td {
            width: 70%;
            word-break: break-word;
        }


        .sinacin-estado-activa,
        .sinacin-estado-desafiliada {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }


        .sinacin-estado-activa {
            background: #d1e7dd;
            color: #0f5132;
        }


        .sinacin-estado-desafiliada {
            background: #f8d7da;
            color: #842029;
        }


        .sinacin-card-peligro {
            border-left: 4px solid #d63638;
        }


        @media screen and (max-width: 600px) {

            .sinacin-card {
                padding: 16px;
            }


            .sinacin-detalle-tabla,
            .sinacin-detalle-tabla tbody,
            .sinacin-detalle-tabla tr,
            .sinacin-detalle-tabla th,
            .sinacin-detalle-tabla td {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }


            .sinacin-detalle-tabla tr {
                margin-bottom: 15px;
            }


            .sinacin-detalle-tabla th {
                background: transparent;
                border: none;
                padding: 4px 0;
            }


            .sinacin-detalle-tabla td {
                border: none;
                padding: 4px 0;
            }

        }

    </style>

    <?php
}


/* =========================================================
 * FORMULARIO DE EDICIÓN
 * ========================================================= */

function sinacin_mostrar_editar_afiliado(
    $persona_id
) {

    global $wpdb;

    $tabla_personas =
        $wpdb->prefix . 'sinacin_personas';

    $tabla_afiliaciones =
        $wpdb->prefix . 'sinacin_afiliaciones';

    $tabla_empresas =
        $wpdb->prefix . 'sinacin_empresas';

    $tabla_faenas =
        $wpdb->prefix . 'sinacin_faenas';


    /*
     * Obtener persona.
     */

    $persona =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$tabla_personas}
                WHERE id = %d
                LIMIT 1
                ",
                $persona_id
            )
        );


    if ( ! $persona ) {

        echo '<div class="wrap">';
        echo '<h1>Afiliado no encontrado</h1>';
        echo '</div>';

        return;
    }


    /*
     * Obtener afiliación activa.
     */

    $afiliacion =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT
                    a.*,
                    e.nombre_empresa,
                    e.rut_empresa,
                    f.nombre_faena

                FROM {$tabla_afiliaciones} a

                INNER JOIN {$tabla_empresas} e
                    ON e.id = a.empresa_id

                INNER JOIN {$tabla_faenas} f
                    ON f.id = a.faena_id

                WHERE a.persona_id = %d
                  AND a.estado = 'ACTIVA'

                ORDER BY
                    a.id DESC

                LIMIT 1
                ",
                $persona_id
            )
        );


    if ( ! $afiliacion ) {

        echo '<div class="wrap">';
        echo '<h1>Afiliación no encontrada</h1>';

        echo '<p>';
        echo 'Este afiliado no tiene una afiliación activa que pueda editarse.';
        echo '</p>';

        echo '<a href="' .
            esc_url(
                admin_url(
                    'admin.php?page=sinacin-afiliados'
                )
            ) .
            '" class="button">';

        echo '&laquo; Volver a afiliados';

        echo '</a>';
        echo '</div>';

        return;
    }


    /*
     * Obtener faenas activas de la empresa.
     */

    $faenas =
        $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT
                    id,
                    nombre_faena,
                    estado

                FROM {$tabla_faenas}

                WHERE empresa_id = %d
                  AND estado = 'ACTIVA'

                ORDER BY
                    nombre_faena ASC
                ",
                $afiliacion->empresa_id
            )
        );


    ?>

    <div class="wrap sinacin-editar-afiliado">

        <h1>
            Editar afiliado
        </h1>


        <p>

            <a
                href="<?php echo esc_url(
                    add_query_arg(
                        array(
                            'page' =>
                                'sinacin-afiliados',
                            'detalle' =>
                                $persona_id,
                        ),
                        admin_url(
                            'admin.php'
                        )
                    )
                ); ?>"
                class="button"
            >
                &laquo; Volver al afiliado
            </a>

        </p>


        <div class="sinacin-card">

            <form
                method="post"
            >

                <?php

                wp_nonce_field(
                    'sinacin_editar_afiliado',
                    'sinacin_editar_afiliado_nonce'
                );

                ?>


                <input
                    type="hidden"
                    name="persona_id"
                    value="<?php echo esc_attr(
                        $persona->id
                    ); ?>"
                >


                <table class="form-table">


                    <!-- ======================================
                         DATOS PERSONALES
                         ====================================== -->

                    <tr>

                        <th>

                            <label for="nombres">
                                Nombres
                            </label>

                        </th>

                        <td>

                            <input
                                type="text"
                                id="nombres"
                                name="nombres"
                                class="regular-text"
                                value="<?php echo esc_attr(
                                    $persona->nombres
                                ); ?>"
                                required
                            >

                        </td>

                    </tr>


                    <tr>

                        <th>

                            <label for="apellido_paterno">
                                Apellido paterno
                            </label>

                        </th>

                        <td>

                            <input
                                type="text"
                                id="apellido_paterno"
                                name="apellido_paterno"
                                class="regular-text"
                                value="<?php echo esc_attr(
                                    $persona->apellido_paterno
                                ); ?>"
                                required
                            >

                        </td>

                    </tr>


                    <tr>

                        <th>

                            <label for="apellido_materno">
                                Apellido materno
                            </label>

                        </th>

                        <td>

                            <input
                                type="text"
                                id="apellido_materno"
                                name="apellido_materno"
                                class="regular-text"
                                value="<?php echo esc_attr(
                                    $persona->apellido_materno
                                ); ?>"
                                required
                            >

                        </td>

                    </tr>


                    <tr>

                        <th>
                            RUT
                        </th>

                        <td>

                            <strong>
                                <?php echo esc_html(
                                    sinacin_afiliados_formatear_rut(
                                        $persona->rut
                                    )
                                ); ?>
                            </strong>

                            <p class="description">
                                El RUT no puede modificarse desde esta pantalla.
                            </p>

                        </td>

                    </tr>


                    <tr>

                        <th>

                            <label for="celular">
                                Celular
                            </label>

                        </th>

                        <td>

                            <input
                                type="text"
                                id="celular"
                                name="celular"
                                class="regular-text"
                                value="<?php echo esc_attr(
                                    $persona->celular
                                ); ?>"
                                required
                            >

                        </td>

                    </tr>


                    <tr>

                        <th>

                            <label for="correo">
                                Correo electrónico
                            </label>

                        </th>

                        <td>

                            <input
                                type="email"
                                id="correo"
                                name="correo"
                                class="regular-text"
                                value="<?php echo esc_attr(
                                    $persona->correo
                                ); ?>"
                                required
                            >

                        </td>

                    </tr>


                    <tr>

                        <th>

                            <label for="cargo">
                                Cargo
                            </label>

                        </th>

                        <td>

                            <input
                                type="text"
                                id="cargo"
                                name="cargo"
                                class="regular-text"
                                value="<?php echo esc_attr(
                                    $persona->cargo
                                ); ?>"
                                required
                            >

                        </td>

                    </tr>


                    <!-- ======================================
                         EMPRESA
                         ====================================== -->

                    <tr>

                        <th>
                            Empresa
                        </th>

                        <td>

                            <strong>
                                <?php echo esc_html(
                                    $afiliacion->nombre_empresa
                                ); ?>
                            </strong>

                            <br>

                            <span class="sinacin-texto-secundario">

                                RUT:
                                <?php echo esc_html(
                                    sinacin_afiliados_formatear_rut(
                                        $afiliacion->rut_empresa
                                    )
                                ); ?>

                            </span>

                            <p class="description">
                                La empresa no puede modificarse desde esta pantalla.
                            </p>

                        </td>

                    </tr>


                    <!-- ======================================
                         FAENA
                         ====================================== -->

                    <tr>

                        <th>

                            <label for="faena_id">
                                Faena / Obra
                            </label>

                        </th>

                        <td>

                            <select
                                id="faena_id"
                                name="faena_id"
                                class="regular-text"
                                required
                            >

                                <option value="">
                                    Seleccionar faena / obra
                                </option>


                                <?php if ( ! empty( $faenas ) ) : ?>

                                    <?php foreach ( $faenas as $faena ) : ?>

                                        <option
                                            value="<?php echo esc_attr(
                                                $faena->id
                                            ); ?>"
                                            <?php selected(
                                                $afiliacion->faena_id,
                                                $faena->id
                                            ); ?>
                                        >
                                            <?php echo esc_html(
                                                $faena->nombre_faena
                                            ); ?>
                                        </option>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </select>


                            <?php if ( empty( $faenas ) ) : ?>

                                <p class="description sinacin-descripcion-error">
                                    Esta empresa no tiene faenas activas disponibles.
                                </p>

                            <?php else : ?>

                                <p class="description">
                                    Seleccione la faena u obra donde actualmente trabaja el afiliado.
                                </p>

                            <?php endif; ?>

                        </td>

                    </tr>


                </table>


                <p class="submit">

                    <button
                        type="submit"
                        name="sinacin_guardar_afiliado"
                        class="button button-primary"
                    >
                        Guardar cambios
                    </button>

                </p>

            </form>

        </div>

    </div>


    <style>

        .sinacin-editar-afiliado {
            max-width: 1000px;
        }


        .sinacin-editar-afiliado .sinacin-card {
            background: #ffffff;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            padding: 24px;
            margin-top: 20px;
        }


        .sinacin-editar-afiliado .sinacin-texto-secundario {
            color: #646970;
            font-size: 13px;
        }


        .sinacin-editar-afiliado .sinacin-descripcion-error {
            color: #b32d2e;
        }


        .sinacin-editar-afiliado select {
            min-width: 300px;
        }


        @media screen and (max-width: 782px) {

            .sinacin-editar-afiliado select {
                width: 100%;
                min-width: 0;
            }

        }

    </style>

    <?php
}

