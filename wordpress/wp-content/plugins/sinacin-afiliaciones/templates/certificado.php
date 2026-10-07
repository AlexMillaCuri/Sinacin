<?php
/**
 * ==========================================
 * SINACIN - CERTIFICACIÓN DE AFILIACIÓN SINDICAL
 * ==========================================
 *
 * Plantilla utilizada por Dompdf para generar
 * el certificado de afiliación sindical.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * ==========================================
 * FORMATEAR RUT
 * ==========================================
 *
 * Ejemplos:
 *
 * 77341890K -> 77.341.890-K
 * 257616908 -> 25.761.690-8
 */

$formatear_rut = function ( $rut ) {

    $rut = strtoupper(
        preg_replace(
            '/[^0-9Kk]/',
            '',
            (string) $rut
        )
    );


    if ( strlen( $rut ) < 2 ) {
        return $rut;
    }


    $dv = substr(
        $rut,
        -1
    );


    $numero = substr(
        $rut,
        0,
        -1
    );


    $numero = number_format(
        (int) $numero,
        0,
        '',
        '.'
    );


    return $numero . '-' . $dv;

};


$rut_persona_formateado =
    $formatear_rut(
        $rut_persona
    );


$rut_empresa_formateado =
    $formatear_rut(
        $rut_empresa
    );


/**
 * ==========================================
 * FECHA DE EMISIÓN EN TEXTO
 * ==========================================
 *
 * Ejemplo:
 *
 * 18 de Septiembre de 2026
 */

$meses = array(

    1  => 'Enero',
    2  => 'Febrero',
    3  => 'Marzo',
    4  => 'Abril',
    5  => 'Mayo',
    6  => 'Junio',
    7  => 'Julio',
    8  => 'Agosto',
    9  => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre',

);


$timestamp_emision =
    current_time(
        'timestamp'
    );


$dia_emision =
    wp_date(
        'j',
        $timestamp_emision
    );


$mes_numero =
    (int) wp_date(
        'n',
        $timestamp_emision
    );


$anio_emision =
    wp_date(
        'Y',
        $timestamp_emision
    );


$mes_emision =
    isset(
        $meses[ $mes_numero ]
    )
        ? $meses[ $mes_numero ]
        : '';

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <style>

        /**
         * ==========================================
         * PÁGINA
         * ==========================================
         */

        @page {

            size: 612pt 792pt;

            margin: 0;

        }


        * {

            box-sizing: border-box;

        }


        html,
        body {

            width: 612pt;

            margin: 0;

            padding: 0;

        }


        body {

            font-family:
                DejaVu Sans,
                sans-serif;

            font-size: 10.5px;

            line-height: 1.6;

            color: #222222;

            background: #ffffff;

        }


        .pagina {

            width: 502pt;

            margin: 0;

            padding:
                42pt
                0
                35pt
                0;

            position: relative;

            left: 55pt;

        }

        .mayusculas {
            text-transform: uppercase;
        }

        .marca-agua {

            position: fixed;

            top: 245pt;

            left: 156pt;

            width: 300pt;

            height: auto;

            opacity: 0.06;

            z-index: -1;

        }

        /**
         * ==========================================
         * ENCABEZADO
         * ==========================================
         */

        .encabezado {

            width: 100%;

            text-align: center;

            margin:
                0
                0
                22px
                0;

        }


        .logo {

            width: 115px;

            max-width: 115px;

            height: auto;

            margin:
                0
                auto
                12px
                auto;

        }


        .nombre-sindicato {

            width: 100%;

            text-align: center;

            font-size: 10px;

            font-weight: bold;

            line-height: 1.45;

            margin:
                0
                0
                5px
                0;

        }


        .sinacin {

            width: 100%;

            text-align: center;

            font-size: 16px;

            font-weight: bold;

            letter-spacing: 1.5px;

            margin:
                0
                0
                4px
                0;

        }


        .datos-sindicato {

            width: 100%;

            text-align: center;

            font-size: 8px;

            line-height: 1.4;

        }


        /**
         * ==========================================
         * SEPARADOR
         * ==========================================
         */

        .separador {

            width: 100%;

            border-top: 1px solid #888888;

            margin:
                0
                0
                25px
                0;

        }


        /**
         * ==========================================
         * TÍTULO
         * ==========================================
         */

        .titulo {

            width: 100%;

            text-align: center;

            font-size: 15px;

            font-weight: bold;

            margin:
                0
                0
                28px
                0;

        }


        /**
         * ==========================================
         * CUERPO DEL DOCUMENTO
         * ==========================================
         */

        .contenido {

            width: 100%;

            margin: 0;

            padding: 0;

        }


        .parrafo {

            width: 100%;

            margin:
                0
                0
                16px
                0;

            padding: 0;

            text-align: justify;

            line-height: 1.75;

        }


        .dato {

            font-weight: bold;

        }


        /**
         * ==========================================
         * TIMBRE
         * ==========================================
         */

        .timbre-container {

            width: 100%;

            text-align: center;

            margin-top: 38px;

            margin-bottom: 28px;

        }


        .timbre {

            width: 270px;

            max-width: 270px;

            height: auto;

        }


        /**
         * ==========================================
         * FECHA
         * ==========================================
         */

        .fecha-documento {

            width: 100%;

            margin-top: 15px;

            text-align: left;

            font-size: 10px;

        }


        /**
         * ==========================================
         * PIE DEL DOCUMENTO
         * ==========================================
         */

        .pie {

            width: 100%;

            margin-top: 25px;

            padding-top: 8px;

            border-top: 1px solid #dddddd;

            text-align: center;

            font-size: 7px;

            color: #777777;

        }

    </style>

