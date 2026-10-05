<?php
/**
 * ==========================================
 * SINACIN - PRUEBA DE CERTIFICADO
 * ==========================================
 *
 * Herramienta temporal para probar la
 * generación de certificados mediante Dompdf.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * ==========================================
 * CREAR PÁGINA DE PRUEBA
 * ==========================================
 */

add_action(
    'admin_menu',
    'sinacin_agregar_pagina_prueba_certificado'
);


/**
 * Agregar página temporal al menú de WordPress.
 */
function sinacin_agregar_pagina_prueba_certificado() {

    add_management_page(

        'Prueba certificado SINACIN',

        'Prueba certificado',

        'manage_options',

        'sinacin-prueba-certificado',

        'sinacin_mostrar_pagina_prueba_certificado'

    );

}


/**
 * ==========================================
 * MOSTRAR PÁGINA
 * ==========================================
 */

function sinacin_mostrar_pagina_prueba_certificado() {

    /**
     * ------------------------------------------
     * SEGURIDAD
     * ------------------------------------------
     */

    if ( ! current_user_can( 'manage_options' ) ) {

        wp_die(
            'No tienes permisos para ejecutar esta prueba.'
        );

    }


    /**
     * ------------------------------------------
     * RESULTADO
     * ------------------------------------------
     */

    $resultado = null;


    /**
     * ------------------------------------------
     * EJECUTAR PRUEBA
     * ------------------------------------------
     */

    if (
        isset( $_POST['sinacin_generar_prueba'] )
    ) {


        /**
         * Verificar nonce.
         */

        check_admin_referer(
            'sinacin_prueba_certificado'
        );


        /**
         * Afiliación que vamos a utilizar.
         */

        $afiliacion_id = 1;


        /**
         * Generar certificado.
         */

        $resultado = sinacin_generar_certificado(
            $afiliacion_id
        );

    }


    ?>

    <div class="wrap">


        <h1>

            Prueba de certificado SINACIN

        </h1>


        <p>

            Esta herramienta es temporal y permite comprobar
            la generación de un certificado PDF utilizando
            una afiliación activa existente.

        </p>


        <?php if ( $resultado ) : ?>


            <?php if ( ! empty( $resultado['success'] ) ) : ?>


                <div class="notice notice-success is-dismissible">

                    <p>

                        <strong>
                            Certificado generado correctamente.
                        </strong>

                    </p>

                </div>


                <table class="widefat striped" style="max-width: 900px;">


                    <tbody>


                        <tr>

                            <td style="width: 250px;">

                                <strong>
                                    Afiliación
                                </strong>

                            </td>

                            <td>

                                <?php

                                echo esc_html(
                                    $afiliacion_id
                                );

                                ?>

                            </td>

                        </tr>


                        <tr>

                            <td>

                                <strong>
                                    ID certificado
                                </strong>

                            </td>

                            <td>

                                <?php

                                echo esc_html(
                                    $resultado['certificado_id']
                                );

                                ?>

                            </td>

                        </tr>


                        <tr>

                            <td>

                                <strong>
                                    Número certificado
                                </strong>

                            </td>

                            <td>

                                <?php

                                echo esc_html(
                                    $resultado['numero_certificado']
                                );

                                ?>

                            </td>

                        </tr>


                        <tr>

                            <td>

                                <strong>
                                    Archivo ID
                                </strong>

                            </td>

                            <td>

                                <?php

                                echo esc_html(
                                    $resultado['archivo_id']
                                );

                                ?>

                            </td>

                        </tr>


                        <tr>

                            <td>

                                <strong>
                                    Ruta del archivo
                                </strong>

                            </td>

                            <td>

                                <code>

                                    <?php

                                    echo esc_html(
                                        $resultado['ruta_archivo']
                                    );

                                    ?>

                                </code>

                            </td>

                        </tr>


                    </tbody>


                </table>


                <?php if ( ! empty( $resultado['archivo_url'] ) ) : ?>


                    <p style="margin-top: 20px;">

                        <a
                            href="<?php echo esc_url( $resultado['archivo_url'] ); ?>"
                            target="_blank"
                            class="button button-primary"
                        >

                            Abrir certificado PDF

                        </a>

                    </p>


                <?php endif; ?>


            <?php else : ?>


                <div class="notice notice-error is-dismissible">

                    <p>

                        <strong>
                            Error:
                        </strong>

                        <?php

                        echo esc_html(
                            $resultado['message']
                        );

                        ?>

                    </p>

                </div>


            <?php endif; ?>


        <?php endif; ?>


        <div
            style="
                background: #ffffff;
                border: 1px solid #dcdcde;
                padding: 25px;
                margin-top: 25px;
                max-width: 900px;
            "
        >


            <h2>

                Generar certificado de prueba

            </h2>


            <p>

                Se utilizará la afiliación activa con ID:

                <strong>1</strong>

            </p>


            <p>

                El certificado será generado mediante Dompdf
                y registrado en la tabla de certificados.

            </p>


            <form
                method="post"
            >


                <?php

                wp_nonce_field(
                    'sinacin_prueba_certificado'
                );

                ?>


                <input
                    type="hidden"
                    name="sinacin_generar_prueba"
                    value="1"
                >


                <?php

                submit_button(
                    'Generar certificado de prueba'
                );

                ?>


            </form>


        </div>


    </div>


    <?php

}