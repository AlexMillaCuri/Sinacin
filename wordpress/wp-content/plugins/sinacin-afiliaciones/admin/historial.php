<?php
/** Historial administrativo SINACIN. Solo lectura. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', 'sinacin_registrar_menu_historial' );
function sinacin_registrar_menu_historial() {
    add_submenu_page( 'sinacin', 'Historial', 'Historial', 'sinacin_gestionar_afiliaciones', 'sinacin-historial', 'sinacin_pagina_historial' );
}


/** Identifica al trabajador relacionado con un evento, cuando existe. */
function sinacin_historial_trabajador( $entidad, $entidad_id ) {
    global $wpdb;
    $entidad_id = absint( $entidad_id );
    $entidad = strtoupper( (string) $entidad );
    if ( ! $entidad_id ) { return null; }
    $personas = $wpdb->prefix . 'sinacin_personas';
    $afiliaciones = $wpdb->prefix . 'sinacin_afiliaciones';
    $solicitudes = $wpdb->prefix . 'sinacin_solicitudes';
    $certificados = $wpdb->prefix . 'sinacin_certificados';
    $sql = '';
    if ( 'PERSONA' === $entidad ) {
        $sql = "SELECT p.nombres, p.apellido_paterno, p.apellido_materno, p.rut FROM {$personas} p WHERE p.id = %d";
    } elseif ( 'AFILIACION' === $entidad ) {
        $sql = "SELECT p.nombres, p.apellido_paterno, p.apellido_materno, p.rut FROM {$afiliaciones} a INNER JOIN {$personas} p ON p.id = a.persona_id WHERE a.id = %d";
    } elseif ( 'SOLICITUD' === $entidad ) {
        $sql = "SELECT p.nombres, p.apellido_paterno, p.apellido_materno, p.rut FROM {$solicitudes} s INNER JOIN {$personas} p ON p.id = s.persona_id WHERE s.id = %d";
    } elseif ( 'CERTIFICADO' === $entidad ) {
        $sql = "SELECT p.nombres, p.apellido_paterno, p.apellido_materno, p.rut, c.numero_certificado FROM {$certificados} c INNER JOIN {$afiliaciones} a ON a.id = c.afiliacion_id INNER JOIN {$personas} p ON p.id = a.persona_id WHERE c.id = %d";
    }
    if ( ! $sql ) { return null; }
    return $wpdb->get_row( $wpdb->prepare( $sql, $entidad_id ) );
}


/** Identificación empresarial o de faena, obtenida de los datos actuales. */
function sinacin_historial_empresa_faena( $entidad, $id ) {
    global $wpdb;
    $id = absint( $id );
    if ( ! $id ) { return null; }
    if ( 'EMPRESA' === strtoupper( $entidad ) ) {
        $tabla = $wpdb->prefix . 'sinacin_empresas';
        return $wpdb->get_row( $wpdb->prepare( "SELECT nombre_empresa, rut_empresa FROM {$tabla} WHERE id = %d", $id ) );
    }
    if ( 'FAENA' === strtoupper( $entidad ) ) {
        $faenas = $wpdb->prefix . 'sinacin_faenas';
        $empresas = $wpdb->prefix . 'sinacin_empresas';
        return $wpdb->get_row( $wpdb->prepare( "SELECT f.nombre_faena, e.nombre_empresa, e.rut_empresa FROM {$faenas} f LEFT JOIN {$empresas} e ON e.id = f.empresa_id WHERE f.id = %d", $id ) );
    }
    return null;
}

/** Recupera únicamente los cambios estructurados creados por este módulo. */
function sinacin_historial_cambios( $descripcion ) {
    if ( ! is_string( $descripcion ) || strpos( $descripcion, 'SINACIN_CAMBIO:' ) !== 0 ) { return null; }
    $datos = json_decode( substr( $descripcion, strlen( 'SINACIN_CAMBIO:' ) ), true );
    return is_array( $datos ) && isset( $datos['cambios'] ) && is_array( $datos['cambios'] ) ? $datos : null;
}

/** Identificación contextual para la columna principal del listado. */
function sinacin_historial_identificacion( $entidad, $id ) {
    if ( in_array( strtoupper( $entidad ), array( 'EMPRESA', 'FAENA' ), true ) ) {
        $dato = sinacin_historial_empresa_faena( $entidad, $id );
        if ( ! $dato ) { return '—'; }
        return strtoupper( $entidad ) === 'EMPRESA'
            ? ( $dato->rut_empresa ?: '—' )
            : ( $dato->nombre_faena . ( ! empty( $dato->nombre_empresa ) ? ' — ' . $dato->nombre_empresa : '' ) );
    }
    $trabajador = sinacin_historial_trabajador( $entidad, $id );
    return $trabajador ? $trabajador->rut : '—';
}

