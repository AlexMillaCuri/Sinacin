<?php
/** SINACIN: importación administrativa de afiliados desde XLSX. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_ajax_sinacin_importar_previsualizar', 'sinacin_importar_previsualizar' );
add_action( 'wp_ajax_sinacin_importar_confirmar', 'sinacin_importar_confirmar' );

function sinacin_importar_autorizar() {
    if ( ! current_user_can( 'sinacin_gestionar_afiliaciones' ) ) {
        wp_send_json_error( array( 'message' => 'No tienes permisos.' ), 403 );
    }
    if ( ! check_ajax_referer( 'sinacin_importar_afiliados', 'nonce', false ) ) {
        wp_send_json_error( array( 'message' => 'Sesión de seguridad vencida. Recarga la página.' ), 403 );
    }
}

function sinacin_importar_error( $mensaje, $codigo = 400 ) {
    wp_send_json_error( array( 'message' => $mensaje ), $codigo );
}

/** Comprueba la migración real de la columna, no solamente el CREATE TABLE del plugin. */
function sinacin_importar_esquema_listo() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'sinacin_afiliaciones';
    $columna = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM `{$tabla}` LIKE %s", 'solicitud_id' ) );
    return $columna && strtoupper( $columna->Null ) === 'YES';
}

/** Lee una hoja XLSX sencilla, sin ejecutar fórmulas ni procesar entidades XML externas. */
function sinacin_importar_leer_xlsx( $archivo ) {
    if ( ! class_exists( 'ZipArchive' ) || ! class_exists( 'DOMDocument' ) ) {
        return new WP_Error( 'dependencias', 'El servidor necesita las extensiones PHP ZIP y DOM.' );
    }
    $zip = new ZipArchive();
    if ( $zip->open( $archivo ) !== true ) {
        return new WP_Error( 'xlsx', 'No se pudo abrir el Excel.' );
    }
    try {
        if ( $zip->numFiles > 150 ) {
            return new WP_Error( 'xlsx', 'El Excel contiene demasiados archivos internos.' );
        }
        $total = 0;
        for ( $i = 0; $i < $zip->numFiles; $i++ ) {
            $stat = $zip->statIndex( $i );
            $total += (int) $stat['size'];
            if ( $total > 12000000 ) {
                return new WP_Error( 'xlsx', 'El Excel excede el límite de contenido descomprimido.' );
            }
        }
        $strings = array();
        $shared = $zip->getFromName( 'xl/sharedStrings.xml' );
        if ( $shared !== false ) {
            $xml = new DOMDocument();
            if ( ! @$xml->loadXML( $shared, LIBXML_NONET ) ) {
                return new WP_Error( 'xlsx', 'El catálogo de textos del Excel no es válido.' );
            }
            $xpath = new DOMXPath( $xml );
            $xpath->registerNamespace( 'x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main' );
            foreach ( $xpath->query( '//x:si' ) as $si ) {
                $texto = '';
                foreach ( $xpath->query( './/x:t', $si ) as $t ) { $texto .= $t->textContent; }
                $strings[] = $texto;
                if ( count( $strings ) > 20000 ) { return new WP_Error( 'xlsx', 'Demasiados textos en el Excel.' ); }
            }
        }
        // La plantilla entregada usa xl/worksheets/sheet1.xml.
        $hoja = $zip->getFromName( 'xl/worksheets/sheet1.xml' );
        if ( $hoja === false ) {
            return new WP_Error( 'xlsx', 'No se encontró la primera hoja. Utiliza la plantilla SINACIN.' );
        }
        $xml = new DOMDocument();
        if ( ! @$xml->loadXML( $hoja, LIBXML_NONET ) ) {
            return new WP_Error( 'xlsx', 'La hoja del Excel no es válida.' );
        }
        $xp = new DOMXPath( $xml );
        $xp->registerNamespace( 'x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main' );
        $filas = array();
        foreach ( $xp->query( '//x:sheetData/x:row' ) as $row ) {
            $numero = (int) $row->getAttribute( 'r' );
            if ( $numero < 1 || $numero > 1002 ) { return new WP_Error( 'xlsx', 'Se admiten hasta 1.000 trabajadores por archivo.' ); }
            $celdas = array_fill( 0, 7, '' );
            foreach ( $xp->query( './x:c', $row ) as $celda ) {
                $ref = $celda->getAttribute( 'r' );
                if ( ! preg_match( '/^([A-Z]+)\d+$/', $ref, $m ) ) { continue; }
                $col = $m[1];
                if ( ! in_array( $col, array( 'A', 'B', 'C', 'D', 'E', 'F', 'G' ), true ) ) { continue; }
                if ( $xp->query( './x:f', $celda )->length ) { return new WP_Error( 'xlsx', 'No se permiten fórmulas. Convierte las celdas a valores.' ); }
                $tipo = $celda->getAttribute( 't' );
                $valor = '';
                if ( $tipo === 'inlineStr' ) {
                    foreach ( $xp->query( './/x:is//x:t', $celda ) as $t ) { $valor .= $t->textContent; }
                } else {
                    $nodo = $xp->query( './x:v', $celda )->item( 0 );
                    $valor = $nodo ? $nodo->textContent : '';
                    if ( $tipo === 's' ) { $valor = isset( $strings[ (int) $valor ] ) ? $strings[ (int) $valor ] : ''; }
                }
                $celdas[ ord( $col ) - 65 ] = trim( (string) $valor );
            }
            if ( count( array_filter( $celdas, 'strlen' ) ) ) { $filas[ $numero ] = $celdas; }
        }
        return $filas;
    } finally {
        $zip->close();
    }
}

