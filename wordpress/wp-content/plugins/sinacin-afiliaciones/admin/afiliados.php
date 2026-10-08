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
        'manage_options',
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
        ! current_user_can( 'manage_options' )
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
        ! current_user_can( 'manage_options' )
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

    if ( ! current_user_can( 'manage_options' ) ) {
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
 * PÁGINA PRINCIPAL
 * ========================================================= */

function sinacin_pagina_afiliados() {

    if (
        ! current_user_can( 'manage_options' )
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


    if ( $buscar !== '' ) {

        $where .= "
            AND (
                CONCAT(
                    p.nombres,
                    ' ',
                    p.apellido_paterno,
                    ' ',
                    p.apellido_materno
                ) LIKE %s
                OR p.rut LIKE %s
                OR e.nombre_empresa LIKE %s
                OR e.rut_empresa LIKE %s
                OR f.nombre_faena LIKE %s
            )
        ";

        $buscar_like =
            '%' .
            $wpdb->esc_like(
                $buscar
            ) .
            '%';

        $params[] = $buscar_like;
        $params[] = $buscar_like;
        $params[] = $buscar_like;
        $params[] = $buscar_like;
        $params[] = $buscar_like;
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

        ORDER BY
            a.id DESC

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
                placeholder="Buscar por nombre, RUT, empresa o faena..."
                class="regular-text"
            >

            <select name="estado">

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

            <button
                type="submit"
                class="button button-primary"
            >
                Buscar
            </button>

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


        <!-- ==========================================
             TABLA
             ========================================== -->

        <div class="sinacin-tabla-wrapper">

            <table
                class="widefat fixed striped sinacin-tabla-afiliados"
            >

                <thead>

                    <tr>

                        <th>
                            Afiliado
                        </th>

                        <th>
                            RUT
                        </th>

                        <th>
                            Empresa
                        </th>

                        <th>
                            Faena / Obra
                        </th>

                        <th>
                            Estado
                        </th>

                        <th>
                            Fecha afiliación
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
                                colspan="7"
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
                                    'estado' =>
                                        $estado,
                                ),
                        )
                    );

                    ?>

                </div>

            </div>

        <?php endif; ?>

    </div>


    <style>

        .sinacin-filtros {
            display: flex;
            gap: 8px;
            align-items: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }


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

