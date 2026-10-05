<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * ==========================================
 * CONFIGURACIÓN SMTP SINACIN
 * ==========================================
 */

function sinacin_configurar_smtp( $phpmailer ) {

    $host = getenv( 'SINACIN_SMTP_HOST' );
    $port = getenv( 'SINACIN_SMTP_PORT' );
    $user = getenv( 'SINACIN_SMTP_USER' );
    $pass = getenv( 'SINACIN_SMTP_PASSWORD' );

    if (
        empty( $host ) ||
        empty( $port ) ||
        empty( $user ) ||
        empty( $pass )
    ) {
        error_log( 'SINACIN SMTP: faltan datos de configuración.' );
        return;
    }

    /*
     * Activar SMTP
     */
    $phpmailer->isSMTP();

    /*
     * Servidor
     */
    $phpmailer->Host = $host;

    /*
     * Puerto
     */
    $phpmailer->Port = absint( $port );

    /*
     * Autenticación
     */
    $phpmailer->SMTPAuth = true;

    /*
     * Credenciales
     */
    $phpmailer->Username = $user;
    $phpmailer->Password = $pass;

    /*
     * Puerto 465 = SSL
     */
    $phpmailer->SMTPSecure = 'ssl';

    /*
     * Remitente
     *
     * Lo configuramos aquí también como respaldo.
     */
    $phpmailer->From = $user;

    $phpmailer->FromName = getenv( 'SINACIN_SMTP_FROM_NAME' );
}

add_action(
    'phpmailer_init',
    'sinacin_configurar_smtp'
);


/**
 * ==========================================
 * REMITENTE WORDPRESS
 * ==========================================
 *
 * WordPress puede reemplazar el From configurado
 * directamente en PHPMailer.
 *
 * Por eso utilizamos estos filtros.
 */


/**
 * Dirección From
 */
function sinacin_correo_from( $from ) {

    $correo = getenv( 'SINACIN_SMTP_FROM' );

    if ( ! empty( $correo ) && is_email( $correo ) ) {
        return $correo;
    }

    return $from;
}

add_filter(
    'wp_mail_from',
    'sinacin_correo_from'
);


/**
 * Nombre From
 */
function sinacin_correo_from_name( $from_name ) {

    $nombre = getenv( 'SINACIN_SMTP_FROM_NAME' );

    if ( ! empty( $nombre ) ) {
        return $nombre;
    }

    return $from_name;
}

add_filter(
    'wp_mail_from_name',
    'sinacin_correo_from_name'
);


/**
 * ==========================================
 * ENVIAR CERTIFICADO POR CORREO
 * ==========================================
 */

function sinacin_enviar_certificado_por_correo(
    $correo,
    $nombre_persona,
    $ruta_pdf,
    $numero_certificado
) {

    $correo = sanitize_email( $correo );
    $nombre_persona = sanitize_text_field( $nombre_persona );
    $numero_certificado = sanitize_text_field( $numero_certificado );

    /*
     * No modificamos la ruta del archivo.
     * Es una ruta interna del servidor.
     */
    $ruta_pdf = (string) $ruta_pdf;


    /**
     * Validar correo
     */

    if ( ! is_email( $correo ) ) {

        error_log(
            'SINACIN: correo del afiliado no válido.'
        );

        return array(
            'success' => false,
            'message' => 'La dirección de correo del afiliado no es válida.',
        );
    }


    /**
     * Validar PDF
     */

    if (
        empty( $ruta_pdf ) ||
        ! file_exists( $ruta_pdf )
    ) {

        error_log(
            'SINACIN: no se encontró el PDF del certificado.'
        );

        return array(
            'success' => false,
            'message' => 'No se encontró el archivo PDF del certificado.',
        );
    }


    /**
     * Asunto
     */

    $asunto = 'Certificado de afiliación SINACIN';


    /**
     * Mensaje
     */

    $mensaje = '
        <html>
        <body>

            <p>
                Estimado/a ' . esc_html( $nombre_persona ) . ',
            </p>

            <p>
                Junto con saludar, informamos que su solicitud
                de afiliación al sindicato SINACIN ha sido
                aprobada correctamente.
            </p>

            <p>
                Adjuntamos su certificado de afiliación
                correspondiente.
            </p>

            <p>
                <strong>
                    Número de certificado:
                </strong>
                ' . esc_html( $numero_certificado ) . '
            </p>

            <p>
                Saludos cordiales,<br>
                <strong>SINACIN</strong>
            </p>

        </body>
        </html>
    ';


    /**
     * Headers
     */

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
    );


    /**
     * Archivo adjunto
     */

    $adjuntos = array(
        $ruta_pdf,
    );


    /**
     * Enviar
     */

    $enviado = wp_mail(
        $correo,
        $asunto,
        $mensaje,
        $headers,
        $adjuntos
    );


    /**
     * Resultado
     */

    if ( ! $enviado ) {

        error_log(
            'SINACIN: wp_mail no pudo enviar el certificado a ' .
            $correo
        );

        return array(
            'success' => false,
            'message' => 'No fue posible enviar el certificado por correo.',
        );
    }


    return array(
        'success' => true,
        'message' => 'Certificado enviado correctamente por correo.',
    );
}