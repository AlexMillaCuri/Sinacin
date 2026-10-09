<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * ==========================================
 * MENÚ ADMINISTRATIVO
 * ==========================================
 */

add_action(
    'admin_menu',
    'sinacin_registrar_menu_empresas'
);


function sinacin_registrar_menu_empresas() {

    add_menu_page(
        'SINACIN',
        'SINACIN',
        'sinacin_gestionar_afiliaciones',
        'sinacin',
        'sinacin_pagina_inicio',
        'dashicons-building',
        25
    );

    // Empresas permanece disponible exclusivamente para administradores.
    add_submenu_page(
        'sinacin',
        'Empresas',
        'Empresas',
        'sinacin_gestionar_afiliaciones',
        'sinacin-empresas',
        'sinacin_pagina_empresas'
    );

}


/** Página inicial del menú SINACIN. */
function sinacin_pagina_inicio() {
    if ( current_user_can( 'manage_options' ) ) {
        sinacin_pagina_empresas();
        return;
    }
    if ( current_user_can( 'sinacin_gestionar_afiliaciones' ) ) {
        sinacin_pagina_solicitudes();
        return;
    }
    wp_die( 'No tienes permisos para acceder a SINACIN.' );
}


/**
 * ==========================================
 * NORMALIZAR RUT
 * ==========================================
 *
 * Ejemplo:
 *
 * 76.123.456-7
 * 76 123 456-7
 * 761234567
 *
 * Todos terminan como:
 *
 * 761234567
 *
 */

function sinacin_normalizar_rut( $rut ) {

    $rut = strtoupper(
        trim(
            $rut
        )
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

    return $rut;
}


/**
 * ==========================================
 * FORMATEAR RUT PARA MOSTRAR
 * ==========================================
 */

function sinacin_formatear_rut( $rut ) {

    $rut =
        sinacin_normalizar_rut(
            $rut
        );


    if (
        strlen( $rut ) < 2
    ) {

        return $rut;

    }


    $cuerpo =
        substr(
            $rut,
            0,
            -1
        );


    $dv =
        substr(
            $rut,
            -1
        );


    $cuerpo_formateado = '';

    $contador = 0;


    for (
        $i = strlen( $cuerpo ) - 1;
        $i >= 0;
        $i--
    ) {

        $cuerpo_formateado =
            $cuerpo[ $i ] .
            $cuerpo_formateado;


        $contador++;


        if (
            $contador === 3
            &&
            $i !== 0
        ) {

            $cuerpo_formateado =
                '.' .
                $cuerpo_formateado;

            $contador = 0;

        }

    }


    return (
        $cuerpo_formateado .
        '-' .
        $dv
    );

}


/**
 * ==========================================
 * VALIDAR RUT CHILENO
 * ==========================================
 */

function sinacin_validar_rut( $rut ) {

    $rut =
        sinacin_normalizar_rut(
            $rut
        );


    /**
     * Debe contener números
     * y un DV numérico o K.
     */

    if (
        ! preg_match(
            '/^[0-9]+[0-9K]$/',
            $rut
        )
    ) {

        return false;

    }


    $cuerpo =
        substr(
            $rut,
            0,
            -1
        );


    $dv =
        substr(
            $rut,
            -1
        );


    if (
        strlen( $cuerpo ) < 1
    ) {

        return false;

    }


    $suma = 0;

    $multiplicador = 2;


    for (
        $i = strlen( $cuerpo ) - 1;
        $i >= 0;
        $i--
    ) {

        $suma +=
            intval(
                $cuerpo[ $i ]
            ) *
            $multiplicador;


        $multiplicador++;


        if (
            $multiplicador > 7
        ) {

            $multiplicador = 2;

        }

    }


    $resto =
        $suma % 11;


    $resultado =
        11 - $resto;


    if (
        $resultado === 11
    ) {

        $dv_calculado = '0';

    }
    elseif (
        $resultado === 10
    ) {

        $dv_calculado = 'K';

    }
    else {

        $dv_calculado =
            (string) $resultado;

    }


    return (
        $dv ===
        $dv_calculado
    );

}


/**
 * ==========================================
 * NORMALIZAR RUT EXISTENTES
 * ==========================================
 *
 * Esto permite corregir registros antiguos
 * que hayan quedado guardados como:
 *
 * 76.123.456-7
 *
 * para convertirlos en:
 *
 * 761234567
 *
 */

function sinacin_normalizar_ruts_empresas_existentes() {

    global $wpdb;


    $tabla_empresas =
        $wpdb->prefix .
        'sinacin_empresas';

    $tabla_faenas =
        $wpdb->prefix .
        'sinacin_faenas';


    $empresas =
        $wpdb->get_results(
            "
            SELECT id, rut_empresa
            FROM {$tabla_empresas}
            "
        );


    if (
        empty( $empresas )
    ) {

        return;

    }


    foreach (
        $empresas as $empresa
    ) {

        $rut_normalizado =
            sinacin_normalizar_rut(
                $empresa->rut_empresa
            );


        if (
            $rut_normalizado !==
            $empresa->rut_empresa
        ) {

            $wpdb->update(
                $tabla_empresas,

                array(
                    'rut_empresa' =>
                        $rut_normalizado,

                    'fecha_actualizacion' =>
                        current_time(
                            'mysql'
                        ),
                ),

                array(
                    'id' =>
                        $empresa->id,
                ),

                array(
                    '%s',
                    '%s',
                ),

                array(
                    '%d',
                )
            );

        }

    }

}


/**
 * Reglas de integridad entre empresas, faenas y afiliaciones.
 * Se comprueban en el servidor para todas las rutas de edición.
 */
function sinacin_empresa_tiene_faenas_activas( $empresa_id ) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'sinacin_faenas';
    return (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla} WHERE empresa_id = %d AND estado = 'ACTIVA'",
        $empresa_id
    ) );
}

function sinacin_faena_tiene_afiliados_activos( $faena_id ) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'sinacin_afiliaciones';
    return (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla} WHERE faena_id = %d AND estado = 'ACTIVA'",
        $faena_id
    ) );
}

