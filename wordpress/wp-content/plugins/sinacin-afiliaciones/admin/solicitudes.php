<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * ==========================================
 * MENÚ SOLICITUDES
 * ==========================================
 */

add_action(
    'admin_menu',
    'sinacin_registrar_menu_solicitudes',
    20
);


function sinacin_registrar_menu_solicitudes() {

    add_submenu_page(
        'sinacin',
        'Solicitudes',
        'Solicitudes',
        'manage_options',
        'sinacin-solicitudes',
        'sinacin_pagina_solicitudes'
    );

}


/**
 * ==========================================
 * FORMATEAR RUT
 * ==========================================
 */

if ( ! function_exists( 'sinacin_formatear_rut' ) ) {

    function sinacin_formatear_rut( $rut ) {

        $rut = strtoupper(
            trim( $rut )
        );

        $rut = str_replace(
            array(
                '.',
                '-',
                ' ',
            ),
            '',
            $rut
        );

        if ( strlen( $rut ) < 2 ) {
            return $rut;
        }

        $cuerpo = substr(
            $rut,
            0,
            -1
        );

        $dv = substr(
            $rut,
            -1
        );

        $resultado = '';
        $contador = 0;

        for (
            $i = strlen( $cuerpo ) - 1;
            $i >= 0;
            $i--
        ) {

            $resultado =
                $cuerpo[ $i ] .
                $resultado;

            $contador++;

            if (
                $contador === 3 &&
                $i !== 0
            ) {

                $resultado =
                    '.' .
                    $resultado;

                $contador = 0;
            }
        }

        return (
            $resultado .
            '-' .
            $dv
        );
    }

}


/**
 * ==========================================
 * PÁGINA PRINCIPAL
 * ==========================================
 */