function sinacin_importar_validar_archivo() {
    if ( empty( $_FILES['archivo'] ) || ! is_array( $_FILES['archivo'] ) ) { sinacin_importar_error( 'Selecciona un archivo Excel.' ); }
    $f = $_FILES['archivo'];
    if ( (int) $f['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file( $f['tmp_name'] ) ) { sinacin_importar_error( 'Error al cargar el archivo.' ); }
    if ( (int) $f['size'] > 2097152 || (int) $f['size'] < 100 ) { sinacin_importar_error( 'El archivo debe ser XLSX y pesar como máximo 2 MB.' ); }
    if ( strtolower( pathinfo( sanitize_file_name( $f['name'] ), PATHINFO_EXTENSION ) ) !== 'xlsx' ) { sinacin_importar_error( 'Solo se aceptan archivos .xlsx.' ); }
    $cabecera = file_get_contents( $f['tmp_name'], false, null, 0, 4 );
    if ( $cabecera !== "PK\x03\x04" ) { sinacin_importar_error( 'El archivo no es un XLSX válido.' ); }
    return $f['tmp_name'];
}

function sinacin_importar_evaluar( $archivo, $empresa_id, $faena_id ) {
    global $wpdb;
    $e = $wpdb->prefix . 'sinacin_empresas';
    $f = $wpdb->prefix . 'sinacin_faenas';
    $p = $wpdb->prefix . 'sinacin_personas';
    $a = $wpdb->prefix . 'sinacin_afiliaciones';
    $s = $wpdb->prefix . 'sinacin_solicitudes';
    $empresa = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$e} WHERE id = %d AND estado = 'ACTIVA'", $empresa_id ) );
    $faena = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$f} WHERE id = %d AND empresa_id = %d AND estado = 'ACTIVA'", $faena_id, $empresa_id ) );
    if ( ! $empresa || ! $faena ) { return new WP_Error( 'seleccion', 'Selecciona una empresa y faena activas que correspondan entre sí.' ); }
    $filas = sinacin_importar_leer_xlsx( $archivo );
    if ( is_wp_error( $filas ) ) { return $filas; }
    $cabecera = array( 'rut', 'nombres', 'apellido_paterno', 'apellido_materno', 'celular', 'correo', 'cargo' );
    if ( ! isset( $filas[1] ) || array_map( 'strtolower', $filas[1] ) !== $cabecera ) {
        return new WP_Error( 'cabecera', 'Los encabezados no coinciden con la plantilla. Usa: ' . implode( ', ', $cabecera ) );
    }
    unset( $filas[1] );
    if ( ! $filas ) { return new WP_Error( 'vacio', 'El archivo no contiene trabajadores.' ); }
    $vistos = array();
    $salida = array();
    $validos = 0;
    $errores = 0;
    foreach ( $filas as $numero => $c ) {
        $motivos = array();
        $rut = strtoupper( preg_replace( '/[^0-9K]/i', '', $c[0] ) );
        $celular = preg_replace( '/\s+/', '', $c[4] );
        if ( ! function_exists( 'sinacin_validar_rut' ) || ! sinacin_validar_rut( $rut ) ) { $motivos[] = 'RUT inválido'; }
        if ( isset( $vistos[ $rut ] ) ) { $motivos[] = 'RUT repetido en el Excel (fila ' . $vistos[ $rut ] . ')'; }
        $vistos[ $rut ] = $numero;
        foreach ( array( 1 => 'Nombres', 2 => 'Apellido paterno', 3 => 'Apellido materno', 4 => 'Celular', 5 => 'Correo', 6 => 'Cargo' ) as $i => $nombre ) {
            if ( trim( $c[$i] ) === '' ) { $motivos[] = $nombre . ' obligatorio'; }
        }
        foreach ( array( 1 => 100, 2 => 100, 3 => 100, 5 => 190, 6 => 150 ) as $i => $max ) {
            if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $c[$i] ) : strlen( $c[$i] ) ) > $max ) { $motivos[] = 'Campo demasiado largo: ' . $cabecera[$i]; }
        }
        if ( ! preg_match( '/^\+569[0-9]{8}$/', $celular ) ) { $motivos[] = 'Celular debe tener formato +569XXXXXXXX'; }
        if ( ! is_email( $c[5] ) ) { $motivos[] = 'Correo inválido'; }
        $persona = null;
        if ( $rut !== '' && ! in_array( 'RUT inválido', $motivos, true ) ) {
            $persona = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p} WHERE REPLACE(REPLACE(UPPER(rut), '.', ''), '-', '') = %s LIMIT 1", $rut ) );
            if ( $persona ) {
                $activo = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$a} WHERE persona_id = %d AND estado = 'ACTIVA' LIMIT 1", $persona->id ) );
                $pendiente = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$s} WHERE persona_id = %d AND estado = 'PENDIENTE' LIMIT 1", $persona->id ) );
                if ( $activo ) { $motivos[] = 'Ya posee afiliación activa'; }
                if ( $pendiente ) { $motivos[] = 'Tiene solicitud pendiente'; }
                if ( ! $activo && ! $pendiente ) {
                    $motivos[] = 'RUT existente en Personas: revisar datos y gestionar manualmente';
                }
            }
        }
        $estado = $motivos ? 'ERROR' : 'VÁLIDO';
        if ( $motivos ) { $errores++; } else { $validos++; }
        $salida[] = array(
            'fila'             => $numero,
            'rut'              => $rut,
            'nombres'          => $c[1],
            'apellido_paterno' => $c[2],
            'apellido_materno' => $c[3],
            'celular'          => $celular,
            'correo'           => $c[5],
            'cargo'            => $c[6],
            'validacion'       => $estado,
            'detalle_error'    => implode( '; ', $motivos ),
            'datos'            => array(
                'rut'              => $rut,
                'nombres'          => $c[1],
                'apellido_paterno' => $c[2],
                'apellido_materno' => $c[3],
                'celular'          => $celular,
                'correo'           => $c[5],
                'cargo'            => $c[6],
            ),
        );
    }
    return array( 'empresa' => $empresa, 'faena' => $faena, 'filas' => $salida, 'validos' => $validos, 'errores' => $errores );
}