function sinacin_empresa_esta_activa( $empresa_id ) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'sinacin_empresas';
    return 'ACTIVA' === $wpdb->get_var( $wpdb->prepare(
        "SELECT estado FROM {$tabla} WHERE id = %d LIMIT 1", $empresa_id
    ) );
}

/**
 * ==========================================
 * PÁGINA EMPRESAS
 * ==========================================
 */

function sinacin_pagina_empresas() {

    if (
        ! current_user_can(
            'sinacin_gestionar_afiliaciones'
        )
    ) {

        wp_die(
            'No tienes permisos para acceder a esta sección.'
        );

    }


    /**
     * Normalizar registros existentes
     * antes de mostrar la información.
     */

    sinacin_normalizar_ruts_empresas_existentes();


    global $wpdb;


    $tabla_empresas =
        $wpdb->prefix .
        'sinacin_empresas';

    $tabla_faenas =
        $wpdb->prefix .
        'sinacin_faenas';


    /**
     * ==========================================
     * PROCESAR ACCIONES
     * ==========================================
     */

    $mensaje =
        '';


    $tipo_mensaje =
        '';


    /**
     * ------------------------------------------
     * GUARDAR / EDITAR EMPRESA
     * ------------------------------------------
     */

    if (
        isset(
            $_POST['sinacin_guardar_empresa']
        )
    ) {

        if (
            ! isset(
                $_POST['sinacin_empresa_nonce']
            )
            ||
            ! wp_verify_nonce(
                sanitize_text_field(
                    wp_unslash(
                        $_POST['sinacin_empresa_nonce']
                    )
                ),
                'sinacin_guardar_empresa'
            )
        ) {

            $mensaje =
                'La sesión de seguridad no es válida.';

            $tipo_mensaje =
                'error';

        }
        else {

            $id =
                isset(
                    $_POST['empresa_id']
                )
                    ? absint(
                        $_POST['empresa_id']
                    )
                    : 0;


            $rut_empresa =
                isset(
                    $_POST['rut_empresa']
                )
                    ? sanitize_text_field(
                        wp_unslash(
                            $_POST['rut_empresa']
                        )
                    )
                    : '';


            $nombre_empresa =
                isset(
                    $_POST['nombre_empresa']
                )
                    ? sanitize_text_field(
                        wp_unslash(
                            $_POST['nombre_empresa']
                        )
                    )
                    : '';


            $estado =
                isset(
                    $_POST['estado']
                )
                    ? sanitize_text_field(
                        wp_unslash(
                            $_POST['estado']
                        )
                    )
                    : 'ACTIVA';


            /**
             * --------------------------------------
             * NORMALIZAR
             * --------------------------------------
             */

            $rut_empresa =
                sinacin_normalizar_rut(
                    $rut_empresa
                );


            $nombre_empresa =
                trim(
                    $nombre_empresa
                );


            /**
             * --------------------------------------
             * VALIDAR ESTADO
             * --------------------------------------
             */

            if (
                ! in_array(
                    $estado,
                    array(
                        'ACTIVA',
                        'INACTIVA',
                    ),
                    true
                )
            ) {

                $estado =
                    'ACTIVA';

            }


            /**
             * --------------------------------------
             * VALIDAR RUT
             * --------------------------------------
             */

            if (
                ! sinacin_validar_rut(
                    $rut_empresa
                )
            ) {

                $mensaje =
                    'El RUT de empresa ingresado no es válido.';

                $tipo_mensaje =
                    'error';

            }
            elseif (
                $nombre_empresa === ''
            ) {

                $mensaje =
                    'El nombre de la empresa es obligatorio.';

                $tipo_mensaje =
                    'error';

            }
            else {

                /**
                 * ----------------------------------
                 * BUSCAR DUPLICADO
                 * ----------------------------------
                 */

                if (
                    $id > 0
                ) {

                    $empresa_existente =
                        $wpdb->get_var(
                            $wpdb->prepare(
                                "
                                SELECT id
                                FROM {$tabla_empresas}
                                WHERE rut_empresa = %s
                                AND id != %d
                                LIMIT 1
                                ",
                                $rut_empresa,
                                $id
                            )
                        );

                }
                else {

                    $empresa_existente =
                        $wpdb->get_var(
                            $wpdb->prepare(
                                "
                                SELECT id
                                FROM {$tabla_empresas}
                                WHERE rut_empresa = %s
                                LIMIT 1
                                ",
                                $rut_empresa
                            )
                        );

                }


                if ( $id > 0 && $estado === 'INACTIVA' && sinacin_empresa_tiene_faenas_activas( $id ) > 0 ) {
                    $mensaje = 'No se puede desactivar la empresa: primero debes desactivar todas sus faenas activas.';
                    $tipo_mensaje = 'error';
                }
                elseif (
                    $empresa_existente
                ) {

                    $mensaje =
                        'Ya existe una empresa registrada con ese RUT.';

                    $tipo_mensaje =
                        'error';

                }
                else {

                    /**
                     * ------------------------------
                     * ACTUALIZAR
                     * ------------------------------
                     */

                    if (
                        $id > 0
                    ) {

                        $resultado =
                            $wpdb->update(
                                $tabla_empresas,

                                array(
                                    'rut_empresa' =>
                                        $rut_empresa,

                                    'nombre_empresa' =>
                                        $nombre_empresa,

                                    'estado' =>
                                        $estado,

                                    'fecha_actualizacion' =>
                                        current_time(
                                            'mysql'
                                        ),
                                ),

                                array(
                                    'id' =>
                                        $id,
                                ),

                                array(
                                    '%s',
                                    '%s',
                                    '%s',
                                    '%s',
                                ),

                                array(
                                    '%d',
                                )
                            );


                        if (
                            $resultado !== false
                        ) {

                            $mensaje =
                                'Empresa actualizada correctamente.';

                            $tipo_mensaje =
                                'success';

                        }
                        else {

                            $mensaje =
                                'No fue posible actualizar la empresa.';

                            $tipo_mensaje =
                                'error';

                        }

                    }

                    /**
                     * ------------------------------
                     * CREAR
                     * ------------------------------
                     */

                    else {

                        $resultado =
                            $wpdb->insert(
                                $tabla_empresas,

                                array(
                                    'rut_empresa' =>
                                        $rut_empresa,

                                    'nombre_empresa' =>
                                        $nombre_empresa,

                                    'estado' =>
                                        $estado,

                                    'fecha_registro' =>
                                        current_time(
                                            'mysql'
                                        ),

                                    'fecha_actualizacion' =>
                                        current_time(
                                            'mysql'
                                        ),
                                ),

                                array(
                                    '%s',
                                    '%s',
                                    '%s',
                                    '%s',
                                    '%s',
                                )
                            );


                        if (
                            $resultado !== false
                        ) {

                            $mensaje =
                                'Empresa registrada correctamente.';

                            $tipo_mensaje =
                                'success';

                        }
                        else {

                            $mensaje =
                                'No fue posible registrar la empresa.';

                            $tipo_mensaje =
                                'error';

                        }

                    }

                }

            }

        }

    }


    /**
     * ------------------------------------------
     * CAMBIAR ESTADO
     * ------------------------------------------
     */

    if (
        isset(
            $_GET['accion']
        )
        &&
        $_GET['accion'] === 'cambiar_estado'
        &&
        isset(
            $_GET['id']
        )
    ) {

        $id =
            absint(
                $_GET['id']
            );


        if (
            $id > 0
        ) {

            if (
                ! isset(
                    $_GET['_wpnonce']
                )
                ||
                ! wp_verify_nonce(
                    sanitize_text_field(
                        wp_unslash(
                            $_GET['_wpnonce']
                        )
                    ),
                    'sinacin_cambiar_estado_' . $id
                )
            ) {

                $mensaje =
                    'La sesión de seguridad no es válida.';

                $tipo_mensaje =
                    'error';

            }
            else {

                $estado_actual =
                    $wpdb->get_var(
                        $wpdb->prepare(
                            "
                            SELECT estado
                            FROM {$tabla_empresas}
                            WHERE id = %d
                            LIMIT 1
                            ",
                            $id
                        )
                    );


                if (
                    ! $estado_actual
                ) {

                    $mensaje =
                        'La empresa no existe.';

                    $tipo_mensaje =
                        'error';

                }
                else {

                    $nuevo_estado =
                        (
                            $estado_actual ===
                            'ACTIVA'
                        )
                            ? 'INACTIVA'
                            : 'ACTIVA';


                    if ( $nuevo_estado === 'INACTIVA' && sinacin_empresa_tiene_faenas_activas( $id ) > 0 ) {
                        $mensaje = 'No se puede desactivar la empresa: primero debes desactivar todas sus faenas activas.';
                        $tipo_mensaje = 'error';
                    } else {
                    $resultado =
                        $wpdb->update(
                            $tabla_empresas,

                            array(
                                'estado' =>
                                    $nuevo_estado,

                                'fecha_actualizacion' =>
                                    current_time(
                                        'mysql'
                                    ),
                            ),

                            array(
                                'id' =>
                                    $id,
                            ),

                            array(
                                '%s',
                                '%s',
                            ),

                            array(
                                '%d',
                            )
                        );


                    if (
                        $resultado !== false
                    ) {

                        $mensaje =
                            'Estado de la empresa actualizado correctamente.';

                        $tipo_mensaje =
                            'success';

                    }
                    else {

                        $mensaje =
                            'No fue posible actualizar el estado.';

                        $tipo_mensaje =
                            'error';

                    }
                    } // Fin de validación de faenas activas.

                }

            }

        }

    }


    /**
     * ==========================================
     * PROCESAR FAENAS
     * ==========================================
     */

    $empresa_faenas_id = isset( $_GET['empresa_faenas'] )
        ? absint( $_GET['empresa_faenas'] )
        : 0;

    // Los avisos de acciones de faenas se muestran dentro de su modal.
    $mensaje_en_modal_faenas = isset( $_POST['sinacin_guardar_faena'] )
        || ( isset( $_GET['accion'] ) && sanitize_key( wp_unslash( $_GET['accion'] ) ) === 'cambiar_estado_faena' );

    if ( isset( $_POST['sinacin_guardar_faena'] ) ) {

        if (
            ! isset( $_POST['sinacin_faena_nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['sinacin_faena_nonce'] ) ),
                'sinacin_guardar_faena'
            )
        ) {
            $mensaje = 'La sesión de seguridad no es válida.';
            $tipo_mensaje = 'error';
        } else {
            $faena_id = isset( $_POST['faena_id'] ) ? absint( $_POST['faena_id'] ) : 0;
            $empresa_id = isset( $_POST['empresa_id_faena'] ) ? absint( $_POST['empresa_id_faena'] ) : 0;
            $nombre_faena = isset( $_POST['nombre_faena'] )
                ? trim( sanitize_text_field( wp_unslash( $_POST['nombre_faena'] ) ) )
                : '';
            $estado_faena = isset( $_POST['estado_faena'] )
                ? sanitize_text_field( wp_unslash( $_POST['estado_faena'] ) )
                : 'ACTIVA';

            if ( ! in_array( $estado_faena, array( 'ACTIVA', 'INACTIVA' ), true ) ) {
                $estado_faena = 'ACTIVA';
            }

            if ( $empresa_id <= 0 ) {
                $mensaje = 'La empresa indicada no es válida.';
                $tipo_mensaje = 'error';
            } elseif ( $nombre_faena === '' ) {
                $mensaje = 'El nombre de la faena es obligatorio.';
                $tipo_mensaje = 'error';
            } else {
                $empresa_existe = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT estado FROM {$tabla_empresas} WHERE id = %d LIMIT 1",
                        $empresa_id
                    )
                );

                if ( ! $empresa_existe ) {
                    $mensaje = 'La empresa no existe.';
                    $tipo_mensaje = 'error';
                } elseif ( $estado_faena === 'ACTIVA' && $empresa_existe !== 'ACTIVA' ) {
                    $mensaje = 'No se puede activar una faena si su empresa está inactiva.';
                    $tipo_mensaje = 'error';
                } elseif ( $faena_id > 0 && $estado_faena === 'INACTIVA' && sinacin_faena_tiene_afiliados_activos( $faena_id ) > 0 ) {
                    $mensaje = 'No se puede desactivar la faena: tiene afiliados activos. Debes desafiliarlos o trasladarlos primero.';
                    $tipo_mensaje = 'error';
                } elseif ( $faena_id > 0 && (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT empresa_id FROM {$tabla_faenas} WHERE id = %d LIMIT 1", $faena_id
                ) ) !== $empresa_id ) {
                    $mensaje = 'La faena no pertenece a la empresa seleccionada.';
                    $tipo_mensaje = 'error';
                } else {
                    $duplicada = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT id FROM {$tabla_faenas} WHERE empresa_id = %d AND nombre_faena = %s AND id != %d LIMIT 1",
                            $empresa_id,
                            $nombre_faena,
                            $faena_id
                        )
                    );

                    if ( $duplicada ) {
                        $mensaje = 'Ya existe una faena con ese nombre en esta empresa.';
                        $tipo_mensaje = 'error';
                    } elseif ( $faena_id > 0 ) {
                        $resultado = $wpdb->update(
                            $tabla_faenas,
                            array(
                                'empresa_id' => $empresa_id,
                                'nombre_faena' => $nombre_faena,
                                'estado' => $estado_faena,
                                'fecha_actualizacion' => current_time( 'mysql' ),
                            ),
                            array( 'id' => $faena_id ),
                            array( '%d', '%s', '%s', '%s' ),
                            array( '%d' )
                        );

                        if ( $resultado !== false ) {
                            $mensaje = 'Faena actualizada correctamente.';
                            $tipo_mensaje = 'success';
                            $empresa_faenas_id = $empresa_id;
                        } else {
                            $mensaje = 'No fue posible actualizar la faena.';
                            $tipo_mensaje = 'error';
                        }
                    } else {
                        $resultado = $wpdb->insert(
                            $tabla_faenas,
                            array(
                                'empresa_id' => $empresa_id,
                                'nombre_faena' => $nombre_faena,
                                'estado' => $estado_faena,
                                'fecha_registro' => current_time( 'mysql' ),
                                'fecha_actualizacion' => current_time( 'mysql' ),
                            ),
                            array( '%d', '%s', '%s', '%s', '%s' )
                        );

                        if ( $resultado !== false ) {
                            $mensaje = 'Faena registrada correctamente.';
                            $tipo_mensaje = 'success';
                            $empresa_faenas_id = $empresa_id;
                        } else {
                            $mensaje = 'No fue posible registrar la faena.';
                            $tipo_mensaje = 'error';
                        }
                    }
                }
            }
        }
    }

    if (
        isset( $_GET['accion'] )
        && $_GET['accion'] === 'cambiar_estado_faena'
        && isset( $_GET['id'] )
        && isset( $_GET['empresa_faenas'] )
    ) {
        $faena_id = absint( $_GET['id'] );
        $empresa_id = absint( $_GET['empresa_faenas'] );

        if (
            ! isset( $_GET['_wpnonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
                'sinacin_cambiar_estado_faena_' . $faena_id
            )
        ) {
            $mensaje = 'La sesión de seguridad no es válida.';
            $tipo_mensaje = 'error';
        } else {
            $estado_actual = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT estado FROM {$tabla_faenas} WHERE id = %d AND empresa_id = %d LIMIT 1",
                    $faena_id,
                    $empresa_id
                )
            );

            if ( ! $estado_actual ) {
                $mensaje = 'La faena no existe.';
                $tipo_mensaje = 'error';
            } else {
                $nuevo_estado = ( $estado_actual === 'ACTIVA' ) ? 'INACTIVA' : 'ACTIVA';
                if ( $nuevo_estado === 'INACTIVA' && sinacin_faena_tiene_afiliados_activos( $faena_id ) > 0 ) {
                    $mensaje = 'No se puede desactivar la faena: tiene afiliados activos. Debes desafiliarlos o trasladarlos primero.';
                    $tipo_mensaje = 'error';
                } elseif ( $nuevo_estado === 'ACTIVA' && ! sinacin_empresa_esta_activa( $empresa_id ) ) {
                    $mensaje = 'No se puede activar la faena mientras su empresa esté inactiva.';
                    $tipo_mensaje = 'error';
                } else {
                $resultado = $wpdb->update(
                    $tabla_faenas,
                    array(
                        'estado' => $nuevo_estado,
                        'fecha_actualizacion' => current_time( 'mysql' ),
                    ),
                    array( 'id' => $faena_id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );

                if ( $resultado !== false ) {
                    $mensaje = 'Estado de la faena actualizado correctamente.';
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = 'No fue posible actualizar el estado de la faena.';
                    $tipo_mensaje = 'error';
                }
                } // Fin de validaciones del estado de faena.
            }
        }
    }

    /**
     * ==========================================
     * EDITAR FAENA
     * ==========================================
     */

    $faena_editar = null;

    if (
        $empresa_faenas_id > 0
        && isset( $_GET['editar_faena'] )
    ) {
        $faena_editar_id = absint( $_GET['editar_faena'] );

        if ( $faena_editar_id > 0 ) {
            $faena_editar = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$tabla_faenas} WHERE id = %d AND empresa_id = %d LIMIT 1",
                    $faena_editar_id,
                    $empresa_faenas_id
                )
            );
        }
    }

    /**
     * ==========================================
     * EDITAR EMPRESA
     * ==========================================
     */

    $empresa_editar =
        null;


    if (
        isset(
            $_GET['editar']
        )
    ) {

        $id_editar =
            absint(
                $_GET['editar']
            );


        if (
            $id_editar > 0
        ) {

            $empresa_editar =
                $wpdb->get_row(
                    $wpdb->prepare(
                        "
                        SELECT *
                        FROM {$tabla_empresas}
                        WHERE id = %d
                        LIMIT 1
                        ",
                        $id_editar
                    )
                );

        }

    }


    /**
     * ==========================================
     * BÚSQUEDA
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


    $buscar_normalizado =
        sinacin_normalizar_rut(
            $buscar
        );


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
     * CONSULTA
     * ==========================================
     */

    if (
        $buscar !== ''
    ) {

        $like_nombre =
            '%' .
            $wpdb->esc_like(
                $buscar
            ) .
            '%';


        $like_rut =
            '%' .
            $wpdb->esc_like(
                $buscar_normalizado
            ) .
            '%';


        $total =
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$tabla_empresas}
                    WHERE nombre_empresa LIKE %s
                    OR rut_empresa LIKE %s
                    ",
                    $like_nombre,
                    $like_rut
                )
            );


        $empresas =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$tabla_empresas}
                    WHERE nombre_empresa LIKE %s
                    OR rut_empresa LIKE %s
                    ORDER BY id DESC
                    LIMIT %d OFFSET %d
                    ",
                    $like_nombre,
                    $like_rut,
                    $por_pagina,
                    $offset
                )
            );

    }
    else {

        $total =
            $wpdb->get_var(
                "
                SELECT COUNT(*)
                FROM {$tabla_empresas}
                "
            );


        $empresas =
            $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT *
                    FROM {$tabla_empresas}
                    ORDER BY id DESC
                    LIMIT %d OFFSET %d
                    ",
                    $por_pagina,
                    $offset
                )
            );

    }


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
            Empresas SINACIN
        </h1>


        <hr class="wp-header-end">


        <?php if ( $mensaje !== '' && ! $mensaje_en_modal_faenas ) : ?>

            <div
                class="notice notice-<?php echo esc_attr( $tipo_mensaje ); ?> is-dismissible"
            >

                <p>
                    <?php echo esc_html( $mensaje ); ?>
                </p>

            </div>

        <?php endif; ?>


        <!-- ==========================================
             FORMULARIO
             ========================================== -->

        <div
            style="
                background:#fff;
                border:1px solid #dcdcde;
                padding:20px;
                margin:20px 0;
                max-width:900px;
            "
        >

            <h2>

                <?php

                echo $empresa_editar
                    ? 'Editar empresa'
                    : 'Registrar nueva empresa';

                ?>

            </h2>


            <form
                method="post"
            >

                <?php

                wp_nonce_field(
                    'sinacin_guardar_empresa',
                    'sinacin_empresa_nonce'
                );

                ?>


                <input
                    type="hidden"
                    name="sinacin_guardar_empresa"
                    value="1"
                >


                <input
                    type="hidden"
                    name="empresa_id"
                    value="<?php
                        echo $empresa_editar
                            ? esc_attr(
                                $empresa_editar->id
                            )
                            : '0';
                    ?>"
                >


                <table
                    class="form-table"
                    role="presentation"
                >

                    <tr>

                        <th scope="row">

                            <label for="rut_empresa">

                                RUT empresa

                            </label>

                        </th>

                        <td>

                            <input
                                type="text"
                                id="rut_empresa"
                                name="rut_empresa"
                                class="regular-text"
                                maxlength="12"
                                required
                                value="<?php

                                    echo $empresa_editar
                                        ? esc_attr(
                                            sinacin_formatear_rut(
                                                $empresa_editar->rut_empresa
                                            )
                                        )
                                        : '';

                                ?>"
                                placeholder="76.123.456-7"
                            >

                            <p class="description">

                                El RUT se almacenará
                                automáticamente sin puntos
                                ni guion.

                            </p>

                        </td>

                    </tr>


                    <tr>

                        <th scope="row">

                            <label for="nombre_empresa">

                                Nombre empresa

                            </label>

                        </th>

                        <td>

                            <input
                                type="text"
                                id="nombre_empresa"
                                name="nombre_empresa"
                                class="regular-text"
                                maxlength="200"
                                required
                                value="<?php

                                    echo $empresa_editar
                                        ? esc_attr(
                                            $empresa_editar->nombre_empresa
                                        )
                                        : '';

                                ?>"
                            >

                        </td>

                    </tr>


                    <tr>

                        <th scope="row">

                            <label for="estado">

                                Estado

                            </label>

                        </th>

                        <td>

                            <select
                                id="estado"
                                name="estado"
                            >

                                <option
                                    value="ACTIVA"
                                    <?php

                                    selected(
                                        $empresa_editar
                                            ? $empresa_editar->estado
                                            : 'ACTIVA',
                                        'ACTIVA'
                                    );

                                    ?>
                                >

                                    ACTIVA

                                </option>

                                <option
                                    value="INACTIVA"
                                    <?php

                                    selected(
                                        $empresa_editar
                                            ? $empresa_editar->estado
                                            : '',
                                        'INACTIVA'
                                    );

                                    ?>
                                >

                                    INACTIVA

                                </option>

                            </select>

                        </td>

                    </tr>

                </table>


                <?php

                submit_button(
                    $empresa_editar
                        ? 'Actualizar empresa'
                        : 'Registrar empresa'
                );

                ?>


                <?php if ( $empresa_editar ) : ?>

                    <a
                        href="<?php
                            echo esc_url(
                                admin_url(
                                    'admin.php?page=sinacin-empresas'
                                )
                            );
                        ?>"
                        class="button"
                    >

                        Cancelar edición

                    </a>

                <?php endif; ?>

            </form>

        </div>


        <?php
        /*
         * ==========================================
         * MODAL DE FAENAS / OBRAS
         * ==========================================
         */
        ?>

        <style>
            .sinacin-modal-overlay {
                display: none;
                position: fixed;
                z-index: 99999;
                inset: 0;
                background: rgba(0, 0, 0, 0.55);
                padding: 40px 20px;
                overflow-y: auto;
                box-sizing: border-box;
            }

            .sinacin-modal-overlay.is-open {
                display: flex;
                align-items: flex-start;
                justify-content: center;
            }

            .sinacin-modal {
                position: relative;
                width: 100%;
                max-width: 1000px;
                background: #fff;
                border-radius: 6px;
                box-shadow: 0 15px 45px rgba(0, 0, 0, 0.25);
                box-sizing: border-box;
            }

            .sinacin-modal-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 20px;
                padding: 20px 24px;
                border-bottom: 1px solid #dcdcde;
            }

            .sinacin-modal-header h2 {
                margin: 0 0 5px;
                font-size: 22px;
            }

            .sinacin-modal-header p {
                margin: 0;
                color: #646970;
            }

            .sinacin-modal-close {
                border: 0;
                background: transparent;
                color: #646970;
                cursor: pointer;
                font-size: 28px;
                line-height: 1;
                padding: 0 4px;
            }

            .sinacin-modal-close:hover {
                color: #1d2327;
            }

            .sinacin-modal-body {
                padding: 24px;
            }

            .sinacin-faena-form {
                background: #f6f7f7;
                border: 1px solid #dcdcde;
                padding: 18px;
                margin-bottom: 24px;
            }

            .sinacin-faena-form h3 {
                margin-top: 0;
            }

            .sinacin-faena-form .form-table {
                margin: 0 0 10px;
            }

            .sinacin-faena-table th,
            .sinacin-faena-table td {
                vertical-align: middle;
            }

            .sinacin-modal-footer {
                padding: 15px 24px;
                border-top: 1px solid #dcdcde;
                text-align: right;
            }

            body.sinacin-modal-open {
                overflow: hidden;
            }
        </style>

        <?php
        $empresa_faenas = null;
        $faenas = array();

        if ( $empresa_faenas_id > 0 ) {

            $empresa_faenas = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$tabla_empresas} WHERE id = %d LIMIT 1",
                    $empresa_faenas_id
                )
            );

            if ( $empresa_faenas ) {
                $faenas = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$tabla_faenas} WHERE empresa_id = %d ORDER BY id DESC",
                        $empresa_faenas_id
                    )
                );
            }
        }
        ?>

        <div
            id="sinacin-faenas-modal"
            class="sinacin-modal-overlay"
            aria-hidden="true"
        >

            <div
                class="sinacin-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="sinacin-faenas-modal-title"
            >

                <?php if ( $empresa_faenas ) : ?>

                    <div class="sinacin-modal-header">

                        <div>
                            <h2 id="sinacin-faenas-modal-title">
                                Faenas / Obras
                            </h2>

                            <p>
                                <strong>
                                    <?php echo esc_html( $empresa_faenas->nombre_empresa ); ?>
                                </strong>

                                &nbsp;|&nbsp;

                                RUT:
                                <?php echo esc_html( sinacin_formatear_rut( $empresa_faenas->rut_empresa ) ); ?>
                            </p>
                        </div>

                        <button
                            type="button"
                            class="sinacin-modal-close"
                            aria-label="Cerrar"
                        >
                            &times;
                        </button>

                    </div>

                    <div class="sinacin-modal-body">

                        <?php if ( $mensaje_en_modal_faenas && $mensaje !== '' ) : ?>
                            <div class="notice notice-<?php echo esc_attr( $tipo_mensaje ); ?> inline" role="alert">
                                <p><?php echo esc_html( $mensaje ); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="sinacin-faena-form">

                            <h3>
                                <?php echo $faena_editar ? 'Editar faena' : 'Nueva faena'; ?>
                            </h3>

                            <form method="post">

                                <?php wp_nonce_field( 'sinacin_guardar_faena', 'sinacin_faena_nonce' ); ?>

                                <input
                                    type="hidden"
                                    name="sinacin_guardar_faena"
                                    value="1"
                                >

                                <input
                                    type="hidden"
                                    name="faena_id"
                                    value="<?php echo $faena_editar ? esc_attr( $faena_editar->id ) : '0'; ?>"
                                >

                                <input
                                    type="hidden"
                                    name="empresa_id_faena"
                                    value="<?php echo esc_attr( $empresa_faenas_id ); ?>"
                                >

                                <table class="form-table" role="presentation">

                                    <tr>
                                        <th scope="row">
                                            <label for="nombre_faena">
                                                Nombre de la faena / obra
                                            </label>
                                        </th>

                                        <td>
                                            <input
                                                type="text"
                                                id="nombre_faena"
                                                name="nombre_faena"
                                                class="regular-text"
                                                maxlength="200"
                                                required
                                                value="<?php echo $faena_editar ? esc_attr( $faena_editar->nombre_faena ) : ''; ?>"
                                                placeholder="Ej.: Proyecto Hospital Norte"
                                            >
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row">
                                            <label for="estado_faena">
                                                Estado
                                            </label>
                                        </th>

                                        <td>
                                            <select
                                                id="estado_faena"
                                                name="estado_faena"
                                            >
                                                <option
                                                    value="ACTIVA"
                                                    <?php selected( $faena_editar ? $faena_editar->estado : 'ACTIVA', 'ACTIVA' ); ?>
                                                >
                                                    ACTIVA
                                                </option>

                                                <option
                                                    value="INACTIVA"
                                                    <?php selected( $faena_editar ? $faena_editar->estado : '', 'INACTIVA' ); ?>
                                                >
                                                    INACTIVA
                                                </option>
                                            </select>
                                        </td>
                                    </tr>

                                </table>

                                <?php
                                submit_button(
                                    $faena_editar
                                        ? 'Actualizar faena'
                                        : 'Crear faena',
                                    'primary',
                                    'submit',
                                    false
                                );
                                ?>

                                <?php if ( $faena_editar ) : ?>

                                    <a
                                        href="<?php echo esc_url( add_query_arg( array( 'page' => 'sinacin-empresas', 'empresa_faenas' => $empresa_faenas_id ), admin_url( 'admin.php' ) ) ); ?>"
                                        class="button"
                                    >
                                        Cancelar edición
                                    </a>

                                <?php endif; ?>

                            </form>

                        </div>

                        <h3>
                            Faenas registradas
                        </h3>

                        <table class="wp-list-table widefat fixed striped sinacin-faena-table">

                            <thead>
                                <tr>
                                    <th style="width:70px;">ID</th>
                                    <th>Faena / Obra</th>
                                    <th style="width:120px;">Estado</th>
                                    <th style="width:170px;">Fecha registro</th>
                                    <th style="width:220px;">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if ( empty( $faenas ) ) : ?>

                                    <tr>
                                        <td colspan="5">
                                            No hay faenas registradas para esta empresa.
                                        </td>
                                    </tr>

                                <?php else : ?>

                                    <?php foreach ( $faenas as $faena ) : ?>

                                        <tr>

                                            <td>
                                                <?php echo esc_html( $faena->id ); ?>
                                            </td>

                                            <td>
                                                <strong>
                                                    <?php echo esc_html( $faena->nombre_faena ); ?>
                                                </strong>
                                            </td>

                                            <td>

                                                <?php if ( $faena->estado === 'ACTIVA' ) : ?>

                                                    <span style="display:inline-block;background:#d1e7dd;color:#0f5132;padding:4px 8px;border-radius:4px;font-weight:600;">
                                                        ACTIVA
                                                    </span>

                                                <?php else : ?>

                                                    <span style="display:inline-block;background:#f8d7da;color:#842029;padding:4px 8px;border-radius:4px;font-weight:600;">
                                                        INACTIVA
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                            <td>
                                                <?php echo esc_html( $faena->fecha_registro ); ?>
                                            </td>

                                            <td>

                                                <a
                                                    href="<?php echo esc_url( add_query_arg( array( 'page' => 'sinacin-empresas', 'empresa_faenas' => $empresa_faenas_id, 'editar_faena' => $faena->id ), admin_url( 'admin.php' ) ) ); ?>"
                                                    class="button button-small"
                                                >
                                                    Editar
                                                </a>

                                                <?php
                                                $url_estado_faena = wp_nonce_url(
                                                    add_query_arg(
                                                        array(
                                                            'page'           => 'sinacin-empresas',
                                                            'empresa_faenas' => $empresa_faenas_id,
                                                            'accion'         => 'cambiar_estado_faena',
                                                            'id'             => $faena->id,
                                                        ),
                                                        admin_url( 'admin.php' )
                                                    ),
                                                    'sinacin_cambiar_estado_faena_' . $faena->id
                                                );
                                                ?>

                                                <a
                                                    href="<?php echo esc_url( $url_estado_faena ); ?>"
                                                    class="button button-small"
                                                >
                                                    <?php echo $faena->estado === 'ACTIVA' ? 'Desactivar' : 'Activar'; ?>
                                                </a>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                    <div class="sinacin-modal-footer">

                        <button
                            type="button"
                            class="button sinacin-modal-close"
                        >
                            Cerrar
                        </button>

                    </div>

                <?php else : ?>

                    <div class="sinacin-modal-header">

                        <h2 id="sinacin-faenas-modal-title">
                            Faenas / Obras
                        </h2>

                        <button
                            type="button"
                            class="sinacin-modal-close"
                            aria-label="Cerrar"
                        >
                            &times;
                        </button>

                    </div>

                    <div class="sinacin-modal-body">
                        <div class="notice notice-error inline">
                            <p>La empresa seleccionada no existe.</p>
                        </div>
                    </div>

                <?php endif; ?>

            </div>

        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {

                const modal = document.getElementById('sinacin-faenas-modal');

                if (!modal) {
                    return;
                }

                const closeButtons = modal.querySelectorAll('.sinacin-modal-close');

                function abrirModal() {
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('sinacin-modal-open');
                }

                function cerrarModal() {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('sinacin-modal-open');

                    // Al cerrar el modal, limpiamos el parámetro de la empresa
                    // para que la siguiente apertura no reutilice la empresa anterior.
                    const url = new URL(window.location.href);
                    url.searchParams.delete('empresa_faenas');
                    url.searchParams.delete('editar_faena');
                    url.searchParams.delete('accion');
                    url.searchParams.delete('id');
                    url.searchParams.delete('_wpnonce');
                    window.history.replaceState({}, '', url.toString());
                }

                closeButtons.forEach(function (button) {
                    button.addEventListener('click', cerrarModal);
                });

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        cerrarModal();
                    }
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                        cerrarModal();
                    }
                });

                document.querySelectorAll('.sinacin-abrir-faenas').forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();

                        const url = button.getAttribute('href');

                        if (!url) {
                            return;
                        }

                        const urlDestino = new URL(url, window.location.origin);
                        const urlActual = new URL(window.location.href);

                        const empresaDestino = urlDestino.searchParams.get('empresa_faenas');
                        const empresaActual = urlActual.searchParams.get('empresa_faenas');

                        // El contenido del modal se genera en PHP.
                        // Si cambia la empresa, debemos recargar la página para que
                        // PHP cargue las faenas de la empresa correcta.
                        if (empresaDestino !== empresaActual) {
                            window.location.href = urlDestino.toString();
                            return;
                        }

                        abrirModal();
                    });
                });

                <?php if ( $empresa_faenas_id > 0 ) : ?>
                    abrirModal();
                <?php endif; ?>

            });
        </script>

        <!-- ==========================================
             BUSCADOR================================= -->

        <form
            method="get"
            style="margin:20px 0;"
        >

            <input
                type="hidden"
                name="page"
                value="sinacin-empresas"
            >


            <input
                type="search"
                name="buscar"
                value="<?php
                    echo esc_attr(
                        $buscar
                    );
                ?>"
                placeholder="Buscar por RUT o nombre..."
                style="min-width:300px;"
            >


            <button
                type="submit"
                class="button"
            >

                Buscar

            </button>


            <?php if ( $buscar !== '' ) : ?>

                <a
                    href="<?php
                        echo esc_url(
                            admin_url(
                                'admin.php?page=sinacin-empresas'
                            )
                        );
                    ?>"
                    class="button"
                >

                    Limpiar

                </a>

            <?php endif; ?>

        </form>


        <!-- ==========================================
             TABLA
             ========================================== -->

        <table
            class="wp-list-table widefat fixed striped"
        >

            <thead>

                <tr>

                    <th
                        style="width:70px;"
                    >
                        ID
                    </th>

                    <th>
                        RUT
                    </th>

                    <th>
                        Empresa
                    </th>

                    <th style="width:110px;">
                        Faenas activas
                    </th>

                    <th
                        style="width:120px;"
                    >
                        Estado
                    </th>

                    <th
                        style="width:170px;"
                    >
                        Fecha registro
                    </th>

                    <th
                        style="width:240px;"
                    >
                        Acciones
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if ( empty( $empresas ) ) : ?>

                    <tr>

                        <td
                            colspan="7"
                        >

                            No se encontraron empresas.

                        </td>

                    </tr>

                <?php else : ?>


                    <?php foreach ( $empresas as $empresa ) : ?>

                        <tr>

                            <td>

                                <?php

                                echo esc_html(
                                    $empresa->id
                                );

                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?php

                                    echo esc_html(
                                        sinacin_formatear_rut(
                                            $empresa->rut_empresa
                                        )
                                    );

                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php

                                echo esc_html(
                                    $empresa->nombre_empresa
                                );

                                ?>

                            </td>


                            <td>

                                <?php
                                $cantidad_faenas = (int) $wpdb->get_var(
                                    $wpdb->prepare(
                                        "SELECT COUNT(*) FROM {$tabla_faenas} WHERE empresa_id = %d AND estado = %s",
                                        $empresa->id,
                                        'ACTIVA'
                                    )
                                );

                                $url_faenas = add_query_arg(
                                    array(
                                        'page'           => 'sinacin-empresas',
                                        'empresa_faenas' => $empresa->id,
                                    ),
                                    admin_url( 'admin.php' )
                                );
                                ?>

                                <a
                                    href="<?php echo esc_url( $url_faenas ); ?>"
                                    class="button button-small sinacin-abrir-faenas"
                                >
                                    <?php echo esc_html( $cantidad_faenas ); ?>
                                    <?php echo $cantidad_faenas === 1 ? 'faena activa' : 'faenas activas'; ?>
                                </a>

                            </td>


                            <td>

                                <?php if (
                                    $empresa->estado ===
                                    'ACTIVA'
                                ) : ?>

                                    <span
                                        style="
                                            display:inline-block;
                                            background:#d1e7dd;
                                            color:#0f5132;
                                            padding:4px 8px;
                                            border-radius:4px;
                                            font-weight:600;
                                        "
                                    >

                                        ACTIVA

                                    </span>

                                <?php else : ?>

                                    <span
                                        style="
                                            display:inline-block;
                                            background:#f8d7da;
                                            color:#842029;
                                            padding:4px 8px;
                                            border-radius:4px;
                                            font-weight:600;
                                        "
                                    >

                                        INACTIVA

                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php

                                echo esc_html(
                                    $empresa->fecha_registro
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
                                                        'sinacin-empresas',

                                                    'editar' =>
                                                        $empresa->id,
                                                ),
                                                admin_url(
                                                    'admin.php'
                                                )
                                            )
                                        );

                                    ?>"
                                    class="button button-small"
                                >

                                    Editar

                                </a>


                                <?php

                                $url_estado =
                                    wp_nonce_url(
                                        add_query_arg(
                                            array(
                                                'page' =>
                                                    'sinacin-empresas',

                                                'accion' =>
                                                    'cambiar_estado',

                                                'id' =>
                                                    $empresa->id,
                                            ),
                                            admin_url(
                                                'admin.php'
                                            )
                                        ),
                                        'sinacin_cambiar_estado_' .
                                        $empresa->id
                                    );

                                ?>


                                <a
                                    href="<?php
                                        echo esc_url(
                                            $url_estado
                                        );
                                    ?>"
                                    class="button button-small"
                                >

                                    <?php

                                    echo (
                                        $empresa->estado ===
                                        'ACTIVA'
                                    )
                                        ? 'Desactivar'
                                        : 'Activar';

                                    ?>

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>


        <!-- ==========================================
             PAGINACIÓN
             ========================================== -->

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
                                        'sinacin-empresas',

                                    'buscar' =>
                                        $buscar,

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


    <script>

    document.addEventListener(
        'DOMContentLoaded',
        function() {

            const campoRut =
                document.getElementById(
                    'rut_empresa'
                );


            if (
                !campoRut
            ) {

                return;

            }


            /**
             * Normalizar RUT.
             */

            function normalizarRut(
                rut
            ) {

                return rut
                    .replace(
                        /\./g,
                        ''
                    )
                    .replace(
                        /-/g,
                        ''
                    )
                    .replace(
                        /\s/g,
                        ''
                    )
                    .toUpperCase();

            }


            /**
             * Formatear RUT.
             */

            function formatearRut(
                rut
            ) {

                rut =
                    normalizarRut(
                        rut
                    );


                if (
                    rut.length < 2
                ) {

                    return rut;

                }


                const cuerpo =
                    rut.slice(
                        0,
                        -1
                    );


                const dv =
                    rut.slice(-1);


                let resultado = '';

                let contador = 0;


                for (
                    let i =
                        cuerpo.length - 1;

                    i >= 0;

                    i--
                ) {

                    resultado =
                        cuerpo.charAt(i) +
                        resultado;


                    contador++;


                    if (
                        contador === 3 &&
                        i !== 0
                    ) {

                        resultado =
                            '.' +
                            resultado;

                        contador = 0;

                    }

                }


                return (
                    resultado +
                    '-' +
                    dv
                );

            }


            campoRut.addEventListener(
                'blur',
                function() {

                    if (
                        campoRut.value.trim() !== ''
                    ) {

                        campoRut.value =
                            formatearRut(
                                campoRut.value
                            );

                    }

                }
            );

        }
    );

    </script>

    <?php

}