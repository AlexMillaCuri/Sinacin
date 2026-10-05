<?php
/**
 * ==========================================
 * SINACIN - PLANTILLA CERTIFICADO
 * ==========================================
 *
 * Esta plantilla es utilizada por Dompdf
 * para generar el certificado de afiliación.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <style>

        @page {
            size: letter;
            margin: 0;
        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            padding: 0;

            font-family: DejaVu Sans, sans-serif;

            color: #333333;

            background: #ffffff;

            font-size: 12px;

        }


        .pagina {

            width: 100%;

            min-height: 100%;

            padding: 55px 65px 50px 65px;

            position: relative;

        }


        /* ==========================================
           ENCABEZADO
           ========================================== */

        .encabezado {

            width: 100%;

            text-align: center;

            margin-bottom: 35px;

        }


        .logo {

            max-width: 190px;

            max-height: 90px;

            margin-bottom: 25px;

        }


        .titulo {

            font-size: 22px;

            font-weight: bold;

            letter-spacing: 1px;

            color: #333333;

            margin-bottom: 8px;

        }


        .subtitulo {

            font-size: 11px;

            color: #777777;

            letter-spacing: 0.5px;

        }


        /* ==========================================
           LÍNEA DECORATIVA
           ========================================== */

        .linea {

            width: 100%;

            height: 2px;

            background-color: #d4af37;

            margin: 25px 0 35px 0;

        }


        /* ==========================================
           CUERPO
           ========================================== */

        .introduccion {

            text-align: justify;

            line-height: 1.7;

            margin-bottom: 25px;

        }


        .nombre-afiliado {

            text-align: center;

            font-size: 18px;

            font-weight: bold;

            margin: 25px 0 8px 0;

            text-transform: uppercase;

        }


        .rut-afiliado {

            text-align: center;

            font-size: 12px;

            color: #555555;

            margin-bottom: 30px;

        }


        /* ==========================================
           TABLA DE DATOS
           ========================================== */

        .tabla-datos {

            width: 100%;

            border-collapse: collapse;

            margin-top: 15px;

            margin-bottom: 25px;

        }


        .tabla-datos td {

            border: 1px solid #dddddd;

            padding: 10px 12px;

            vertical-align: middle;

        }


        .tabla-datos .etiqueta {

            width: 32%;

            font-weight: bold;

            background-color: #f7f7f7;

            color: #444444;

        }


        .tabla-datos .valor {

            width: 68%;

            color: #333333;

        }


        /* ==========================================
           TEXTO FINAL
           ========================================== */

        .texto-final {

            text-align: justify;

            line-height: 1.7;

            margin-top: 25px;

        }


        /* ==========================================
           NÚMERO DE CERTIFICADO
           ========================================== */

        .numero-certificado {

            text-align: center;

            margin-top: 25px;

            font-size: 10px;

            color: #777777;

        }


        .numero-certificado strong {

            color: #333333;

        }


        /* ==========================================
           FIRMA
           ========================================== */

        .firma-container {

            width: 100%;

            margin-top: 65px;

            text-align: center;

        }


        .firma-linea {

            width: 220px;

            border-top: 1px solid #555555;

            margin: 0 auto 8px auto;

        }


        .firma-titulo {

            font-weight: bold;

            font-size: 11px;

        }


        .firma-organizacion {

            font-size: 10px;

            color: #666666;

            margin-top: 3px;

        }


        /* ==========================================
           TIMBRE
           ========================================== */

        .timbre-container {

            position: absolute;

            right: 65px;

            bottom: 55px;

            width: 120px;

            text-align: center;

        }


        .timbre {

            max-width: 115px;

            max-height: 115px;

        }


        /* ==========================================
           PIE
           ========================================== */

        .pie {

            position: absolute;

            left: 65px;

            right: 65px;

            bottom: 25px;

            text-align: center;

            font-size: 8px;

            color: #999999;

        }


        /* ==========================================
           MODELO
           ========================================== */

        .modelo-prueba {

            text-align: center;

            margin-top: 18px;

            font-size: 8px;

            color: #999999;

            letter-spacing: 0.5px;

        }

    </style>