function sinacin_pagina_solicitudes() {

    if (
        ! current_user_can(
            'manage_options'
        )
    ) {

        wp_die(
            'No tienes permisos para acceder a esta sección.'
        );

    }

    global $wpdb;

    $tabla_solicitudes =
        $wpdb->prefix .
        'sinacin_solicitudes';

    $tabla_personas =
        $wpdb->prefix .
        'sinacin_personas';

    $tabla_empresas =
        $wpdb->prefix .
        'sinacin_empresas';

    $tabla_faenas =
        $wpdb->prefix .
        'sinacin_faenas';

    $tabla_afiliaciones =
        $wpdb->prefix .
        'sinacin_afiliaciones';

    $tabla_historial =
        $wpdb->prefix .
        'sinacin_historial';


    /**
     * ==========================================
     * MENSAJES
     * ==========================================
     */

    $mensaje = '';

    $tipo_mensaje = '';


    /**
     * ==========================================
     * PROCESAR APROBACIÓN
     * ==========================================
     */

    if (
        isset(
            $_POST['sinacin_aprobar_solicitud']
        )
    ) {

        $solicitud_id =
            isset(
                $_POST['solicitud_id']
            )
                ? absint(
                    $_POST['solicitud_id']
                )
                : 0;


        if (
            ! isset(
                $_POST['sinacin_solicitud_nonce']
            )
            ||
            ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_POST['sinacin_solicitud_nonce']
                    )
                ),
                'sinacin_aprobar_solicitud_' .
                $solicitud_id
            )
        ) {

            $mensaje =
                'La sesión de seguridad no es válida.';

            $tipo_mensaje =
                'error';

        }
        elseif (
            $solicitud_id <= 0
        ) {

            $mensaje =
                'Solicitud no válida.';

            $tipo_mensaje =
                'error';

        }
        else {

            /**
             * Obtener solicitud.
             */

            $solicitud =
                $wpdb->get_row(
                    $wpdb->prepare(
                        "
                        SELECT
                            s.*,
                            p.nombres,
                            p.apellido_paterno,
                            p.apellido_materno,
                            p.rut,
                            p.correo,
                            e.rut_empresa,
                            e.nombre_empresa,
                            e.estado AS estado_empresa,
                            f.nombre_faena,
                            f.estado AS estado_faena
                        FROM {$tabla_solicitudes} s

                        INNER JOIN {$tabla_personas} p
                            ON p.id = s.persona_id

                        INNER JOIN {$tabla_empresas} e
                            ON e.id = s.empresa_id

                        INNER JOIN {$tabla_faenas} f
                            ON f.id = s.faena_id
                            AND f.empresa_id = s.empresa_id

                        WHERE s.id = %d

                        LIMIT 1
                        ",
                        $solicitud_id
                    )
                );


            if (
                ! $solicitud
            ) {

                $mensaje =
                    'La solicitud no existe.';

                $tipo_mensaje =
                    'error';

            }
            elseif (
                $solicitud->estado !==
                'PENDIENTE'
            ) {

                $mensaje =
                    'La solicitud ya fue procesada anteriormente.';

                $tipo_mensaje =
                    'error';

            }
            elseif (
                $solicitud->estado_empresa !==
                'ACTIVA'
            ) {

                $mensaje =
                    'No se puede aprobar una solicitud asociada a una empresa inactiva.';

                $tipo_mensaje =
                    'error';

            }
            elseif (
                $solicitud->estado_faena !==
                'ACTIVA'
            ) {

                $mensaje =
                    'No se puede aprobar una solicitud asociada a una faena u obra inactiva.';

                $tipo_mensaje =
                    'error';

            }
            else {

                /**
                 * Verificar afiliación activa.
                 */

                $afiliacion_existente =
                    $wpdb->get_var(
                        $wpdb->prepare(
                            "
                            SELECT id
                            FROM {$tabla_afiliaciones}
                            WHERE persona_id = %d
                            AND estado = 'ACTIVA'
                            LIMIT 1
                            ",
                            $solicitud->persona_id
                        )
                    );


                if (
                    $afiliacion_existente
                ) {

                    $mensaje =
                        'La persona ya posee una afiliación activa.';

                    $tipo_mensaje =
                        'error';

                }
                else {

                    /**
                     * Iniciar transacción.
                     */

                    $wpdb->query(
                        'START TRANSACTION'
                    );


                    $ahora =
                        current_time(
                            'mysql'
                        );


                    /**
                     * Crear afiliación.
                     */

                    $insertar_afiliacion =
                        $wpdb->insert(
                            $tabla_afiliaciones,

                            array(
                                'persona_id' =>
                                    $solicitud->persona_id,

                                'empresa_id' =>
                                    $solicitud->empresa_id,

                                'faena_id' =>
                                    $solicitud->faena_id,

                                'solicitud_id' =>
                                    $solicitud_id,

                                'estado' =>
                                    'ACTIVA',

                                'fecha_afiliacion' =>
                                    $ahora,

                                'fecha_desafiliacion' =>
                                    null,
                            ),

                            array(
                                '%d',
                                '%d',
                                '%d',
                                '%d',
                                '%s',
                                '%s',
                                null,
                            )
                        );


                    if (
                        $insertar_afiliacion === false
                    ) {

                        $wpdb->query(
                            'ROLLBACK'
                        );

                        $mensaje =
                            'No fue posible crear la afiliación.';

                        $tipo_mensaje =
                            'error';

                    }
                    else {

                        $afiliacion_id =
                            $wpdb->insert_id;


                        /**
                         * Actualizar solicitud.
                         */

                        $actualizar_solicitud =
                            $wpdb->update(
                                $tabla_solicitudes,

                                array(
                                    'estado' =>
                                        'APROBADA',

                                    'fecha_revision' =>
                                        $ahora,

                                    'usuario_revisor' =>
                                        get_current_user_id(),

                                    'motivo_rechazo' =>
                                        null,
                                ),

                                array(
                                    'id' =>
                                        $solicitud_id,

                                    'estado' =>
                                        'PENDIENTE',
                                ),

                                array(
                                    '%s',
                                    '%s',
                                    '%d',
                                    null,
                                ),

                                array(
                                    '%d',
                                    '%s',
                                )
                            );


                        if (
                            $actualizar_solicitud === false
                            ||
                            $actualizar_solicitud !== 1
                        ) {

                            $wpdb->query(
                                'ROLLBACK'
                            );

                            $mensaje =
                                'No fue posible actualizar el estado de la solicitud.';

                            $tipo_mensaje =
                                'error';

                        }
                        else {

                            /**
                             * Registrar historial.
                             */

                            $nombre_persona =
                                trim(
                                    $solicitud->nombres .
                                    ' ' .
                                    $solicitud->apellido_paterno .
                                    ' ' .
                                    $solicitud->apellido_materno
                                );


                            $descripcion =
                                sprintf(
                                    'Solicitud #%d aprobada. Persona: %s (%s). Empresa: %s (%s). Faena/Obra: %s. Afiliación #%d creada.',
                                    $solicitud_id,
                                    $nombre_persona,
                                    sinacin_formatear_rut(
                                        $solicitud->rut
                                    ),
                                    $solicitud->nombre_empresa,
                                    sinacin_formatear_rut(
                                        $solicitud->rut_empresa
                                    ),
                                    $solicitud->nombre_faena,
                                    $afiliacion_id
                                );


                            $insertar_historial =
                                $wpdb->insert(
                                    $tabla_historial,

                                    array(
                                        'usuario_id' =>
                                            get_current_user_id(),

                                        'entidad' =>
                                            'SOLICITUD',

                                        'entidad_id' =>
                                            $solicitud_id,

                                        'accion' =>
                                            'APROBAR',

                                        'descripcion' =>
                                            $descripcion,

                                        'ip' =>
                                            isset(
                                                $_SERVER['REMOTE_ADDR']
                                            )
                                                ? sanitize_text_field(
                                                    wp_unslash(
                                                        $_SERVER['REMOTE_ADDR']
                                                    )
                                                )
                                                : null,

                                        'fecha' =>
                                            $ahora,
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


                            if (
                                $insertar_historial === false
                            ) {

                                $wpdb->query(
                                    'ROLLBACK'
                                );

                                $mensaje =
                                    'La afiliación fue preparada, pero no fue posible registrar el historial. No se aplicaron los cambios.';

                                $tipo_mensaje =
                                    'error';

                            }
                            else {

    /**
     * ==========================================
     * CONFIRMAR TRANSACCIÓN
     * ==========================================
     *
     * Desde este punto la afiliación ya está
     * aprobada definitivamente.
     *
     * Si posteriormente falla el certificado
     * o el correo, NO se hará rollback.
     */

    $wpdb->query(
        'COMMIT'
    );


    /**
     * ==========================================
     * GENERAR CERTIFICADO
     * ==========================================
     */

    $resultado_certificado =
        sinacin_generar_certificado(
            $afiliacion_id
        );


    /*
     * ==========================================
     * CERTIFICADO GENERADO CORRECTAMENTE
     * ==========================================
     */

    if (
        is_array( $resultado_certificado ) &&
        ! empty( $resultado_certificado['success'] )
    ) {

        $numero_certificado =
            isset(
                $resultado_certificado['numero_certificado']
            )
                ? $resultado_certificado['numero_certificado']
                : '';


        $archivo_id =
            isset(
                $resultado_certificado['archivo_id']
            )
                ? absint(
                    $resultado_certificado['archivo_id']
                )
                : 0;


        /**
         * ==========================================
         * OBTENER RUTA DEL PDF
         * ==========================================
         *
         * El certificado recién generado puede
         * entregar directamente la ruta.
         *
         * Si no la entrega, intentamos obtenerla
         * desde el attachment de WordPress.
         */

        $ruta_pdf = '';


        if (
            isset(
                $resultado_certificado['ruta_archivo']
            ) &&
            ! empty(
                $resultado_certificado['ruta_archivo']
            )
        ) {

            $ruta_pdf =
                $resultado_certificado['ruta_archivo'];

        }
        elseif (
            $archivo_id > 0
        ) {

            $ruta_pdf =
                get_attached_file(
                    $archivo_id
                );

        }


        /**
         * ==========================================
         * ENVIAR CERTIFICADO POR CORREO
         * ==========================================
         */

        $resultado_correo =
            sinacin_enviar_certificado_por_correo(
                $solicitud->correo,
                $nombre_persona,
                $ruta_pdf,
                $numero_certificado
            );


        /**
         * ==========================================
         * RESULTADO FINAL
         * ==========================================
         */

        if (
            is_array( $resultado_correo ) &&
            ! empty( $resultado_correo['success'] )
        ) {

            $mensaje =
                sprintf(
                    'Solicitud aprobada correctamente. Afiliación #%d creada, certificado %s generado y enviado al correo %s.',
                    $afiliacion_id,
                    $numero_certificado,
                    $solicitud->correo
                );

            $tipo_mensaje =
                'success';

        }
        else {

            /*
             * La afiliación YA está aprobada.
             *
             * El error de correo NO revierte
             * la afiliación.
             */

            $mensaje =
                sprintf(
                    'Solicitud aprobada correctamente. Afiliación #%d creada y certificado %s generado, pero no fue posible enviar el certificado por correo. Puedes descargarlo manualmente desde Afiliados.',
                    $afiliacion_id,
                    $numero_certificado
                );

            $tipo_mensaje =
                'warning';


            error_log(
                'SINACIN: afiliación #' .
                $afiliacion_id .
                ' aprobada, pero no fue posible enviar el certificado por correo.'
            );

        }

    }
    else {

        /*
         * La afiliación YA está aprobada.
         *
         * El error de generación del certificado
         * NO revierte la afiliación.
         */

        $mensaje =
            sprintf(
                'Solicitud aprobada correctamente y afiliación #%d creada, pero no fue posible generar el certificado. Revisa el sistema y genera/descarga el certificado manualmente.',
                $afiliacion_id
            );

        $tipo_mensaje =
            'warning';


        error_log(
            'SINACIN: afiliación #' .
            $afiliacion_id .
            ' aprobada, pero falló la generación del certificado.'
        );

    }

}

                        }

                    }

                }

            }

        }

    }


    /**
     * ==========================================
     * PROCESAR RECHAZO
     * ==========================================
     */

    if (
        isset(
            $_POST['sinacin_rechazar_solicitud']
        )
    ) {

        $solicitud_id =
            isset(
                $_POST['solicitud_id']
            )
                ? absint(
                    $_POST['solicitud_id']
                )
                : 0;


        $motivo =
            isset(
                $_POST['motivo_rechazo']
            )
                ? sanitize_textarea_field(
                    wp_unslash(
                        $_POST['motivo_rechazo']
                    )
                )
                : '';


        if (
            ! isset(
                $_POST['sinacin_solicitud_nonce']
            )
            ||
            ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_POST['sinacin_solicitud_nonce']
                    )
                ),
                'sinacin_rechazar_solicitud_' .
                $solicitud_id
            )
        ) {

            $mensaje =
                'La sesión de seguridad no es válida.';

            $tipo_mensaje =
                'error';

        }
        elseif (
            $solicitud_id <= 0
        ) {

            $mensaje =
                'Solicitud no válida.';

            $tipo_mensaje =
                'error';

        }
        elseif (
            trim(
                $motivo
            ) === ''
        ) {

            $mensaje =
                'Debes indicar un motivo de rechazo.';

            $tipo_mensaje =
                'error';

        }
        else {

            $solicitud =
                $wpdb->get_row(
                    $wpdb->prepare(
                        "
                        SELECT
                            s.*,
                            p.nombres,
                            p.apellido_paterno,
                            p.apellido_materno,
                            p.rut,
                            e.rut_empresa,
                            e.nombre_empresa,
                            f.nombre_faena
                        FROM {$tabla_solicitudes} s

                        INNER JOIN {$tabla_personas} p
                            ON p.id = s.persona_id

                        INNER JOIN {$tabla_empresas} e
                            ON e.id = s.empresa_id

                        INNER JOIN {$tabla_faenas} f
                            ON f.id = s.faena_id
                            AND f.empresa_id = s.empresa_id

                        WHERE s.id = %d

                        LIMIT 1
                        ",
                        $solicitud_id
                    )
                );


            if (
                ! $solicitud
            ) {

                $mensaje =
                    'La solicitud no existe.';

                $tipo_mensaje =
                    'error';

            }
            elseif (
                $solicitud->estado !==
                'PENDIENTE'
            ) {

                $mensaje =
                    'La solicitud ya fue procesada anteriormente.';

                $tipo_mensaje =
                    'error';

            }
            else {

                $ahora =
                    current_time(
                        'mysql'
                    );


                $resultado =
                    $wpdb->update(
                        $tabla_solicitudes,

                        array(
                            'estado' =>
                                'RECHAZADA',

                            'fecha_revision' =>
                                $ahora,

                            'usuario_revisor' =>
                                get_current_user_id(),

                            'motivo_rechazo' =>
                                $motivo,
                        ),

                        array(
                            'id' =>
                                $solicitud_id,

                            'estado' =>
                                'PENDIENTE',
                        ),

                        array(
                            '%s',
                            '%s',
                            '%d',
                            '%s',
                        ),

                        array(
                            '%d',
                            '%s',
                        )
                    );


                if (
                    $resultado !== 1
                ) {

                    $mensaje =
                        'No fue posible rechazar la solicitud.';

                    $tipo_mensaje =
                        'error';

                }
                else {

                    $nombre_persona =
                        trim(
                            $solicitud->nombres .
                            ' ' .
                            $solicitud->apellido_paterno .
                            ' ' .
                            $solicitud->apellido_materno
                        );


                    $descripcion =
                        sprintf(
                            'Solicitud #%d rechazada. Persona: %s (%s). Empresa: %s (%s). Faena/Obra: %s. Motivo: %s',
                            $solicitud_id,
                            $nombre_persona,
                            sinacin_formatear_rut(
                                $solicitud->rut
                            ),
                            $solicitud->nombre_empresa,
                            sinacin_formatear_rut(
                                $solicitud->rut_empresa
                            ),
                            $solicitud->nombre_faena,
                            $motivo
                        );


                    $wpdb->insert(
                        $tabla_historial,

                        array(
                            'usuario_id' =>
                                get_current_user_id(),

                            'entidad' =>
                                'SOLICITUD',

                            'entidad_id' =>
                                $solicitud_id,

                            'accion' =>
                                'RECHAZAR',

                            'descripcion' =>
                                $descripcion,

                            'ip' =>
                                isset(
                                    $_SERVER['REMOTE_ADDR']
                                )
                                    ? sanitize_text_field(
                                        wp_unslash(
                                            $_SERVER['REMOTE_ADDR']
                                        )
                                    )
                                    : null,

                            'fecha' =>
                                $ahora,
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


                    $mensaje =
                        'Solicitud rechazada correctamente.';

                    $tipo_mensaje =
                        'success';

                }

            }

        }

    }


    /**
 * ==========================================
 * VISTA DETALLE
 * ==========================================
 */

$detalle_id =
    isset(
        $_GET['detalle']
    )
        ? absint(
            $_GET['detalle']
        )
        : 0;


if (
    $detalle_id > 0
) {

    /**
     * Mostrar mensaje del procesamiento
     * antes de cargar el detalle.
     */
    if (
        $mensaje !== ''
    ) {

        ?>
        <div class="wrap">

            <div
                class="notice notice-<?php echo esc_attr( $tipo_mensaje ); ?> is-dismissible"
            >
                <p>
                    <?php
                    echo esc_html(
                        $mensaje
                    );
                    ?>
                </p>
            </div>

        </div>
        <?php

    }


    sinacin_mostrar_detalle_solicitud(
        $detalle_id
    );

    return;

}


    /**
     * ==========================================
     * FILTROS
     * ==========================================
     */

    $buscar =
        isset(
            $_GET['buscar']
        )
            ? sanitize_text_field(
                wp_unslash(
                    $_GET['buscar']
                )
            )
            : '';


    $estado =
        isset(
            $_GET['estado']
        )
            ? sanitize_text_field(
                wp_unslash(
                    $_GET['estado']
                )
            )
            : 'PENDIENTE';


    $estados_permitidos =
        array(
            'PENDIENTE',
            'APROBADA',
            'RECHAZADA',
            'TODAS',
        );


    if (
        ! in_array(
            $estado,
            $estados_permitidos,
            true
        )
    ) {

        $estado =
            'PENDIENTE';

    }


    /**
     * ==========================================
     * PAGINACIÓN
     * ==========================================
     */

    $por_pagina =
        20;


    $pagina_actual =
        isset(
            $_GET['pagina']
        )
            ? max(
                1,
                absint(
                    $_GET['pagina']
                )
            )
            : 1;


    $offset =
        (
            $pagina_actual - 1
        ) *
        $por_pagina;


    /**
     * ==========================================
     * WHERE
     * ==========================================
     */

    $where =
        'WHERE 1=1';


    $parametros =
        array();


    if (
        $estado !== 'TODAS'
    ) {

        $where .=
            ' AND s.estado = %s';

        $parametros[] =
            $estado;

    }


    if (
        $buscar !== ''
    ) {

        $like =
            '%' .
            $wpdb->esc_like(
                $buscar
            ) .
            '%';


        $where .=
            "
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


        $parametros[] =
            $like;

        $parametros[] =
            $like;

        $parametros[] =
            $like;

        $parametros[] =
            $like;

        $parametros[] =
            $like;

    }


    /**
     * ==========================================
     * CONTAR
     * ==========================================
     */

    $sql_count =
        "
        SELECT COUNT(*)

        FROM {$tabla_solicitudes} s

        INNER JOIN {$tabla_personas} p
            ON p.id = s.persona_id

        INNER JOIN {$tabla_empresas} e
            ON e.id = s.empresa_id

        INNER JOIN {$tabla_faenas} f
            ON f.id = s.faena_id
            AND f.empresa_id = s.empresa_id

        {$where}
        ";


    if (
        ! empty( $parametros )
    ) {

        $total =
            $wpdb->get_var(
                $wpdb->prepare(
                    $sql_count,
                    ...$parametros
                )
            );

    }
    else {

        $total =
            $wpdb->get_var(
                $sql_count
            );

    }


    /**
     * ==========================================
     * OBTENER SOLICITUDES
     * ==========================================
     */

    $sql =
        "
        SELECT

            s.*,

            p.nombres,
            p.apellido_paterno,
            p.apellido_materno,
            p.rut,
            p.celular,
            p.correo,
            p.cargo,

            e.rut_empresa,
            e.nombre_empresa,
            e.estado AS estado_empresa,
            f.nombre_faena,
            f.estado AS estado_faena

        FROM {$tabla_solicitudes} s

        INNER JOIN {$tabla_personas} p
            ON p.id = s.persona_id

        INNER JOIN {$tabla_empresas} e
            ON e.id = s.empresa_id

        INNER JOIN {$tabla_faenas} f
            ON f.id = s.faena_id
            AND f.empresa_id = s.empresa_id

        {$where}

        ORDER BY s.fecha_solicitud DESC

        LIMIT %d OFFSET %d
        ";


    $parametros_lista =
        $parametros;

    $parametros_lista[] =
        $por_pagina;

    $parametros_lista[] =
        $offset;


    $solicitudes =
        $wpdb->get_results(
            $wpdb->prepare(
                $sql,
                ...$parametros_lista
            )
        );


    $total_paginas =
        max(
            1,
            ceil(
                $total /
                $por_pagina
            )
        );


    /**
     * ==========================================
     * INTERFAZ
     * ==========================================
     */

    ?>

    <div class="wrap">

        <h1 class="wp-heading-inline">
            Solicitudes de afiliación
        </h1>


        <hr class="wp-header-end">


        <?php if (
            $mensaje !== ''
        ) : ?>

            <div
                class="notice notice-<?php echo esc_attr( $tipo_mensaje ); ?> is-dismissible"
            >

                <p>
                    <?php
                    echo esc_html(
                        $mensaje
                    );
                    ?>
                </p>

            </div>

        <?php endif; ?>


        <!-- ======================================
             FILTROS
             ====================================== -->

        <form
            method="get"
            style="
                margin:20px 0;
                display:flex;
                gap:8px;
                align-items:center;
                flex-wrap:wrap;
            "
        >

            <input
                type="hidden"
                name="page"
                value="sinacin-solicitudes"
            >


            <input
                type="search"
                name="buscar"
                value="<?php
                    echo esc_attr(
                        $buscar
                    );
                ?>"
                placeholder="Nombre, RUT, empresa o faena..."
                style="min-width:300px;"
            >


            <select
                name="estado"
            >

                <option
                    value="PENDIENTE"
                    <?php selected(
                        $estado,
                        'PENDIENTE'
                    ); ?>
                >
                    Pendientes
                </option>

                <option
                    value="APROBADA"
                    <?php selected(
                        $estado,
                        'APROBADA'
                    ); ?>
                >
                    Aprobadas
                </option>

                <option
                    value="RECHAZADA"
                    <?php selected(
                        $estado,
                        'RECHAZADA'
                    ); ?>
                >
                    Rechazadas
                </option>

                <option
                    value="TODAS"
                    <?php selected(
                        $estado,
                        'TODAS'
                    ); ?>
                >
                    Todas
                </option>

            </select>


            <button
                type="submit"
                class="button"
            >
                Filtrar
            </button>


            <?php if (
                $buscar !== ''
                ||
                $estado !== 'PENDIENTE'
            ) : ?>

                <a
                    href="<?php
                        echo esc_url(
                            admin_url(
                                'admin.php?page=sinacin-solicitudes'
                            )
                        );
                    ?>"
                    class="button"
                >
                    Limpiar
                </a>

            <?php endif; ?>

        </form>


        <!-- ======================================
             TABLA
             ====================================== -->

        <table
            class="wp-list-table widefat fixed striped"
        >

            <thead>

                <tr>

                    <th style="width:60px;">
                        ID
                    </th>

                    <th>
                        Persona
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
                        Correo
                    </th>

                    <th>
                        Estado
                    </th>

                    <th>
                        Fecha solicitud
                    </th>

                    <th style="width:100px;">
                        Acción
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if (
                    empty(
                        $solicitudes
                    )
                ) : ?>

                    <tr>

                        <td colspan="9">

                            No existen solicitudes
                            para los filtros seleccionados.

                        </td>

                    </tr>

                <?php else : ?>


                    <?php foreach (
                        $solicitudes
                        as $solicitud
                    ) : ?>

                        <?php

                        $nombre_completo =
                            trim(
                                $solicitud->nombres .
                                ' ' .
                                $solicitud->apellido_paterno .
                                ' ' .
                                $solicitud->apellido_materno
                            );

                        ?>


                        <tr>

                            <td>

                                <?php
                                echo esc_html(
                                    $solicitud->id
                                );
                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?php
                                    echo esc_html(
                                        $nombre_completo
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php
                                echo esc_html(
                                    sinacin_formatear_rut(
                                        $solicitud->rut
                                    )
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo esc_html(
                                    $solicitud->nombre_empresa
                                );
                                ?>

                                <br>

                                <small>

                                    <?php
                                    echo esc_html(
                                        sinacin_formatear_rut(
                                            $solicitud->rut_empresa
                                        )
                                    );
                                    ?>

                                </small>

                            </td>


                            <td>

                                <?php
                                echo esc_html(
                                    $solicitud->nombre_faena
                                );
                                ?>

                                <?php if (
                                    $solicitud->estado_faena !== 'ACTIVA'
                                ) : ?>

                                    <br>
                                    <small>Faena inactiva</small>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php
                                echo esc_html(
                                    $solicitud->correo
                                );
                                ?>

                            </td>


                            <td>

                                <?php

                                $clase_estado =
                                    'sinacin-estado-' .
                                    strtolower(
                                        $solicitud->estado
                                    );

                                ?>


                                <span
                                    class="<?php
                                        echo esc_attr(
                                            $clase_estado
                                        );
                                    ?>"
                                >

                                    <?php
                                    echo esc_html(
                                        $solicitud->estado
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php
                                echo esc_html(
                                    $solicitud->fecha_solicitud
                                );
                                ?>

                            </td>


                            <td>

                                <a
                                    href="<?php

                                        echo esc_url(
                                            add_query_arg(
                                                array(
                                                    'page' =>
                                                        'sinacin-solicitudes',

                                                    'detalle' =>
                                                        $solicitud->id,
                                                ),
                                                admin_url(
                                                    'admin.php'
                                                )
                                            )
                                        );

                                    ?>"
                                    class="button button-small"
                                >

                                    Ver detalle

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>


        <!-- ======================================
             PAGINACIÓN
             ====================================== -->

        <?php if (
            $total_paginas > 1
        ) : ?>

            <div
                style="margin-top:20px;"
            >

                <?php

                echo paginate_links(
                    array(
                        'base' =>
                            add_query_arg(
                                array(
                                    'page' =>
                                        'sinacin-solicitudes',

                                    'buscar' =>
                                        $buscar,

                                    'estado' =>
                                        $estado,

                                    'pagina' =>
                                        '%#%',
                                ),
                                admin_url(
                                    'admin.php'
                                )
                            ),

                        'format' => '',

                        'current' =>
                            $pagina_actual,

                        'total' =>
                            $total_paginas,

                        'prev_text' =>
                            '« Anterior',

                        'next_text' =>
                            'Siguiente »',
                    )
                );

                ?>

            </div>

        <?php endif; ?>


    </div>


    <style>

        .sinacin-estado-pendiente {
            display:inline-block;
            background:#fff3cd;
            color:#664d03;
            padding:4px 8px;
            border-radius:4px;
            font-weight:600;
        }

        .sinacin-estado-aprobada {
            display:inline-block;
            background:#d1e7dd;
            color:#0f5132;
            padding:4px 8px;
            border-radius:4px;
            font-weight:600;
        }

        .sinacin-estado-rechazada {
            display:inline-block;
            background:#f8d7da;
            color:#842029;
            padding:4px 8px;
            border-radius:4px;
            font-weight:600;
        }

        .sinacin-detalle-box {
            background:#fff;
            border:1px solid #dcdcde;
            padding:20px;
            margin:20px 0;
        }

        .sinacin-detalle-box h2 {
            margin-top:0;
        }

        .sinacin-detalle-tabla {
            width:100%;
            border-collapse:collapse;
        }

        .sinacin-detalle-tabla th,
        .sinacin-detalle-tabla td {
            border-bottom:1px solid #eee;
            padding:10px;
            text-align:left;
            vertical-align:top;
        }

        .sinacin-detalle-tabla th {
            width:220px;
            background:#f6f7f7;
        }

        .sinacin-acciones-solicitud {
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            align-items:flex-start;
        }

        .sinacin-rechazo {
            margin-top:15px;
            padding:15px;
            background:#f6f7f7;
            border:1px solid #dcdcde;
        }

    </style>

    <?php
}


/**
 * ==========================================
 * DETALLE DE SOLICITUD
 * ==========================================
 */

function sinacin_mostrar_detalle_solicitud(
    $solicitud_id
) {

    global $wpdb;


    $tabla_solicitudes =
        $wpdb->prefix .
        'sinacin_solicitudes';

    $tabla_personas =
        $wpdb->prefix .
        'sinacin_personas';

    $tabla_empresas =
        $wpdb->prefix .
        'sinacin_empresas';

    $tabla_faenas =
        $wpdb->prefix .
        'sinacin_faenas';

    $tabla_documentos =
        $wpdb->prefix .
        'sinacin_documentos';


    /**
     * Obtener solicitud.
     */

    $solicitud =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT

                    s.*,

                    p.nombres,
                    p.apellido_paterno,
                    p.apellido_materno,
                    p.rut,
                    p.celular,
                    p.correo,
                    p.cargo,

                    e.rut_empresa,
                    e.nombre_empresa,
                    e.estado AS estado_empresa,
                    f.nombre_faena,
                    f.estado AS estado_faena

                FROM {$tabla_solicitudes} s

                INNER JOIN {$tabla_personas} p
                    ON p.id = s.persona_id

                INNER JOIN {$tabla_empresas} e
                    ON e.id = s.empresa_id

                INNER JOIN {$tabla_faenas} f
                    ON f.id = s.faena_id
                    AND f.empresa_id = s.empresa_id

                WHERE s.id = %d

                LIMIT 1
                ",
                $solicitud_id
            )
        );


    if (
        ! $solicitud
    ) {

        echo '<div class="wrap">';

        echo '<div class="notice notice-error">';

        echo '<p>';

        echo 'La solicitud no existe.';

        echo '</p>';

        echo '</div>';

        echo '</div>';

        return;

    }


    /**
     * Obtener documento.
     */

    $documento =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$tabla_documentos}
                WHERE persona_id = %d
                AND estado = 'VIGENTE'
                ORDER BY id DESC
                LIMIT 1
                ",
                $solicitud->persona_id
            )
        );


    $nombre_completo =
        trim(
            $solicitud->nombres .
            ' ' .
            $solicitud->apellido_paterno .
            ' ' .
            $solicitud->apellido_materno
        );


    ?>

    <div class="wrap">

        <h1>

            Solicitud #<?php
            echo esc_html(
                $solicitud->id
            );
            ?>

        </h1>


        <p>

            <a
                href="<?php
                    echo esc_url(
                        admin_url(
                            'admin.php?page=sinacin-solicitudes'
                        )
                    );
                ?>"
                class="button"
            >

                « Volver a solicitudes

            </a>

        </p>


        <!-- ======================================
             ESTADO
             ====================================== -->

        <div class="sinacin-detalle-box">

            <h2>
                Estado de la solicitud
            </h2>


            <p>

                <strong>
                    Estado:
                </strong>


                <span
                    class="sinacin-estado-<?php
                        echo esc_attr(
                            strtolower(
                                $solicitud->estado
                            )
                        );
                    ?>"
                >

                    <?php
                    echo esc_html(
                        $solicitud->estado
                    );
                    ?>

                </span>

            </p>


            <?php if (
                $solicitud->fecha_solicitud
            ) : ?>

                <p>

                    <strong>
                        Fecha solicitud:
                    </strong>

                    <?php
                    echo esc_html(
                        $solicitud->fecha_solicitud
                    );
                    ?>

                </p>

            <?php endif; ?>


            <?php if (
                $solicitud->fecha_revision
            ) : ?>

                <p>

                    <strong>
                        Fecha revisión:
                    </strong>

                    <?php
                    echo esc_html(
                        $solicitud->fecha_revision
                    );
                    ?>

                </p>

            <?php endif; ?>


            <?php if (
                $solicitud->motivo_rechazo
            ) : ?>

                <p>

                    <strong>
                        Motivo de rechazo:
                    </strong>

                    <br>

                    <?php
                    echo nl2br(
                        esc_html(
                            $solicitud->motivo_rechazo
                        )
                    );
                    ?>

                </p>

            <?php endif; ?>

        </div>


        <!-- ======================================
             DATOS PERSONA
             ====================================== -->

        <div class="sinacin-detalle-box">

            <h2>
                Datos del afiliado
            </h2>


            <table class="sinacin-detalle-tabla">

                <tr>

                    <th>
                        Nombre completo
                    </th>

                    <td>
                        <?php
                        echo esc_html(
                            $nombre_completo
                        );
                        ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        RUT
                    </th>

                    <td>
                        <?php
                        echo esc_html(
                            sinacin_formatear_rut(
                                $solicitud->rut
                            )
                        );
                        ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        Celular
                    </th>

                    <td>
                        <?php
                        echo esc_html(
                            $solicitud->celular
                        );
                        ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        Correo electrónico
                    </th>

                    <td>
                        <?php
                        echo esc_html(
                            $solicitud->correo
                        );
                        ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        Cargo
                    </th>

                    <td>
                        <?php
                        echo esc_html(
                            $solicitud->cargo
                        );
                        ?>
                    </td>

                </tr>

            </table>

        </div>


        <!-- ======================================
             EMPRESA
             ====================================== -->

        <div class="sinacin-detalle-box">

            <h2>
                Empresa
            </h2>


            <table class="sinacin-detalle-tabla">

                <tr>

                    <th>
                        Empresa
                    </th>

                    <td>
                        <?php
                        echo esc_html(
                            $solicitud->nombre_empresa
                        );
                        ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        RUT empresa
                    </th>

                    <td>
                        <?php
                        echo esc_html(
                            sinacin_formatear_rut(
                                $solicitud->rut_empresa
                            )
                        );
                        ?>
                    </td>

                </tr>


                <tr>

                    <th>
                        Estado empresa
                    </th>

                    <td>

                        <?php
                        echo esc_html(
                            $solicitud->estado_empresa
                        );
                        ?>

                    </td>

                </tr>

            </table>

        </div>


        <!-- ======================================
             FAENA / OBRA
             ====================================== -->

        <div class="sinacin-detalle-box">

            <h2>
                Faena / Obra
            </h2>

            <table class="sinacin-detalle-tabla">

                <tr>
                    <th>
                        Faena / Obra
                    </th>
                    <td>
                        <?php
                        echo esc_html(
                            $solicitud->nombre_faena
                        );
                        ?>
                    </td>
                </tr>

                <tr>
                    <th>
                        Estado de la faena
                    </th>
                    <td>
                        <?php
                        echo esc_html(
                            $solicitud->estado_faena
                        );
                        ?>
                    </td>
                </tr>

            </table>

        </div>


        <!-- ======================================
             TÉRMINOS
             ====================================== -->

        <div class="sinacin-detalle-box">

            <h2>
                Aceptación de términos
            </h2>


            <p>

                <strong>
                    Acepta términos:
                </strong>

                <?php

                echo (
                    (int)
                    $solicitud->acepta_terminos === 1
                )
                    ? 'Sí'
                    : 'No';

                ?>

            </p>


            <p>

                <strong>
                    Versión:
                </strong>

                <?php
                echo esc_html(
                    $solicitud->version_terminos
                );
                ?>

            </p>


            <p>

                <strong>
                    Fecha de aceptación:
                </strong>

                <?php
                echo esc_html(
                    $solicitud->fecha_aceptacion
                );
                ?>

            </p>

        </div>


        <!-- ======================================
             DOCUMENTO
             ====================================== -->

        <div class="sinacin-detalle-box">

            <h2>
                Documento de identidad
            </h2>


            <?php if (
                $documento
            ) : ?>

                <p>

                    <strong>
                        Archivo:
                    </strong>

                    <?php
                    echo esc_html(
                        $documento->nombre_archivo
                    );
                    ?>

                </p>


                <?php

                $archivo_url =
                    wp_get_attachment_url(
                        $documento->archivo_id
                    );

                ?>


                <?php if (
                    $archivo_url
                ) : ?>

                    <p>

                        <a
                            href="<?php
                                echo esc_url(
                                    $archivo_url
                                );
                            ?>"
                            target="_blank"
                            class="button"
                        >

                            Ver documento

                        </a>

                    </p>

                <?php else : ?>

                    <p>
                        No fue posible encontrar el archivo.
                    </p>

                <?php endif; ?>


            <?php else : ?>

                <p>
                    No se encontró un documento vigente.
                </p>

            <?php endif; ?>

        </div>


        <!-- ======================================
             ACCIONES
             ====================================== -->

        <?php if (
            $solicitud->estado ===
            'PENDIENTE'
        ) : ?>

            <div class="sinacin-detalle-box">

                <h2>
                    Revisar solicitud
                </h2>


                <div class="sinacin-acciones-solicitud">


                    <!-- APROBAR -->

                    <form
                        method="post"
                        onsubmit="
                            return confirm(
                                '¿Confirmas que deseas aprobar esta solicitud y crear la afiliación activa?'
                            );
                        "
                    >

                        <?php

                        wp_nonce_field(
                            'sinacin_aprobar_solicitud_' .
                            $solicitud->id,
                            'sinacin_solicitud_nonce'
                        );

                        ?>


                        <input
                            type="hidden"
                            name="solicitud_id"
                            value="<?php
                                echo esc_attr(
                                    $solicitud->id
                                );
                            ?>"
                        >


                        <button
                            type="submit"
                            name="sinacin_aprobar_solicitud"
                            class="button button-primary"
                        >

                            Aprobar solicitud

                        </button>

                    </form>


                </div>


                <!-- RECHAZAR -->

                <div class="sinacin-rechazo">

                    <h3>
                        Rechazar solicitud
                    </h3>


                    <form
                        method="post"
                        onsubmit="
                            return confirm(
                                '¿Confirmas que deseas rechazar esta solicitud?'
                            );
                        "
                    >

                        <?php

                        wp_nonce_field(
                            'sinacin_rechazar_solicitud_' .
                            $solicitud->id,
                            'sinacin_solicitud_nonce'
                        );

                        ?>


                        <input
                            type="hidden"
                            name="solicitud_id"
                            value="<?php
                                echo esc_attr(
                                    $solicitud->id
                                );
                            ?>"
                        >


                        <p>

                            <label
                                for="motivo_rechazo"
                            >

                                <strong>
                                    Motivo del rechazo
                                </strong>

                            </label>

                        </p>


                        <textarea
                            id="motivo_rechazo"
                            name="motivo_rechazo"
                            rows="5"
                            style="
                                width:100%;
                                max-width:700px;
                            "
                            required
                            placeholder="Indica el motivo por el cual la solicitud es rechazada..."
                        ></textarea>


                        <p>

                            <button
                                type="submit"
                                name="sinacin_rechazar_solicitud"
                                class="button"
                            >

                                Rechazar solicitud

                            </button>

                        </p>

                    </form>

                </div>

            </div>

        <?php endif; ?>


        </div>


    <!-- ==========================================
         ESTILOS DEL DETALLE
         ========================================== -->

    <style>

        /* ==========================================
           CONTENEDOR GENERAL
           ========================================== */

        .sinacin-detalle-box {
            background: #ffffff;
            border: 1px solid #dcdcde;
            border-radius: 6px;
            padding: 24px;
            margin: 20px 0;
            max-width: 1000px;
            box-sizing: border-box;
        }


        /* ==========================================
           TÍTULOS
           ========================================== */

        .sinacin-detalle-box h2 {
            margin: 0 0 20px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #eeeeee;
            font-size: 20px;
            line-height: 1.4;
        }


        .sinacin-detalle-box h3 {
            margin-top: 0;
        }


        /* ==========================================
           TABLA DE DATOS
           ========================================== */

        .sinacin-detalle-tabla {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed;
        }


        .sinacin-detalle-tabla th,
        .sinacin-detalle-tabla td {
            border-bottom: 1px solid #eeeeee !important;
            padding: 13px 12px !important;
            text-align: left !important;
            vertical-align: middle !important;
            box-sizing: border-box;
        }


        .sinacin-detalle-tabla tr:last-child th,
        .sinacin-detalle-tabla tr:last-child td {
            border-bottom: none !important;
        }


        .sinacin-detalle-tabla th {
            width: 30% !important;
            background: #f6f7f7 !important;
            font-weight: 600 !important;
            color: #1d2327 !important;
        }


        .sinacin-detalle-tabla td {
            width: 70% !important;
            color: #3c434a !important;
            word-break: break-word;
            overflow-wrap: anywhere;
        }


        /* ==========================================
           ESTADOS
           ========================================== */

        .sinacin-estado-pendiente {
            display: inline-block;
            background: #fff3cd;
            color: #664d03;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 12px;
        }


        .sinacin-estado-aprobada {
            display: inline-block;
            background: #d1e7dd;
            color: #0f5132;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 12px;
        }


        .sinacin-estado-rechazada {
            display: inline-block;
            background: #f8d7da;
            color: #842029;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 12px;
        }


        /* ==========================================
           TEXTO
           ========================================== */

        .sinacin-detalle-box p {
            line-height: 1.6;
        }


        .sinacin-detalle-box strong {
            color: #1d2327;
        }


        /* ==========================================
           ACCIONES
           ========================================== */

        .sinacin-acciones-solicitud {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }


        /* ==========================================
           RECHAZO
           ========================================== */

        .sinacin-rechazo {
            margin-top: 25px;
            padding: 20px;
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            border-radius: 6px;
        }


        .sinacin-rechazo h3 {
            margin-bottom: 15px;
        }


        .sinacin-rechazo textarea {
            width: 100%;
            max-width: 700px;
            min-height: 120px;
            resize: vertical;
            box-sizing: border-box;
        }


        /* ==========================================
           BOTONES
           ========================================== */

        .sinacin-detalle-box .button {
            min-height: 36px;
            padding: 6px 14px;
        }


        /* ==========================================
           RESPONSIVE
           ========================================== */

        @media screen and (max-width: 782px) {

            .sinacin-detalle-box {
                padding: 18px;
            }

            .sinacin-detalle-tabla th {
                width: 35% !important;
            }

            .sinacin-detalle-tabla td {
                width: 65% !important;
            }

        }


        @media screen and (max-width: 600px) {

            .sinacin-detalle-box {
                padding: 16px;
                margin: 15px 0;
            }


            .sinacin-detalle-tabla,
            .sinacin-detalle-tabla tbody,
            .sinacin-detalle-tabla tr,
            .sinacin-detalle-tabla th,
            .sinacin-detalle-tabla td {
                display: block !important;
                width: 100% !important;
            }


            .sinacin-detalle-tabla tr {
                margin-bottom: 15px;
                border-bottom: 1px solid #eeeeee;
                padding-bottom: 10px;
            }


            .sinacin-detalle-tabla th {
                background: transparent !important;
                border: none !important;
                padding: 4px 0 !important;
                font-size: 12px;
                text-transform: uppercase;
                color: #646970 !important;
            }


            .sinacin-detalle-tabla td {
                border: none !important;
                padding: 3px 0 8px 0 !important;
                font-size: 14px;
                color: #1d2327 !important;
            }


            .sinacin-acciones-solicitud {
                flex-direction: column;
                align-items: stretch;
            }


            .sinacin-acciones-solicitud form {
                width: 100%;
            }


            .sinacin-acciones-solicitud .button {
                width: 100%;
                text-align: center;
            }


            .sinacin-rechazo {
                padding: 15px;
            }


            .sinacin-rechazo .button {
                width: 100%;
            }

        }

    </style>


    <?php
}