function sinacin_pagina_historial() {
    if ( ! current_user_can( 'sinacin_gestionar_afiliaciones' ) ) {
        wp_die( 'No tienes permisos para consultar el historial.' );
    }
    global $wpdb;
    $tabla = $wpdb->prefix . 'sinacin_historial';
    $usuarios = $wpdb->users;
    // Detalle de un evento existente. Nunca se modifica el registro.
    $detalle_id = isset( $_GET['sinacin_detalle'] ) && is_scalar( $_GET['sinacin_detalle'] )
        ? absint( $_GET['sinacin_detalle'] ) : 0;
    if ( $detalle_id ) {
        $evento = $wpdb->get_row( $wpdb->prepare(
            "SELECT h.*, u.display_name, u.user_login FROM {$tabla} h LEFT JOIN {$usuarios} u ON u.ID = h.usuario_id WHERE h.id = %d LIMIT 1",
            $detalle_id
        ) );
        echo '<div class="wrap"><h1>Detalle del evento #' . esc_html( $detalle_id ) . '</h1>';
        echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=sinacin-historial' ) ) . '">&laquo; Volver al historial</a></p>';
        if ( ! $evento ) {
            echo '<div class="notice notice-warning"><p>El evento solicitado no existe.</p></div></div>';
            return;
        }
        $responsable = $evento->usuario_id
            ? ( $evento->display_name ?: ( 'Usuario #' . $evento->usuario_id . ' (no disponible)' ) )
            : 'Sistema / visitante';
        $trabajador = sinacin_historial_trabajador( $evento->entidad, $evento->entidad_id );
        $empresa_faena = sinacin_historial_empresa_faena( $evento->entidad, $evento->entidad_id );
        $datos_cambio = sinacin_historial_cambios( $evento->descripcion );
        $tipo = strtoupper( (string) $evento->entidad );
        $campos = array(
            'Fecha y hora' => $evento->fecha,
            'Responsable' => $responsable,
            'ID de usuario' => $evento->usuario_id ?: '—',
            'Módulo' => $evento->entidad,
            'ID del registro afectado' => $evento->entidad_id,
            'Acción' => str_replace( '_', ' ', $evento->accion ),
        );
        if ( in_array( $tipo, array( 'PERSONA', 'SOLICITUD', 'AFILIACION', 'CERTIFICADO' ), true ) ) {
            $campos['Trabajador afectado'] = $trabajador
                ? trim( $trabajador->nombres . ' ' . $trabajador->apellido_paterno . ' ' . $trabajador->apellido_materno )
                : 'No disponible';
            $campos['RUT del trabajador'] = $trabajador ? $trabajador->rut : 'No disponible';
        }
        if ( in_array( $tipo, array( 'EMPRESA', 'FAENA' ), true ) ) {
            $campos['Empresa'] = $empresa_faena && ! empty( $empresa_faena->nombre_empresa ) ? $empresa_faena->nombre_empresa : 'No disponible';
            $campos['RUT empresa'] = $empresa_faena && ! empty( $empresa_faena->rut_empresa ) ? $empresa_faena->rut_empresa : 'No disponible';
        }
        if ( 'FAENA' === $tipo ) {
            $campos['Faena / obra'] = $empresa_faena && ! empty( $empresa_faena->nombre_faena ) ? $empresa_faena->nombre_faena : 'No disponible';
        }
        if ( 'CERTIFICADO' === $tipo ) {
            $campos['Número de certificado'] = $trabajador && isset( $trabajador->numero_certificado )
                ? $trabajador->numero_certificado : 'No disponible';
        }
        $campos['Descripción registrada'] = $datos_cambio
            ? ( isset( $datos_cambio['resumen'] ) ? $datos_cambio['resumen'] : 'Cambio registrado' )
            : ( $evento->descripcion ?: '—' );
        $campos['Dirección IP'] = $evento->ip ?: '—';
        echo '<table class="widefat striped" style="max-width:960px"><tbody>';
        foreach ( $campos as $etiqueta => $valor ) {
            echo '<tr><th scope="row" style="width:240px">' . esc_html( $etiqueta ) . '</th><td style="overflow-wrap:anywhere">' . esc_html( (string) $valor ) . '</td></tr>';
        }
        echo '</tbody></table>';
        // Los cambios de faena antiguos conservan ambos valores dentro de la descripción.
        $anterior = null;
        $nuevo = null;
        if ( 'CAMBIAR_FAENA' === $evento->accion && is_string( $evento->descripcion )
            && preg_match( '/(?:Cambio(?: masivo)? de faena desde) "([^"]+)" a "([^"]+)"/u', $evento->descripcion, $coincidencias ) ) {
            $anterior = $coincidencias[1];
            $nuevo = $coincidencias[2];
        }
        echo '<h2>Comparación del cambio</h2>';
        if ( $datos_cambio ) {
            echo '<table class="widefat striped" style="max-width:960px"><thead><tr><th>Campo</th><th>Valor anterior</th><th>Valor nuevo</th></tr></thead><tbody>';
            foreach ( $datos_cambio['cambios'] as $campo => $valores ) {
                if ( ! is_array( $valores ) ) { continue; }
                echo '<tr><td>' . esc_html( $campo ) . '</td><td>' . esc_html( isset( $valores['anterior'] ) ? (string) $valores['anterior'] : '—' ) . '</td><td>' . esc_html( isset( $valores['nuevo'] ) ? (string) $valores['nuevo'] : '—' ) . '</td></tr>';
            }
            echo '</tbody></table>';
        } elseif ( null !== $anterior ) {
            echo '<table class="widefat striped" style="max-width:960px"><thead><tr><th>Valor anterior</th><th>Valor nuevo</th></tr></thead><tbody><tr><td>'
                . esc_html( $anterior ) . '</td><td>' . esc_html( $nuevo ) . '</td></tr></tbody></table>';
        } else {
            echo '<p>Este evento no contiene valores anteriores y nuevos identificables en la información almacenada.</p>';
        }
        echo '<p class="description">Los datos identificativos se consultan desde los registros actuales y pueden haber cambiado. Los valores anteriores y nuevos se muestran únicamente cuando quedaron registrados en el evento.</p></div>';
        return;
    }
    $buscar = isset( $_GET['sinacin_buscar'] ) && is_scalar( $_GET['sinacin_buscar'] ) ? sanitize_text_field( wp_unslash( $_GET['sinacin_buscar'] ) ) : '';
    $entidad = isset( $_GET['sinacin_entidad'] ) && is_scalar( $_GET['sinacin_entidad'] ) ? sanitize_text_field( wp_unslash( $_GET['sinacin_entidad'] ) ) : '';
    $accion = isset( $_GET['sinacin_accion'] ) && is_scalar( $_GET['sinacin_accion'] ) ? sanitize_text_field( wp_unslash( $_GET['sinacin_accion'] ) ) : '';
    $desde = isset( $_GET['sinacin_desde'] ) && is_scalar( $_GET['sinacin_desde'] ) ? sanitize_text_field( wp_unslash( $_GET['sinacin_desde'] ) ) : '';
    $hasta = isset( $_GET['sinacin_hasta'] ) && is_scalar( $_GET['sinacin_hasta'] ) ? sanitize_text_field( wp_unslash( $_GET['sinacin_hasta'] ) ) : '';
    foreach ( array( 'desde', 'hasta' ) as $campo ) {
        $valor = $$campo;
        $fecha = DateTimeImmutable::createFromFormat( '!Y-m-d', $valor );
        if ( ! $fecha || $fecha->format( 'Y-m-d' ) !== $valor ) { $$campo = ''; }
    }
    $por_pagina = 20;
    $pagina = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
    $where = array( '1=1' );
    $parametros = array();
    if ( $buscar !== '' ) {
        $where[] = '(h.descripcion LIKE %s OR CAST(h.entidad_id AS CHAR) = %s OR u.display_name LIKE %s OR u.user_login LIKE %s)';
        $like = '%' . $wpdb->esc_like( $buscar ) . '%';
        array_push( $parametros, $like, $buscar, $like, $like );
    }
    if ( $entidad !== '' ) { $where[] = 'h.entidad = %s'; $parametros[] = $entidad; }
    if ( $accion !== '' ) { $where[] = 'h.accion = %s'; $parametros[] = $accion; }
    if ( $desde !== '' ) { $where[] = 'h.fecha >= %s'; $parametros[] = $desde . ' 00:00:00'; }
    if ( $hasta !== '' ) { $where[] = 'h.fecha <= %s'; $parametros[] = $hasta . ' 23:59:59'; }
    $condicion = implode( ' AND ', $where );
    $from = "FROM {$tabla} h LEFT JOIN {$usuarios} u ON u.ID = h.usuario_id WHERE {$condicion}";
    $total_sql = "SELECT COUNT(*) {$from}";
    $total = (int) $wpdb->get_var( $parametros ? $wpdb->prepare( $total_sql, $parametros ) : $total_sql );
    $total_paginas = max( 1, (int) ceil( $total / $por_pagina ) );
    $pagina = min( $pagina, $total_paginas );
    $sql = "SELECT h.*, u.display_name, u.user_login {$from} ORDER BY h.fecha DESC, h.id DESC LIMIT %d OFFSET %d";
    $filas = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $parametros, array( $por_pagina, ( $pagina - 1 ) * $por_pagina ) ) ) );
    // Resolver únicamente los trabajadores de la página actual.
    $trabajadores_filas = array();
    foreach ( $filas as $fila ) {
        $trabajadores_filas[ $fila->id ] = sinacin_historial_identificacion( $fila->entidad, $fila->entidad_id );
    }
    $entidades = $wpdb->get_col( "SELECT DISTINCT entidad FROM {$tabla} ORDER BY entidad ASC" );
    $acciones = $wpdb->get_col( "SELECT DISTINCT accion FROM {$tabla} ORDER BY accion ASC" );
    $base = admin_url( 'admin.php' );
    $filtros = array( 'page' => 'sinacin-historial', 'sinacin_buscar' => $buscar, 'sinacin_entidad' => $entidad, 'sinacin_accion' => $accion, 'sinacin_desde' => $desde, 'sinacin_hasta' => $hasta );
    ?>
    <div class="wrap">
        <h1>Historial de SINACIN</h1>
        <p>Registro de operaciones del sistema. Esta sección es de solo lectura y muestra únicamente los eventos que ya fueron guardados.</p>
        <form method="get" style="display:flex;flex-wrap:wrap;align-items:end;gap:10px;margin:20px 0;">
            <input type="hidden" name="page" value="sinacin-historial">
            <label>Buscar<br><input type="search" name="sinacin_buscar" value="<?php echo esc_attr( $buscar ); ?>" placeholder="Descripción, ID o usuario"></label>
            <label>Módulo<br><select name="sinacin_entidad"><option value="">Todos</option><?php foreach ( $entidades as $opcion ) : ?><option value="<?php echo esc_attr( $opcion ); ?>" <?php selected( $entidad, $opcion ); ?>><?php echo esc_html( $opcion ); ?></option><?php endforeach; ?></select></label>
            <label>Acción<br><select name="sinacin_accion"><option value="">Todas</option><?php foreach ( $acciones as $opcion ) : ?><option value="<?php echo esc_attr( $opcion ); ?>" <?php selected( $accion, $opcion ); ?>><?php echo esc_html( str_replace( '_', ' ', $opcion ) ); ?></option><?php endforeach; ?></select></label>
            <label>Desde<br><input type="date" name="sinacin_desde" value="<?php echo esc_attr( $desde ); ?>"></label>
            <label>Hasta<br><input type="date" name="sinacin_hasta" value="<?php echo esc_attr( $hasta ); ?>"></label>
            <button class="button button-primary" type="submit">Filtrar</button>
            <a class="button" href="<?php echo esc_url( add_query_arg( 'page', 'sinacin-historial', $base ) ); ?>">Limpiar</a>
        </form>
        <p><strong><?php echo esc_html( number_format_i18n( $total ) ); ?></strong> evento(s) encontrado(s)</p>
        <div style="overflow-x:auto"><table class="widefat striped" style="min-width:900px">
            <thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Módulo</th><th>Identificación del registro</th><th>Acción</th><th>Descripción</th><th>IP</th><th>Detalle</th></tr></thead>
            <tbody><?php if ( ! $filas ) : ?><tr><td colspan="8">No hay eventos para los filtros seleccionados.</td></tr><?php else : foreach ( $filas as $fila ) : ?>
                <tr>
                    <td><?php echo esc_html( $fila->fecha ); ?></td>
                    <td><?php echo esc_html( $fila->usuario_id ? ( $fila->display_name ?: ( 'Usuario #' . $fila->usuario_id . ' (no disponible)' ) ) : 'Sistema / visitante' ); ?></td>
                    <td><?php echo esc_html( $fila->entidad ); ?></td>
                    <td><?php echo esc_html( $trabajadores_filas[ $fila->id ] ); ?></td>
                    <td><?php echo esc_html( str_replace( '_', ' ', $fila->accion ) ); ?></td>
                    <td><?php echo esc_html( ( $datos = sinacin_historial_cambios( $fila->descripcion ) ) ? ( isset( $datos['resumen'] ) ? $datos['resumen'] : 'Cambio registrado' ) : $fila->descripcion ); ?></td>
                    <td><?php echo esc_html( $fila->ip ?: '—' ); ?></td>
                    <td><a class="button button-small" href="<?php echo esc_url( add_query_arg( array_merge( $filtros, array( 'sinacin_detalle' => absint( $fila->id ) ) ), $base ) ); ?>">Ver detalle</a></td>
                </tr>
            <?php endforeach; endif; ?></tbody>
        </table></div>
        <?php if ( $total_paginas > 1 ) : ?>
            <div class="tablenav"><div class="tablenav-pages">
                <?php echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( array_merge( $filtros, array( 'paged' => '%#%' ) ), $base ), 'format' => '', 'current' => $pagina, 'total' => $total_paginas, 'prev_text' => '&laquo;', 'next_text' => '&raquo;' ) ) ); ?>
            </div></div>
        <?php endif; ?>
    </div>
    <?php
}

