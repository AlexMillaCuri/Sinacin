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
        'Empresas SINACIN',
        'SINACIN',
        'manage_options',
        'sinacin',
        'sinacin_pagina_empresas',
        'dashicons-building',
        25
    );

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
 * ==========================================
 * PÁGINA EMPRESAS
 * ==========================================
 */

function sinacin_pagina_empresas() {

    if (
        ! current_user_can(
            'manage_options'
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


                if (
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

                }

            }

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


        <?php if ( $mensaje !== '' ) : ?>

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
                                    'admin.php?page=sinacin'
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


        <!-- ==========================================
             BUSCADOR
             ========================================== -->

        <form
            method="get"
            style="margin:20px 0;"
        >

            <input
                type="hidden"
                name="page"
                value="sinacin"
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
                                'admin.php?page=sinacin'
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
                        style="width:220px;"
                    >
                        Acciones
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if ( empty( $empresas ) ) : ?>

                    <tr>

                        <td
                            colspan="6"
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
                                                        'sinacin',

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
                                                    'sinacin',

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
                                        'sinacin',

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