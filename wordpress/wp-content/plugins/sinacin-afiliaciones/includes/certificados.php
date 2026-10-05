<?php
/**
 * ==========================================
 * SINACIN - GESTIÓN DE CERTIFICADOS
 * ==========================================
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * ==========================================
 * CARGAR DOMPDF
 * ==========================================
 */

$sinacin_dompdf_autoload = plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

if ( file_exists( $sinacin_dompdf_autoload ) ) {
    require_once $sinacin_dompdf_autoload;
}


/**
 * ==========================================
 * IMPORTAR DOMPDF
 * ==========================================
 */

use Dompdf\Dompdf;
use Dompdf\Options;


/**
 * ==========================================
 * GENERAR CERTIFICADO
 * ==========================================
 *
 * Genera un certificado PDF para una afiliación
 * activa y registra el certificado en la base de datos.
 *
 * @param int $afiliacion_id ID de la afiliación.
 *
 * @return array
 */

function sinacin_generar_certificado( $afiliacion_id ) {

    global $wpdb;


    /**
     * ------------------------------------------
     * VALIDAR ID
     * ------------------------------------------
     */

    $afiliacion_id = absint( $afiliacion_id );

    if ( ! $afiliacion_id ) {

        return array(
            'success' => false,
            'message' => 'ID de afiliación no válido.'
        );

    }


    /**
     * ------------------------------------------
     * VERIFICAR DOMPDF
     * ------------------------------------------
     */

    if ( ! class_exists( 'Dompdf\Dompdf' ) ) {

        return array(
            'success' => false,
            'message' => 'Dompdf no está disponible.'
        );

    }


    /**
     * ------------------------------------------
     * NOMBRES DE TABLAS
     * ------------------------------------------
     */

    $tabla_afiliaciones = $wpdb->prefix . 'sinacin_afiliaciones';
    $tabla_personas     = $wpdb->prefix . 'sinacin_personas';
    $tabla_empresas     = $wpdb->prefix . 'sinacin_empresas';
    $tabla_certificados = $wpdb->prefix . 'sinacin_certificados';


    /**
     * ------------------------------------------
     * BUSCAR AFILIACIÓN
     * ------------------------------------------
     */

    $afiliacion = $wpdb->get_row(
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
                e.rut_empresa

            FROM {$tabla_afiliaciones} a

            INNER JOIN {$tabla_personas} p
                ON p.id = a.persona_id

            INNER JOIN {$tabla_empresas} e
                ON e.id = a.empresa_id

            WHERE a.id = %d

            LIMIT 1
            ",
            $afiliacion_id
        )
    );


    /**
     * ------------------------------------------
     * VERIFICAR AFILIACIÓN
     * ------------------------------------------
     */

    if ( ! $afiliacion ) {

        return array(
            'success' => false,
            'message' => 'No se encontró la afiliación.'
        );

    }


    /**
     * ------------------------------------------
     * VERIFICAR ESTADO
     * ------------------------------------------
     */

    if ( strtoupper( $afiliacion->estado ) !== 'ACTIVA' ) {

        return array(
            'success' => false,
            'message' => 'Solo se pueden generar certificados para afiliaciones activas.'
        );

    }


    /**
     * ------------------------------------------
     * VERIFICAR SI YA EXISTE CERTIFICADO
     * ------------------------------------------
     *
     * Evitamos generar certificados duplicados.
     */

    $certificado_existente = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT *
            FROM {$tabla_certificados}
            WHERE afiliacion_id = %d
            ORDER BY id DESC
            LIMIT 1
            ",
            $afiliacion_id
        )
    );


    if ( $certificado_existente ) {

        return array(
            'success'          => true,
            'existing'         => true,
            'certificado_id'   => (int) $certificado_existente->id,
            'numero_certificado' => $certificado_existente->numero_certificado,
            'archivo_id'       => (int) $certificado_existente->archivo_id,
            'message'          => 'El certificado ya existe.'
        );

    }


    /**
     * ------------------------------------------
     * FECHA DE EMISIÓN
     * ------------------------------------------
     */

    $fecha_emision = current_time( 'mysql' );


    /**
     * ------------------------------------------
     * INSERTAR REGISTRO INICIAL
     * ------------------------------------------
     *
     * Primero insertamos el registro para obtener
     * el ID del certificado.
     */

    $resultado = $wpdb->insert(
        $tabla_certificados,
        array(
            'afiliacion_id'     => $afiliacion_id,
            'numero_certificado' => 'TEMP-' . wp_generate_uuid4(),
            'archivo_id'        => null,
            'fecha_emision'     => $fecha_emision,
            'fecha_vencimiento' => null,
            'estado'            => 'VIGENTE'
        ),
        array(
            '%d',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s'
        )
    );


    if ( false === $resultado ) {

        return array(
            'success' => false,
            'message' => 'No fue posible registrar el certificado en la base de datos.'
        );

    }


    /**
     * ------------------------------------------
     * OBTENER ID DEL CERTIFICADO
     * ------------------------------------------
     */

    $certificado_id = (int) $wpdb->insert_id;


    /**
     * ------------------------------------------
     * GENERAR NÚMERO DE CERTIFICADO
     * ------------------------------------------
     *
     * Ejemplo:
     *
     * SINACIN-2026-000001
     */

    $numero_certificado = sprintf(
        'SINACIN-%s-%06d',
        current_time( 'Y' ),
        $certificado_id
    );


    /**
     * ------------------------------------------
     * ACTUALIZAR NÚMERO
     * ------------------------------------------
     */

    $actualizado = $wpdb->update(
        $tabla_certificados,
        array(
            'numero_certificado' => $numero_certificado
        ),
        array(
            'id' => $certificado_id
        ),
        array(
            '%s'
        ),
        array(
            '%d'
        )
    );


    if ( false === $actualizado ) {

        $wpdb->delete(
            $tabla_certificados,
            array(
                'id' => $certificado_id
            ),
            array(
                '%d'
            )
        );

        return array(
            'success' => false,
            'message' => 'No fue posible asignar el número de certificado.'
        );

    }


    /**
     * ==========================================
     * PREPARAR DATOS DEL CERTIFICADO
     * ==========================================
     */


    /**
     * Nombre completo.
     */

    $nombre_completo = trim(
        $afiliacion->nombres . ' ' .
        $afiliacion->apellido_paterno . ' ' .
        $afiliacion->apellido_materno
    );


    /**
     * ------------------------------------------
     * FORMATEAR FECHAS
     * ------------------------------------------
     */

    $fecha_emision_formateada = wp_date(
        'd/m/Y',
        current_time( 'timestamp' )
    );


    /**
     * ------------------------------------------
     * FECHA DE AFILIACIÓN
     * ------------------------------------------
     */

    $fecha_afiliacion_formateada = '';

    if ( ! empty( $afiliacion->fecha_afiliacion ) ) {

        $timestamp_afiliacion = strtotime(
            $afiliacion->fecha_afiliacion
        );

        if ( $timestamp_afiliacion ) {

            $fecha_afiliacion_formateada = wp_date(
                'd/m/Y',
                $timestamp_afiliacion
            );

        }

    }


    /**
     * ------------------------------------------
     * RUT PERSONA
     * ------------------------------------------
     */

    $rut_persona = $afiliacion->rut;


    /**
     * ------------------------------------------
     * RUT EMPRESA
     * ------------------------------------------
     */

    $rut_empresa = $afiliacion->rut_empresa;


    /**
     * ==========================================
     * IMÁGENES
     * ==========================================
     *
     * Se convierten a Base64 para que Dompdf
     * pueda utilizarlas directamente.
     */

    $plugin_dir = plugin_dir_path(
        dirname( __FILE__ )
    );

    $logo_path = $plugin_dir . 'assets/images/logo_sinacin.png';

    $timbre_path = $plugin_dir . 'assets/images/timbre_sinacin.png';


    /**
     * ------------------------------------------
     * LOGO
     * ------------------------------------------
     */

    $logo_data = '';

    if ( file_exists( $logo_path ) ) {

        $logo_mime = wp_check_filetype(
            $logo_path
        );

        $logo_data = 'data:' .
            $logo_mime['type'] .
            ';base64,' .
            base64_encode(
                file_get_contents( $logo_path )
            );

    }


    /**
     * ------------------------------------------
     * TIMBRE
     * ------------------------------------------
     */

    $timbre_data = '';

    if ( file_exists( $timbre_path ) ) {

        $timbre_mime = wp_check_filetype(
            $timbre_path
        );

        $timbre_data = 'data:' .
            $timbre_mime['type'] .
            ';base64,' .
            base64_encode(
                file_get_contents( $timbre_path )
            );

    }


    /**
     * ==========================================
     * CARGAR PLANTILLA
     * ==========================================
     */

    $template_path = $plugin_dir . 'templates/certificado.php';


    if ( ! file_exists( $template_path ) ) {

        $wpdb->delete(
            $tabla_certificados,
            array(
                'id' => $certificado_id
            ),
            array(
                '%d'
            )
        );

        return array(
            'success' => false,
            'message' => 'No se encontró la plantilla del certificado.'
        );

    }


    /**
     * ==========================================
     * VARIABLES DISPONIBLES EN LA PLANTILLA
     * ==========================================
     */

    $datos_certificado = array(

        'certificado_id'           => $certificado_id,

        'numero_certificado'       => $numero_certificado,

        'nombre_completo'          => $nombre_completo,

        'rut_persona'              => $rut_persona,

        'cargo'                    => $afiliacion->cargo,

        'nombre_empresa'           => $afiliacion->nombre_empresa,

        'rut_empresa'              => $rut_empresa,

        'fecha_afiliacion'         => $fecha_afiliacion_formateada,

        'fecha_emision'            => $fecha_emision_formateada,

        'logo_data'                => $logo_data,

        'timbre_data'              => $timbre_data

    );


    /**
     * ==========================================
     * GENERAR HTML
     * ==========================================
     */

    extract(
        $datos_certificado,
        EXTR_SKIP
    );


    ob_start();

    include $template_path;

    $html = ob_get_clean();


    /**
     * ==========================================
     * CONFIGURAR DOMPDF
     * ==========================================
     */

    $options = new Options();

    $options->set(
        'defaultFont',
        'DejaVu Sans'
    );

    $options->set(
        'isRemoteEnabled',
        false
    );


    /**
     * ------------------------------------------
     * DIRECTORIO PERMITIDO
     * ------------------------------------------
     */

    $options->set(
        'chroot',
        $plugin_dir
    );


    /**
     * ==========================================
     * CREAR PDF
     * ==========================================
     */

    try {

        $dompdf = new Dompdf(
            $options
        );


        $dompdf->loadHtml(
            $html,
            'UTF-8'
        );


        /**
         * Tamaño carta.
         */

        $dompdf->setPaper(
            'LETTER',
            'portrait'
        );


        $dompdf->render();


        $pdf_content = $dompdf->output();

    } catch ( Exception $e ) {

        /**
         * Si falla Dompdf eliminamos
         * el registro temporal.
         */

        $wpdb->delete(
            $tabla_certificados,
            array(
                'id' => $certificado_id
            ),
            array(
                '%d'
            )
        );


        error_log(
            'SINACIN - Error al generar certificado: ' .
            $e->getMessage()
        );


        return array(
            'success' => false,
            'message' => 'Ocurrió un error al generar el PDF.'
        );

    }


    /**
     * ==========================================
     * CREAR DIRECTORIO DE CERTIFICADOS
     * ==========================================
     */

    $upload_dir = wp_upload_dir();

    $certificados_dir = trailingslashit(
        $upload_dir['basedir']
    ) . 'sinacin-certificados';


    if ( ! wp_mkdir_p( $certificados_dir ) ) {

        $wpdb->delete(
            $tabla_certificados,
            array(
                'id' => $certificado_id
            ),
            array(
                '%d'
            )
        );

        return array(
            'success' => false,
            'message' => 'No fue posible crear el directorio de certificados.'
        );

    }


    /**
     * ==========================================
     * NOMBRE DEL ARCHIVO
     * ==========================================
     */

    $nombre_archivo = sanitize_file_name(
        $numero_certificado . '.pdf'
    );


    $ruta_archivo = trailingslashit(
        $certificados_dir
    ) . $nombre_archivo;


    /**
     * ==========================================
     * GUARDAR PDF
     * ==========================================
     */

    $guardado = file_put_contents(
        $ruta_archivo,
        $pdf_content
    );


    if ( false === $guardado ) {

        $wpdb->delete(
            $tabla_certificados,
            array(
                'id' => $certificado_id
            ),
            array(
                '%d'
            )
        );

        return array(
            'success' => false,
            'message' => 'No fue posible guardar el archivo PDF.'
        );

    }


    /**
     * ==========================================
     * REGISTRAR PDF EN WORDPRESS
     * ==========================================
     *
     * Para esta primera versión registraremos
     * el archivo en la Biblioteca de Medios.
     */

    require_once ABSPATH . 'wp-admin/includes/file.php';

    require_once ABSPATH . 'wp-admin/includes/media.php';

    require_once ABSPATH . 'wp-admin/includes/image.php';


    $archivo_url = trailingslashit(
        $upload_dir['baseurl']
    ) . 'sinacin-certificados/' . $nombre_archivo;


    $attachment = array(

        'post_mime_type' => 'application/pdf',

        'post_title' => $numero_certificado,

        'post_content' => '',

        'post_status' => 'inherit'

    );


    $archivo_id = wp_insert_attachment(
        $attachment,
        $ruta_archivo
    );


    /**
     * ------------------------------------------
     * SI FALLA EL REGISTRO EN WORDPRESS
     * ------------------------------------------
     */

    if ( is_wp_error( $archivo_id ) ) {

        error_log(
            'SINACIN - No se pudo registrar el PDF en la Biblioteca de Medios: ' .
            $archivo_id->get_error_message()
        );

        $archivo_id = 0;

    }


    /**
     * ==========================================
     * ACTUALIZAR CERTIFICADO
     * ==========================================
     */

    $actualizado_certificado = $wpdb->update(

        $tabla_certificados,

        array(
            'archivo_id' => $archivo_id
        ),

        array(
            'id' => $certificado_id
        ),

        array(
            '%d'
        ),

        array(
            '%d'
        )

    );


    if ( false === $actualizado_certificado ) {

        /**
         * El PDF ya fue creado, por lo que no
         * eliminamos automáticamente el archivo.
         */

        error_log(
            'SINACIN - No se pudo actualizar archivo_id del certificado ' .
            $certificado_id
        );

    }


    /**
     * ==========================================
     * RESULTADO
     * ==========================================
     */

    return array(

        'success' => true,

        'existing' => false,

        'certificado_id' => $certificado_id,

        'numero_certificado' => $numero_certificado,

        'archivo_id' => (int) $archivo_id,

        'archivo_url' => $archivo_url,

        'ruta_archivo' => $ruta_archivo,

        'message' => 'Certificado generado correctamente.'

    );

}