</head>


<body>


<div class="pagina">


    <!-- ==========================================
         ENCABEZADO
         ========================================== -->

    <div class="encabezado">


        <?php if ( ! empty( $logo_data ) ) : ?>

            <img
                src="<?php echo esc_attr( $logo_data ); ?>"
                class="logo"
            >

        <?php endif; ?>


        <div class="titulo">

            CERTIFICADO DE AFILIACIÓN

        </div>


        <div class="subtitulo">

            SINDICATO NACIONAL DE TRABAJADORES — SINACIN

        </div>


    </div>



    <!-- ==========================================
         LÍNEA
         ========================================== -->

    <div class="linea"></div>



    <!-- ==========================================
         INTRODUCCIÓN
         ========================================== -->

    <div class="introduccion">

        Por medio del presente documento, el Sindicato Nacional de
        Trabajadores — SINACIN certifica que la persona individualizada
        a continuación se encuentra registrada como afiliada a esta
        organización sindical.

    </div>



    <!-- ==========================================
         NOMBRE
         ========================================== -->

    <div class="nombre-afiliado">

        <?php echo esc_html( $nombre_completo ); ?>

    </div>


    <div class="rut-afiliado">

        RUT:
        <?php echo esc_html( $rut_persona ); ?>

    </div>



    <!-- ==========================================
         DATOS DEL AFILIADO
         ========================================== -->

    <table class="tabla-datos">


        <tr>

            <td class="etiqueta">

                Cargo

            </td>

            <td class="valor">

                <?php echo esc_html( $cargo ); ?>

            </td>

        </tr>


        <tr>

            <td class="etiqueta">

                Empresa

            </td>

            <td class="valor">

                <?php echo esc_html( $nombre_empresa ); ?>

            </td>

        </tr>


        <tr>

            <td class="etiqueta">

                RUT Empresa

            </td>

            <td class="valor">

                <?php echo esc_html( $rut_empresa ); ?>

            </td>

        </tr>


        <tr>

            <td class="etiqueta">

                Fecha de afiliación

            </td>

            <td class="valor">

                <?php

                echo ! empty( $fecha_afiliacion )

                    ? esc_html( $fecha_afiliacion )

                    : 'No registrada';

                ?>

            </td>

        </tr>


        <tr>

            <td class="etiqueta">

                Fecha de emisión

            </td>

            <td class="valor">

                <?php echo esc_html( $fecha_emision ); ?>

            </td>

        </tr>


    </table>



    <!-- ==========================================
         TEXTO FINAL
         ========================================== -->

    <div class="texto-final">

        Se extiende el presente certificado a solicitud del interesado,
        para los fines que estime convenientes.

    </div>



    <!-- ==========================================
         NÚMERO
         ========================================== -->

    <div class="numero-certificado">

        Número de certificado:

        <strong>

            <?php echo esc_html( $numero_certificado ); ?>

        </strong>

    </div>



    <!-- ==========================================
         FIRMA
         ========================================== -->

    <div class="firma-container">


        <div class="firma-linea"></div>


        <div class="firma-titulo">

            REPRESENTANTE SINDICAL

        </div>


        <div class="firma-organizacion">

            SINACIN

        </div>


    </div>



    <!-- ==========================================
         TIMBRE
         ========================================== -->

    <?php if ( ! empty( $timbre_data ) ) : ?>

        <div class="timbre-container">

            <img
                src="<?php echo esc_attr( $timbre_data ); ?>"
                class="timbre"
            >

        </div>

    <?php endif; ?>



    <!-- ==========================================
         MODELO
         ========================================== -->

    <div class="modelo-prueba">

        MODELO DE PRUEBA — DOCUMENTO GENERADO AUTOMÁTICAMENTE

    </div>



    <!-- ==========================================
         PIE
         ========================================== -->

    <div class="pie">

        Sindicato Nacional de Trabajadores — SINACIN

    </div>


</div>


</body>

</html>