function sinacin_importar_previsualizar() {
    sinacin_importar_autorizar();
    if ( ! sinacin_importar_esquema_listo() ) { sinacin_importar_error( 'La columna solicitud_id todavía no permite NULL en la base de datos. Realiza la migración antes de importar.' ); }
    $archivo = sinacin_importar_validar_archivo();
    $empresa = isset( $_POST['empresa_id'] ) ? absint( $_POST['empresa_id'] ) : 0;
    $faena = isset( $_POST['faena_id'] ) ? absint( $_POST['faena_id'] ) : 0;
    $r = sinacin_importar_evaluar( $archivo, $empresa, $faena );
    if ( is_wp_error( $r ) ) { sinacin_importar_error( $r->get_error_message() ); }
    $vista = array();
    foreach ( $r['filas'] as $fila ) { unset( $fila['datos'] ); $vista[] = $fila; }
    wp_send_json_success( array( 'filas' => $vista, 'validos' => $r['validos'], 'errores' => $r['errores'], 'hash' => hash_file( 'sha256', $archivo ) ) );
}

function sinacin_importar_confirmar() {
    sinacin_importar_autorizar();
    if ( ! sinacin_importar_esquema_listo() ) { sinacin_importar_error( 'La base de datos aún no permite afiliaciones sin solicitud.' ); }
    if ( empty( $_POST['autorizacion_confirmada'] ) || $_POST['autorizacion_confirmada'] !== '1' ) {
        sinacin_importar_error( 'Debes confirmar que el sindicato cuenta con respaldo de la afiliación de los trabajadores.' );
    }
    $archivo = sinacin_importar_validar_archivo();
    $hash = isset( $_POST['hash'] ) ? sanitize_text_field( wp_unslash( $_POST['hash'] ) ) : '';
    if ( ! preg_match( '/^[a-f0-9]{64}$/', $hash ) || ! hash_equals( $hash, hash_file( 'sha256', $archivo ) ) ) {
        sinacin_importar_error( 'El archivo cambió desde la vista previa. Vuelve a validarlo.' );
    }
    $empresa_id = isset( $_POST['empresa_id'] ) ? absint( $_POST['empresa_id'] ) : 0;
    $faena_id = isset( $_POST['faena_id'] ) ? absint( $_POST['faena_id'] ) : 0;
    global $wpdb;
    $lock = 'sinacin_importar_' . md5( $wpdb->prefix );
    if ( (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $lock ) ) !== 1 ) {
        sinacin_importar_error( 'Hay otra importación en curso. Intenta nuevamente.', 409 );
    }
    try {
        $r = sinacin_importar_evaluar( $archivo, $empresa_id, $faena_id );
        if ( is_wp_error( $r ) ) { sinacin_importar_error( $r->get_error_message() ); }
        if ( $r['errores'] || ! $r['validos'] ) { sinacin_importar_error( 'La validación detectó errores. Revisa el archivo antes de confirmar.' ); }
        $p = $wpdb->prefix . 'sinacin_personas';
        $a = $wpdb->prefix . 'sinacin_afiliaciones';
        $h = $wpdb->prefix . 'sinacin_historial';
        $ahora = current_time( 'mysql' );
        $usuario = get_current_user_id();
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) && filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP ) ? $_SERVER['REMOTE_ADDR'] : null;
        $wpdb->query( 'START TRANSACTION' );
        $ids = array();
        foreach ( $r['filas'] as $fila ) {
            $datos = $fila['datos'];
            $datos['fecha_registro'] = $ahora;
            $datos['fecha_actualizacion'] = $ahora;
            if ( $wpdb->insert( $p, $datos ) === false ) { $wpdb->query( 'ROLLBACK' ); sinacin_importar_error( 'No se pudo crear la persona de la fila ' . $fila['fila'] . '. Se canceló toda la importación.' ); }
            $persona_id = (int) $wpdb->insert_id;
            if ( $wpdb->insert( $a, array( 'persona_id' => $persona_id, 'empresa_id' => $empresa_id, 'faena_id' => $faena_id, 'solicitud_id' => null, 'estado' => 'ACTIVA', 'fecha_afiliacion' => $ahora, 'fecha_desafiliacion' => null ) ) === false ) {
                $wpdb->query( 'ROLLBACK' ); sinacin_importar_error( 'No se pudo crear la afiliación de la fila ' . $fila['fila'] . '. Se canceló toda la importación.' );
            }
            $afiliacion_id = (int) $wpdb->insert_id;
            $ids[] = $afiliacion_id;
            if ( $wpdb->insert( $h, array( 'usuario_id' => $usuario, 'entidad' => 'AFILIACION', 'entidad_id' => $afiliacion_id, 'accion' => 'IMPORTAR', 'descripcion' => sprintf( 'Importación administrativa desde Excel. RUT: %s. Empresa: %s. Faena: %s. Sin solicitud de origen. Respaldo de afiliación confirmado por administrador.', $datos['rut'], $r['empresa']->nombre_empresa, $r['faena']->nombre_faena ), 'ip' => $ip, 'fecha' => $ahora ) ) === false ) {
                $wpdb->query( 'ROLLBACK' ); sinacin_importar_error( 'No se pudo registrar el historial. Se canceló toda la importación.' );
            }
        }
        if ( $wpdb->query( 'COMMIT' ) === false ) { sinacin_importar_error( 'No se pudo confirmar la transacción. Verifica el estado de la base de datos.' ); }
        wp_send_json_success( array( 'message' => sprintf( 'Se importaron correctamente %d afiliados. No se emitieron certificados automáticamente.', count( $ids ) ), 'total' => count( $ids ) ) );
    } finally {
        $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
    }
}