</head>


<body>

<?php if ( ! empty( $logo_data ) ) : ?>

    <img
        src="<?php echo esc_attr( $logo_data ); ?>"
        class="marca-agua"
        alt=""
    >

<?php endif; ?>


<div class="pagina">


    <!-- ==========================================
         ENCABEZADO
         ========================================== -->

    <div class="encabezado">


        <!-- LOGO SINACIN -->

        <?php if ( ! empty( $logo_data ) ) : ?>

            <img
                src="<?php echo esc_attr( $logo_data ); ?>"
                class="logo"
                alt="SINACIN"
            >

        <?php endif; ?>


        <!-- NOMBRE DEL SINDICATO -->

        <div class="nombre-sindicato">

            SINDICATO INTEREMPRESA NACIONAL DE LA
            CONSTRUCCION INDUSTRIAL Y ACTIVIDADES ANEXAS

        </div>


        <!-- SINACIN -->

        <div class="sinacin">

            SINACIN

        </div>


        <!-- DATOS INSTITUCIONALES -->

        <div class="datos-sindicato">

            Fundado el 24 de febrero de 2016

            &nbsp;&nbsp;|&nbsp;&nbsp;

            R.S.U.: 13-01-4641

        </div>


    </div>


    <!-- ==========================================
         SEPARADOR
         ========================================== -->

    <div class="separador"></div>


    <!-- ==========================================
         TÍTULO
         ========================================== -->

    <div class="titulo">

        CERTIFICACIÓN DE AFILIACIÓN SINDICAL

    </div>


    <!-- ==========================================
         CONTENIDO
         ========================================== -->

    <div class="contenido">


        <!-- PRIMER PÁRRAFO -->

        <p class="parrafo">
            Por medio del presente documento se deja constancia de que
            el/la Sr./Sra.

            <span class="dato mayusculas">

                <?php echo esc_html( $nombre_completo ); ?>

            </span>,

            RUT

            <span class="dato">

                <?php echo esc_html( $rut_persona_formateado ); ?>

            </span>,

            perteneciente a la empresa

            <span class="dato mayusculas">

                <?php echo esc_html( $nombre_empresa ); ?>

            </span>,

            RUT

            <span class="dato">

                <?php echo esc_html( $rut_empresa_formateado ); ?>

            </span>,

            y desempeñándose en la faena/obra

            <span class="dato mayusculas">

                <?php echo esc_html( $nombre_faena ); ?>

            </span>.

            Se encuentra afiliado(a) a la organización sindical
            SINACIN, manifestando su adherencia a todos los acuerdos
            colectivos de condiciones de trabajo y remuneraciones
            suscritos por el Sindicato Interempresa Nacional de la
            Construcción Industrial, Obras Civiles y Actividades
            Anexas, en adelante, SINACIN.

        </p>


        <!-- SEGUNDO PÁRRAFO -->

        <p class="parrafo">

            Asimismo, reconoce la representación de los delegados
            sindicales en faena para el cumplimiento de los acuerdos
            alcanzados y autoriza el descuento de la cuota sindical
            correspondiente a su categoría.

        </p>


        <!-- TERCER PÁRRAFO -->

        <p class="parrafo">

            La presente adherencia tendrá una duración equivalente a
            la de su contrato de trabajo, o hasta que voluntariamente
            renuncie a ella mediante un documento legalmente válido.

        </p>


    </div>


    <!-- ==========================================
         TIMBRE
         ========================================== -->

    <?php if ( ! empty( $timbre_data ) ) : ?>

        <div class="timbre-container">

            <img
                src="<?php echo esc_attr( $timbre_data ); ?>"
                class="timbre"
                alt="Timbre SINACIN"
            >

        </div>

    <?php endif; ?>


    <!-- ==========================================
         FECHA DEL DOCUMENTO
         ========================================== -->

    <div class="fecha-documento">

        Santiago,
        <?php echo esc_html( $dia_emision ); ?>
        de
        <?php echo esc_html( $mes_emision ); ?>
        de
        <?php echo esc_html( $anio_emision ); ?>

    </div>


    <!-- ==========================================
         PIE
         ========================================== -->

    <div class="pie">

        Documento generado electrónicamente por SINACIN

        &nbsp;&nbsp;—&nbsp;&nbsp;

        <?php echo esc_html( $numero_certificado ); ?>

    </div>


</div>


</body>

</html>