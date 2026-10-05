<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * ==========================================
 * PÁGINA DE PRUEBA DE CORREO
 * ==========================================
 */

add_action( 'admin_menu', 'sinacin_registrar_pagina_prueba_correo' );

function sinacin_registrar_pagina_prueba_correo() {

    add_management_page(
        'Prueba correo SINACIN',
        'Prueba correo SINACIN',
        'manage_options',
        'sinacin-prueba-correo',
        'sinacin_pagina_prueba_correo'
    );
}


/**
 * ==========================================
 * PÁGINA
 * ==========================================
 */

function sinacin_pagina_prueba_correo() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'No tienes permisos para acceder a esta página.' );
    }

    $mensaje_resultado = '';
    $tipo_resultado = '';

    /*
     * ==========================================
     * PROCESAR PRUEBA
     * ==========================================
     */

    if (
        isset( $_POST['sinacin_prueba_correo'] ) &&
        check_admin_referer(
            'sinacin_prueba_correo_action',
            'sinacin_prueba_correo_nonce'
        )
    ) {

        $correo_destino = isset( $_POST['correo_destino'] )
            ? sanitize_email( wp_unslash( $_POST['correo_destino'] ) )
            : '';

        /*
         * Validar correo
         */

        if ( ! is_email( $correo_destino ) ) {

            $mensaje_resultado = 'La dirección de correo ingresada no es válida.';
            $tipo_resultado = 'error';

        } else {

            /*
             * Verificar configuración SMTP
             */

            $smtp_host = getenv( 'SINACIN_SMTP_HOST' );
            $smtp_port = getenv( 'SINACIN_SMTP_PORT' );
            $smtp_user = getenv( 'SINACIN_SMTP_USER' );
            $smtp_pass = getenv( 'SINACIN_SMTP_PASSWORD' );

            if (
                empty( $smtp_host ) ||
                empty( $smtp_port ) ||
                empty( $smtp_user ) ||
                empty( $smtp_pass )
            ) {

                $mensaje_resultado =
                    'La configuración SMTP no está completa. ' .
                    'Revisa las variables SINACIN_SMTP_* del archivo .env.';

                $tipo_resultado = 'error';

            } else {

                /*
                 * Capturar errores de wp_mail
                 */

                $error_correo = '';

                $callback_error = function( $wp_error ) use ( &$error_correo ) {

                    if ( is_wp_error( $wp_error ) ) {
                        $error_correo = $wp_error->get_error_message();
                    }
                };

                add_action(
                    'wp_mail_failed',
                    $callback_error
                );

                /*
                 * Contenido del correo
                 */

                $asunto = 'Prueba de correo - SINACIN';

                $contenido = '
                    <html>
                    <body>

                        <p>Estimado/a,</p>

                        <p>
                            Este es un correo de prueba del
                            sistema de gestión de afiliaciones
                            de <strong>SINACIN</strong>.
                        </p>

                        <p>
                            La prueba está verificando que
                            WordPress pueda utilizar correctamente
                            el servidor SMTP configurado.
                        </p>

                        <p>
                            <strong>Servidor SMTP:</strong>
                            ' . esc_html( $smtp_host ) . '
                        </p>

                        <p>
                            <strong>Puerto:</strong>
                            ' . esc_html( $smtp_port ) . '
                        </p>

                        <p>
                            <strong>Usuario:</strong>
                            ' . esc_html( $smtp_user ) . '
                        </p>

                        <p>
                            Si recibiste este correo, la configuración
                            SMTP está funcionando correctamente.
                        </p>

                        <p>
                            Saludos cordiales,<br>
                            <strong>SINACIN</strong>
                        </p>

                    </body>
                    </html>
                ';

                $headers = array(
                    'Content-Type: text/html; charset=UTF-8',
                );

                /*
                 * Enviar correo
                 */

                $enviado = wp_mail(
                    $correo_destino,
                    $asunto,
                    $contenido,
                    $headers
                );

                remove_action(
                    'wp_mail_failed',
                    $callback_error
                );

                /*
                 * Resultado
                 */

                if ( $enviado ) {

                    $mensaje_resultado =
                        'El correo fue enviado correctamente a ' .
                        $correo_destino .
                        '.';

                    $tipo_resultado = 'success';

                } else {

                    $mensaje_resultado =
                        'WordPress no pudo enviar el correo.';

                    if ( ! empty( $error_correo ) ) {

                        $mensaje_resultado .=
                            ' Error: ' .
                            $error_correo;
                    }

                    $tipo_resultado = 'error';
                }
            }
        }
    }

    ?>

    <div class="wrap">

        <h1>Prueba de correo SINACIN</h1>

        <p>
            Esta herramienta permite comprobar que WordPress
            pueda enviar correos utilizando el servidor SMTP
            de SINACIN.
        </p>

        <?php if ( ! empty( $mensaje_resultado ) ) : ?>

            <div
                class="notice notice-<?php echo esc_attr( $tipo_resultado ); ?> is-dismissible"
            >
                <p>
                    <strong>
                        <?php echo esc_html( $mensaje_resultado ); ?>
                    </strong>
                </p>
            </div>

        <?php endif; ?>


        <div
            style="
                background:#fff;
                border:1px solid #ccd0d4;
                padding:25px;
                max-width:700px;
                margin-top:20px;
            "
        >

            <h2>Enviar correo de prueba</h2>

            <form method="post">

                <?php
                wp_nonce_field(
                    'sinacin_prueba_correo_action',
                    'sinacin_prueba_correo_nonce'
                );
                ?>

                <table class="form-table">

                    <tr>

                        <th scope="row">

                            <label for="correo_destino">
                                Correo destinatario
                            </label>

                        </th>

                        <td>

                            <input
                                type="email"
                                id="correo_destino"
                                name="correo_destino"
                                class="regular-text"
                                required
                                placeholder="correo@ejemplo.cl"
                            >

                            <p class="description">
                                Ingresa un correo al que tengas acceso
                                para comprobar la recepción.
                            </p>

                        </td>

                    </tr>

                </table>


                <p class="submit">

                    <button
                        type="submit"
                        name="sinacin_prueba_correo"
                        class="button button-primary"
                    >
                        Enviar correo de prueba
                    </button>

                </p>

            </form>

        </div>


        <div
            style="
                background:#f6f7f7;
                border-left:4px solid #2271b1;
                padding:15px;
                max-width:700px;
                margin-top:20px;
            "
        >

            <p>
                <strong>Configuración esperada:</strong>
            </p>

            <ul>

                <li>
                    Servidor:
                    <code>mail.sinacin.cl</code>
                </li>

                <li>
                    Puerto:
                    <code>465</code>
                </li>

                <li>
                    Seguridad:
                    <code>SSL</code>
                </li>

                <li>
                    Usuario:
                    <code>support@sinacin.cl</code>
                </li>

            </ul>

            <p>
                La contraseña SMTP no se muestra en esta pantalla.
            </p>

        </div>

    </div>

    <?php
}