/** Renderiza botón, modal y JS exclusivamente dentro de Solicitudes. */
function sinacin_importar_interfaz() {
    if ( ! current_user_can( 'sinacin_gestionar_afiliaciones' ) ) { return; }
    global $wpdb;
    $empresas = $wpdb->get_results( "SELECT id, nombre_empresa FROM {$wpdb->prefix}sinacin_empresas WHERE estado = 'ACTIVA' ORDER BY nombre_empresa" );
    $faenas = $wpdb->get_results( "SELECT id, empresa_id, nombre_faena FROM {$wpdb->prefix}sinacin_faenas WHERE estado = 'ACTIVA' ORDER BY nombre_faena" );
    ?>
    <div id="sinacin-importar-modal" class="sinacin-importar-modal" role="dialog" aria-modal="true" aria-labelledby="sinacin-importar-titulo" hidden>
      <div class="sinacin-importar-panel">
        <div class="sinacin-importar-cabecera"><h2 id="sinacin-importar-titulo">Importar afiliados desde Excel</h2><button type="button" class="button" id="sinacin-importar-cerrar">Cerrar ×</button></div>
        <p>Selecciona empresa, faena y un Excel. La validación no modifica los registros.</p>
        <div class="sinacin-importar-campos">
          <label>Empresa <select id="sinacin-importar-empresa"><option value="">Seleccionar empresa</option><?php foreach ( $empresas as $empresa ) : ?><option value="<?php echo esc_attr( $empresa->id ); ?>"><?php echo esc_html( $empresa->nombre_empresa ); ?></option><?php endforeach; ?></select></label>
          <label>Faena / Obra <select id="sinacin-importar-faena" disabled><option value="">Seleccionar faena</option></select></label>
          <label>Archivo Excel (.xlsx) <input id="sinacin-importar-archivo" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"></label>
        </div>
        <?php
        $excel_url = content_url(
            'plugins/sinacin-afiliaciones/assets/plantilla_importacion_sinacin.xlsx'
        );
        ?>

        <p>
            <a href="<?php echo esc_url( $excel_url ); ?>" download>
                Descargar plantilla Excel
            </a>
        </p>
        <button type="button" class="button button-primary" id="sinacin-importar-validar">Validar archivo</button>
        <div id="sinacin-importar-mensaje" role="status" aria-live="polite"></div>
        <div id="sinacin-importar-vista" hidden>
          <p id="sinacin-importar-resumen"></p>
          <div class="sinacin-importar-tabla-wrap" role="region" aria-label="Vista previa de afiliados" tabindex="0">
            <table class="widefat striped sinacin-importar-tabla">
              <thead>
                <tr>
                  <th scope="col">Fila</th>
                  <th scope="col">RUT</th>
                  <th scope="col">Nombres</th>
                  <th scope="col">Apellido paterno</th>
                  <th scope="col">Apellido materno</th>
                  <th scope="col">Celular</th>
                  <th scope="col">Correo</th>
                  <th scope="col">Cargo</th>
                  <th scope="col">Validación</th>
                  <th scope="col">Detalle del error</th>
                </tr>
              </thead>
              <tbody id="sinacin-importar-tbody"></tbody>
            </table>
          </div>
          <p><label><input type="checkbox" id="sinacin-importar-autorizacion"> Confirmo que el sindicato cuenta con respaldo verificable de la afiliación de todas las personas del archivo.</label></p>
          <button type="button" class="button button-primary" id="sinacin-importar-confirmar" disabled>Confirmar importación</button>
        </div>
      </div>
    </div>
    <style>
      .sinacin-importar-tabla{min-width:1420px;table-layout:auto}
      .sinacin-importar-tabla th,.sinacin-importar-tabla td{white-space:nowrap;vertical-align:middle;padding:10px 12px}
      .sinacin-importar-tabla th:last-child,.sinacin-importar-tabla td:last-child{min-width:260px;max-width:370px;white-space:normal;overflow-wrap:anywhere}
      .sinacin-importar-fila-error td:nth-last-child(2){color:#b32d2e;font-weight:700}
      .sinacin-importar-fila-valida td:nth-last-child(2){color:#007017;font-weight:700}
      .sinacin-importar-modal[hidden],#sinacin-importar-vista[hidden]{display:none!important}.sinacin-importar-modal{position:fixed;inset:0;z-index:100100;background:rgba(0,0,0,.65);display:flex;justify-content:center;align-items:center;padding:15px;box-sizing:border-box}.sinacin-importar-panel{background:#fff;border-radius:8px;width:min(1280px,100%);max-height:92vh;overflow:auto;padding:24px;box-sizing:border-box}.sinacin-importar-cabecera{display:flex;justify-content:space-between;gap:15px;align-items:center}.sinacin-importar-campos{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}.sinacin-importar-campos label{font-weight:600}.sinacin-importar-campos select,.sinacin-importar-campos input{display:block;width:100%;margin-top:7px}.sinacin-importar-tabla-wrap{max-height:340px;overflow:auto;margin:12px 0}#sinacin-importar-mensaje{margin:12px 0;font-weight:600}@media(max-width:782px){.sinacin-importar-campos{grid-template-columns:1fr}}
    </style>
    <script>
    (function(){'use strict';
      const modal=document.getElementById('sinacin-importar-modal');if(!modal)return;
      const abrir=document.getElementById('sinacin-abrir-importador'),cerrar=document.getElementById('sinacin-importar-cerrar');
      const empresa=document.getElementById('sinacin-importar-empresa'),faena=document.getElementById('sinacin-importar-faena'),archivo=document.getElementById('sinacin-importar-archivo');
      const validar=document.getElementById('sinacin-importar-validar'),confirmar=document.getElementById('sinacin-importar-confirmar'),autorizacion=document.getElementById('sinacin-importar-autorizacion');
      const vista=document.getElementById('sinacin-importar-vista'),mensaje=document.getElementById('sinacin-importar-mensaje'),tbody=document.getElementById('sinacin-importar-tbody'),resumen=document.getElementById('sinacin-importar-resumen');
      const faenas=<?php echo wp_json_encode( $faenas ); ?>,nonce=<?php echo wp_json_encode( wp_create_nonce( 'sinacin_importar_afiliados' ) ); ?>,url=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
      let hash='',validos=0,errores=0,ocupado=false,foco=null;
      function invalidar(){hash='';validos=0;errores=0;vista.hidden=true;confirmar.disabled=true;autorizacion.checked=false;mensaje.textContent='';}
      function actualizar(){confirmar.disabled=ocupado||!hash||errores>0||validos<1||!autorizacion.checked;}
      function salir(){if(ocupado)return;modal.hidden=true;document.body.style.overflow='';if(foco)foco.focus();}
      abrir.addEventListener('click',()=>{foco=document.activeElement;modal.hidden=false;document.body.style.overflow='hidden';cerrar.focus();});
      cerrar.addEventListener('click',salir);modal.addEventListener('click',e=>{if(e.target===modal)salir();});
      document.addEventListener('keydown',e=>{if(!modal.hidden&&e.key==='Escape')salir();});
      empresa.addEventListener('change',()=>{faena.replaceChildren(new Option('Seleccionar faena',''));faenas.filter(f=>String(f.empresa_id)===empresa.value).forEach(f=>faena.add(new Option(f.nombre_faena,f.id)));faena.disabled=!empresa.value;invalidar();});
      faena.addEventListener('change',invalidar);archivo.addEventListener('change',invalidar);autorizacion.addEventListener('change',actualizar);
      async function enviar(accion){const datos=new FormData();datos.append('action',accion);datos.append('nonce',nonce);datos.append('empresa_id',empresa.value);datos.append('faena_id',faena.value);datos.append('archivo',archivo.files[0]);if(accion==='sinacin_importar_confirmar'){datos.append('hash',hash);datos.append('autorizacion_confirmada',autorizacion.checked?'1':'0');}const resp=await fetch(url,{method:'POST',body:datos,credentials:'same-origin'});const json=await resp.json();if(!json.success)throw Error(json.data?.message||'Error al procesar el archivo');return json.data;}
      validar.addEventListener('click',async()=>{if(ocupado)return;if(!empresa.value||!faena.value||!archivo.files.length){mensaje.textContent='Selecciona empresa, faena y archivo.';return;}invalidar();ocupado=true;validar.disabled=true;mensaje.textContent='Validando Excel…';try{const r=await enviar('sinacin_importar_previsualizar');hash=r.hash;validos=r.validos;errores=r.errores;resumen.textContent=`Válidos: ${validos} | Con errores: ${errores}`;tbody.replaceChildren();r.filas.forEach(f=>{const tr=document.createElement('tr');tr.className=f.validacion==='ERROR'?'sinacin-importar-fila-error':'sinacin-importar-fila-valida';[f.fila,f.rut,f.nombres,f.apellido_paterno,f.apellido_materno,f.celular,f.correo,f.cargo,f.validacion,f.detalle_error||'Sin errores'].forEach(v=>{const td=document.createElement('td');td.textContent=v??'';tr.appendChild(td);});tbody.appendChild(tr);});vista.hidden=false;mensaje.textContent=errores?'Corrige los errores del Excel y vuelve a validarlo.':'Validación correcta. Revisa y confirma para importar.';}catch(e){mensaje.textContent=e.message;}finally{ocupado=false;validar.disabled=false;actualizar();}});
      confirmar.addEventListener('click',async()=>{if(confirmar.disabled||ocupado)return;if(!window.confirm(`¿Importar ${validos} afiliados activos? Esta acción registrará afiliaciones reales.`))return;ocupado=true;confirmar.disabled=true;validar.disabled=true;mensaje.textContent='Registrando afiliados…';try{const r=await enviar('sinacin_importar_confirmar');mensaje.textContent=r.message;hash='';vista.hidden=true;archivo.value='';}catch(e){mensaje.textContent=e.message;}finally{ocupado=false;validar.disabled=false;actualizar();}});
    })();
    </script>
    <?php
}
