<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * ==========================================
 * CONFIGURACIÓN
 * ==========================================
 */

define( 'SINACIN_VERSION_TERMINOS', '1.0' );


/**
 * ==========================================
 * SHORTCODE
 * ==========================================
 */

add_shortcode(
    'sinacin_formulario',
    'sinacin_mostrar_formulario'
);


/**
 * ==========================================
 * AJAX - VALIDAR RUT EMPRESA
 * ==========================================
 */

add_action(
    'wp_ajax_sinacin_validar_empresa',
    'sinacin_ajax_validar_empresa'
);

add_action(
    'wp_ajax_nopriv_sinacin_validar_empresa',
    'sinacin_ajax_validar_empresa'
);


function sinacin_ajax_validar_empresa() {

    check_ajax_referer(
        'sinacin_validar_empresa',
        'nonce'
    );

    global $wpdb;


    /**
     * ------------------------------------------
     * OBTENER RUT
     * ------------------------------------------
     */

    $rut_empresa = isset( $_POST['rut_empresa'] )
        ? sanitize_text_field(
            wp_unslash(
                $_POST['rut_empresa']
            )
        )
        : '';


    /**
     * ------------------------------------------
     * NORMALIZAR RUT
     * ------------------------------------------
     */

    $rut_empresa =
        sinacin_normalizar_rut_formulario(
            $rut_empresa
        );


    /**
     * ------------------------------------------
     * VALIDAR RUT
     * ------------------------------------------
     */

    if (
        ! sinacin_validar_rut_formulario(
            $rut_empresa
        )
    ) {

        wp_send_json_error(
            array(
                'message' =>
                    'El RUT de empresa ingresado no es válido.',
            )
        );

    }


    /**
     * ------------------------------------------
     * TABLA EMPRESAS
     * ------------------------------------------
     */

    $tabla_empresas =
        $wpdb->prefix . 'sinacin_empresas';


    /**
     * ------------------------------------------
     * BUSCAR EMPRESA ACTIVA
     *
     * IMPORTANTE:
     *
     * Se normaliza también el valor almacenado
     * en la base de datos.
     *
     * Así funcionan ambos formatos:
     *
     * 76.123.456-7
     * 761234567
     *
     * ------------------------------------------
     */

    $empresa = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT id
            FROM {$tabla_empresas}
            WHERE REPLACE(
                    REPLACE(
                        REPLACE(
                            UPPER(rut_empresa),
                            '.',
                            ''
                        ),
                        '-',
                        ''
                    ),
                    ' ',
                    ''
                  ) = %s
            AND estado = 'ACTIVA'
            LIMIT 1
            ",
            $rut_empresa
        )
    );


    /**
     * ------------------------------------------
     * EMPRESA NO DISPONIBLE
     * ------------------------------------------
     */

    if ( ! $empresa ) {

        wp_send_json_error(
            array(
                'message' =>
                    'No es posible continuar con el RUT de empresa ingresado.',
            )
        );

    }


    /**
     * ------------------------------------------
     * EMPRESA VÁLIDA
     * ------------------------------------------
     */

    wp_send_json_success(
        array(
            'message' =>
                'RUT de empresa validado correctamente.',
        )
    );

}


/**
 * ==========================================
 * MOSTRAR FORMULARIO
 * ==========================================
 */

function sinacin_mostrar_formulario() {
    $base_imagenes = plugins_url( '../assets/images/', __FILE__ );
    ob_start();
    ?>
    <div class="sinacin-formulario-container">
      <form id="sinacin-formulario-afiliacion" method="post" enctype="multipart/form-data" novalidate>
        <header class="sinacin-formulario-header">
          <img class="sinacin-logo" src="<?php echo esc_url( $base_imagenes . 'logo_sinacin.png' ); ?>" alt="Logo de SINACIN">
          <h2>Solicitud de afiliación a sindicato SINACIN</h2>
          <p>Completa tus datos para enviar tu solicitud de afiliación.</p>
        </header>
        <?php wp_nonce_field( 'sinacin_enviar_solicitud', 'sinacin_nonce' ); ?>
        <section class="sinacin-seccion" aria-labelledby="sinacin-titulo-empresa">
          <h3 id="sinacin-titulo-empresa">Empresa y lugar de trabajo</h3>
          <p class="sinacin-ayuda-seccion">Primero valida el RUT de tu empresa para continuar.</p>
          <div class="sinacin-campo">
            <label for="rut_empresa">RUT de empresa <span aria-hidden="true">*</span></label>
            <input type="text" id="rut_empresa" name="rut_empresa" placeholder="Ej.: 76.123.456-7" maxlength="12" required autocomplete="off" inputmode="text">
            <small id="sinacin-empresa-mensaje" aria-live="polite"></small>
          </div>
          <div class="sinacin-campo">
            <label for="faena_id">Faena u obra donde trabajas <span aria-hidden="true">*</span></label>
            <select id="faena_id" name="faena_id" required disabled><option value="">Primero valida el RUT de la empresa</option></select>
            <small id="sinacin-faena-mensaje">Selecciona la faena u obra donde trabajas.</small>
          </div>
        </section>
        <section class="sinacin-seccion" aria-labelledby="sinacin-titulo-datos">
          <h3 id="sinacin-titulo-datos">Datos personales</h3>
          <div class="sinacin-campo"><label for="nombres">Nombre(s) <span aria-hidden="true">*</span></label><input type="text" id="nombres" name="nombres" maxlength="100" autocomplete="given-name" required disabled placeholder="Ingresa tus nombres"></div>
          <div class="sinacin-grid">
            <div class="sinacin-campo"><label for="apellido_paterno">Apellido paterno <span aria-hidden="true">*</span></label><input type="text" id="apellido_paterno" name="apellido_paterno" maxlength="100" autocomplete="family-name" required disabled placeholder="Apellido paterno"></div>
            <div class="sinacin-campo"><label for="apellido_materno">Apellido materno <span aria-hidden="true">*</span></label><input type="text" id="apellido_materno" name="apellido_materno" maxlength="100" required disabled placeholder="Apellido materno"></div>
            <div class="sinacin-campo"><label for="rut">RUT <span aria-hidden="true">*</span></label><input type="text" id="rut" name="rut" maxlength="12" required disabled placeholder="12.345.678-9" autocomplete="off"><small id="sinacin-rut-mensaje" aria-live="polite"></small></div>
            <div class="sinacin-campo"><label for="celular_digitos">Celular <span aria-hidden="true">*</span></label><div class="sinacin-telefono"><span aria-hidden="true">+56 9</span><input type="tel" id="celular_digitos" name="celular_digitos" inputmode="numeric" autocomplete="tel-national" placeholder="1234 5678" maxlength="8" minlength="8" pattern="[0-9]{8}" required disabled aria-label="Ocho dígitos del celular"></div><input type="hidden" id="celular" name="celular" value=""></div>
          </div>
          <div class="sinacin-campo"><label for="correo">Correo electrónico <span aria-hidden="true">*</span></label><input type="email" id="correo" name="correo" maxlength="190" autocomplete="email" placeholder="ejemplo@correo.cl" required disabled></div>
          <div class="sinacin-campo"><label for="cargo">Cargo actual <span aria-hidden="true">*</span></label><input type="text" id="cargo" name="cargo" maxlength="150" placeholder="Ej.: Soldador, operador, maestro" required disabled></div>
        </section>
        <section class="sinacin-seccion" aria-labelledby="sinacin-titulo-cedula">
          <h3 id="sinacin-titulo-cedula">Documento de identidad</h3>
          <div class="sinacin-campo"><label for="cedula">Fotografía de cédula de identidad <span aria-hidden="true">*</span> <em>Solo lado frontal</em></label>
            <div class="sinacin-documento-grid"><div class="sinacin-subida"><input type="file" id="cedula" name="cedula" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required disabled><small>JPG, JPEG, PNG o WEBP. Máximo 5 MB. Debe verse completa y legible.</small><div id="sinacin-vista-previa" hidden><img id="sinacin-imagen-previa" alt="Vista previa de la fotografía seleccionada"></div></div><figure class="sinacin-ejemplo"><img src="<?php echo esc_url( $base_imagenes . 'ejemplo_carnet_frontal.png' ); ?>" alt="Ejemplo de fotografía del lado frontal de la cédula de identidad" loading="lazy"><figcaption>Ejemplo: lado frontal</figcaption></figure></div>
          </div>
        </section>
        <section class="sinacin-seccion" aria-labelledby="sinacin-titulo-declaraciones">
          <h3 id="sinacin-titulo-declaraciones">Declaraciones y autorizaciones</h3>
          <div class="sinacin-declaraciones">
            <label class="sinacin-checkbox-label" for="acepta_terminos"><input type="checkbox" id="acepta_terminos" name="acepta_terminos" value="1" required disabled><span>En conformidad con el Código del Trabajo, declaro libre y voluntariamente mi decisión de afiliarme al Sindicato Interempresa Nacional de la Construcción Industrial y Actividades Anexas - SINACIN.</span></label>
            <label class="sinacin-checkbox-label" for="autoriza_descuento"><input type="checkbox" id="autoriza_descuento" name="autoriza_descuento" value="1" required disabled><span>Autorizo expresamente a mi empleador para descontar de mis remuneraciones la cuota sindical ordinaria y aquellas extraordinarias aprobadas conforme a la ley y a los estatutos sindicales, debiendo enterarse dichos montos al sindicato.</span></label>
            <label class="sinacin-checkbox-label" for="reconoce_delegados"><input type="checkbox" id="reconoce_delegados" name="reconoce_delegados" value="1" required disabled><span>Reconozco la representación de los delegados en faena y el cumplimiento de los acuerdos alcanzados.</span></label>
          </div>
        </section>
        <div class="sinacin-privacidad">Tus antecedentes serán utilizados para gestionar tu solicitud de afiliación. Verifica que tus datos sean correctos antes de enviarlos.</div>
        <button type="submit" id="sinacin-boton-enviar" disabled>Enviar solicitud de afiliación</button>
        <div id="sinacin-mensaje-general" role="status" aria-live="polite" hidden></div>
      </form>
      <div id="sinacin-confirmacion-final" class="sinacin-confirmacion-final" role="status" aria-live="polite" hidden>
        <span class="sinacin-confirmacion-icono" aria-hidden="true">✓</span>
        <h2>¡Solicitud enviada con éxito!</h2>
        <p>Tu solicitud de afiliación fue recibida correctamente y quedó pendiente de revisión por SINACIN.</p>
        <p class="sinacin-confirmacion-nota">Gracias por completar el formulario.</p>
      </div>
    </div>
    <style>
      .sinacin-formulario-container{--sinacin-rojo:#922e38;--sinacin-borde:#e9dadd;--sinacin-texto:#30343b;max-width:820px;margin:36px auto;padding:0 16px;font-family:system-ui,-apple-system,"Segoe UI",Arial,sans-serif;color:var(--sinacin-texto);box-sizing:border-box}
      .sinacin-formulario-container *{box-sizing:border-box}
      .sinacin-formulario-container [hidden]{display:none!important}
      #sinacin-empresa-mensaje[data-estado="cargando"]{display:flex;align-items:center;gap:9px;color:#762832}
      #sinacin-empresa-mensaje[data-estado="cargando"]::before{content:"";display:inline-block;width:15px;height:15px;flex:0 0 15px;border:2px solid #e9dadd;border-top-color:#922e38;border-radius:50%;animation:sinacin-giro .7s linear infinite}
      #sinacin-empresa-mensaje[data-estado="exito"]{color:#28623c}
      #sinacin-empresa-mensaje[data-estado="error"]{color:#922e38}
      @keyframes sinacin-giro{to{transform:rotate(360deg)}}
      .sinacin-confirmacion-final:not([hidden]){display:flex;flex-direction:column;align-items:center;text-align:center;background:#fff;border:1px solid #e9dadd;border-radius:20px;padding:54px 30px;box-shadow:0 15px 45px rgba(54,24,31,.07)}
      .sinacin-confirmacion-icono{display:flex;align-items:center;justify-content:center;width:68px;height:68px;background:#eef8f0;color:#27623c;border-radius:50%;font-size:38px;font-weight:700;margin-bottom:20px}
      .sinacin-confirmacion-final h2{font-size:25px;color:#762832;margin:0 0 14px}
      .sinacin-confirmacion-final p{font-size:15px;line-height:1.65;color:#49434a;margin:0 0 12px;max-width:530px}
      .sinacin-confirmacion-final .sinacin-confirmacion-nota{font-size:13px;color:#756e74}
      .sinacin-formulario-container form{background:#fff;border:1px solid var(--sinacin-borde);border-radius:20px;padding:36px 42px;box-shadow:0 15px 45px rgba(54,24,31,.07)}
      .sinacin-formulario-header{text-align:center;border-bottom:1px solid #eee6e8;padding-bottom:28px;margin-bottom:26px}
      .sinacin-formulario-container .sinacin-logo{display:block;max-width:180px;max-height:110px;width:auto;height:auto;object-fit:contain;margin:0 auto 18px}
      .sinacin-formulario-header h2{font-size:clamp(22px,3vw,29px);line-height:1.25;color:#762832;font-weight:750;margin:0}
      .sinacin-formulario-header p{font-size:15px;color:#68616a;line-height:1.5;margin:12px 0 0}
      .sinacin-seccion{padding:10px 0 22px;margin-bottom:16px;border-bottom:1px solid #f0e8ea}
      .sinacin-seccion h3{font-size:17px;font-weight:700;color:#652630;margin:0 0 17px}
      .sinacin-ayuda-seccion{font-size:13px;color:#6c6267;margin:-8px 0 16px}
      .sinacin-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 16px}
      .sinacin-campo{margin-bottom:18px;min-width:0}
      .sinacin-campo label{display:block;font-size:13.5px;font-weight:650;margin:0 0 8px;color:#3b3034}
      .sinacin-campo label span{color:var(--sinacin-rojo)}
      .sinacin-campo label em{font-style:normal;color:#8d3e49;font-weight:500;font-size:12px;margin-left:5px}
      .sinacin-campo input:not([type="checkbox"]):not([type="hidden"]),.sinacin-campo select{width:100%;min-height:46px;border:1px solid #dcd1d4;border-radius:10px;background:#fff;padding:10px 13px;font-size:15px;color:#282328;box-shadow:none}
      .sinacin-campo input:focus,.sinacin-campo select:focus{outline:2px solid rgba(146,46,56,.17);border-color:var(--sinacin-rojo)}
      .sinacin-campo input:disabled,.sinacin-campo select:disabled{background:#f6f4f5;color:#898187;cursor:not-allowed}
      .sinacin-campo small{display:block;font-size:12px;color:#726b70;margin-top:6px;line-height:1.5}
      .sinacin-telefono{display:flex;align-items:center;border:1px solid #dcd1d4;border-radius:10px;overflow:hidden;background:#fff}
      .sinacin-telefono>span{flex-shrink:0;padding:0 12px;background:#f8f1f2;color:#6c2c36;font-weight:700;font-size:14px;align-self:stretch;display:flex;align-items:center;border-right:1px solid #e8dadd}
      .sinacin-formulario-container .sinacin-telefono input{border:0!important;border-radius:0!important;min-width:0}
      .sinacin-documento-grid{display:grid;grid-template-columns:1fr 210px;gap:18px;align-items:start}
      .sinacin-subida{min-width:0}.sinacin-subida input[type="file"]{font-size:12px;padding:9px;max-width:100%}
      .sinacin-ejemplo{margin:0;border:1px solid var(--sinacin-borde);border-radius:12px;background:#fbf8f9;padding:10px;text-align:center}
      .sinacin-ejemplo img{display:block;width:100%;height:auto;max-height:140px;object-fit:contain;border-radius:6px}
      .sinacin-ejemplo figcaption{font-size:11px;color:#76696d;margin-top:6px}
      #sinacin-vista-previa{margin-top:12px}#sinacin-imagen-previa{max-width:100%;max-height:150px;border-radius:8px;border:1px solid #e8dadd}
      .sinacin-declaraciones{background:#fcf8f9;border:1px solid #eee1e4;border-radius:12px;padding:8px 18px}
      .sinacin-checkbox-label{display:flex;gap:12px;align-items:flex-start;font-size:13.5px;line-height:1.6;padding:15px 0;color:#4b4246;cursor:pointer}
      .sinacin-checkbox-label+.sinacin-checkbox-label{border-top:1px solid #ede1e4}
      .sinacin-checkbox-label input{flex-shrink:0;width:18px;height:18px;margin:3px 0 0;accent-color:var(--sinacin-rojo)}
      .sinacin-privacidad{background:#f9f7f8;border-radius:9px;padding:13px 15px;color:#655d62;font-size:12.5px;line-height:1.5;margin:16px 0 22px}
      #sinacin-boton-enviar{display:block;width:100%;border:0;border-radius:11px;background:var(--sinacin-rojo);color:#fff;font-size:15px;font-weight:750;padding:16px;cursor:pointer;transition:background .2s}
      #sinacin-boton-enviar:hover:not(:disabled){background:#76232d}#sinacin-boton-enviar:disabled{opacity:.48;cursor:not-allowed}
      #sinacin-mensaje-general:not([hidden]){margin-top:17px;border-radius:10px;padding:14px;font-size:14px;line-height:1.5;background:#f9f0f2;color:#6f2631}
      #sinacin-mensaje-general[data-tipo="exito"]{background:#eef8f0;color:#225b36}
      @media(max-width:650px){.sinacin-formulario-container{padding:0 9px;margin:20px auto}.sinacin-formulario-container form{padding:24px 18px;border-radius:15px}.sinacin-grid,.sinacin-documento-grid{grid-template-columns:1fr}.sinacin-ejemplo{max-width:280px}.sinacin-formulario-header h2{font-size:22px}}
    </style>
    <script>
    (function(){
      function iniciar(){
        const f=document.getElementById('sinacin-formulario-afiliacion');if(!f||f.dataset.sinacinIniciado)return;f.dataset.sinacinIniciado='1';
        const empresa=f.querySelector('#rut_empresa'),rut=f.querySelector('#rut'),faena=f.querySelector('#faena_id'),msgEmpresa=f.querySelector('#sinacin-empresa-mensaje'),msgRut=f.querySelector('#sinacin-rut-mensaje'),msgFaena=f.querySelector('#sinacin-faena-mensaje'),boton=f.querySelector('#sinacin-boton-enviar'),general=f.querySelector('#sinacin-mensaje-general'),celular=f.querySelector('#celular'),digitos=f.querySelector('#celular_digitos'),cedula=f.querySelector('#cedula'),vista=f.querySelector('#sinacin-vista-previa'),imgVista=f.querySelector('#sinacin-imagen-previa'),confirmacion=document.getElementById('sinacin-confirmacion-final');
        const ajaxUrl=<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,nonceEmpresa=<?php echo wp_json_encode( wp_create_nonce( 'sinacin_validar_empresa' ) ); ?>;
        let empresaValida=false,rutValido=false,enviando=false,secuencia=0,temporizador=null,previewUrl=null;
        const bloqueados=Array.from(f.querySelectorAll('input:not(#rut_empresa):not([type="hidden"]),select'));
        function normalizar(v){return v.replace(/[.\-\s]/g,'').toUpperCase()}
        function validar(v){v=normalizar(v);if(!/^\d{1,8}[\dK]$/.test(v))return false;let suma=0,m=2;for(let i=v.length-2;i>=0;i--){suma+=Number(v[i])*m;m=m===7?2:m+1}const r=11-suma%11;return v.slice(-1)===(r===11?'0':r===10?'K':String(r))}
        function formato(v){v=normalizar(v);if(v.length<2)return v;return v.slice(0,-1).replace(/\B(?=(\d{3})+(?!\d))/g,'.')+'-'+v.slice(-1)}
        function mensaje(texto,tipo){general.hidden=!texto;general.textContent=texto||'';general.dataset.tipo=tipo||'error'}
        function estadoEmpresa(texto,estado){msgEmpresa.textContent=texto||'';msgEmpresa.dataset.estado=estado||''}
        function actualizar(){boton.disabled=enviando||!empresaValida||!rutValido||!f.checkValidity()}
        function bloquear(){bloqueados.forEach(e=>e.disabled=true);faena.innerHTML='<option value="">Primero valida el RUT de la empresa</option>';msgFaena.textContent='Selecciona la faena u obra donde trabajas.';rutValido=false;msgRut.textContent='';actualizar()}
        function habilitar(){bloqueados.forEach(e=>e.disabled=false);faena.disabled=true;actualizar()}
        function telefono(){digitos.value=digitos.value.replace(/\D/g,'').slice(0,8);celular.value=digitos.value.length===8?'+56 9'+digitos.value:''}
        async function cargarFaenas(){faena.disabled=true;faena.innerHTML='<option value="">Cargando faenas...</option>';msgFaena.textContent='';const n=secuencia;const datos=new URLSearchParams({action:'sinacin_obtener_faenas_formulario',nonce:nonceEmpresa,rut_empresa:empresa.value});try{const r=await fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:datos});const j=await r.json();if(n!==secuencia||!empresaValida)return;faena.innerHTML='<option value="">Selecciona una faena u obra</option>';if(!j.success||!Array.isArray(j.data.faenas)||!j.data.faenas.length){msgFaena.textContent=j.data?.message||'No hay faenas activas disponibles.';faena.disabled=true;actualizar();return}j.data.faenas.forEach(item=>{const o=document.createElement('option');o.value=String(item.id);o.textContent=item.nombre;faena.appendChild(o)});faena.disabled=false;msgFaena.textContent='Selecciona la faena donde trabajas.';actualizar()}catch(e){if(n!==secuencia)return;faena.innerHTML='<option value="">No se pudieron cargar las faenas</option>';msgFaena.textContent='Inténtalo nuevamente modificando el RUT de empresa.';actualizar()}}
        empresa.addEventListener('input',()=>{
          secuencia++;clearTimeout(temporizador);empresaValida=false;bloquear();estadoEmpresa('','');
          const valor=empresa.value.trim();
          if(!valor)return;
          if(!validar(valor)){estadoEmpresa('Ingresa un RUT de empresa válido.','error');return}
          empresa.value=formato(valor);
          const n=secuencia;
          estadoEmpresa('Verificando empresa...','cargando');
          temporizador=setTimeout(async()=>{
            const datos=new URLSearchParams({action:'sinacin_validar_empresa',nonce:nonceEmpresa,rut_empresa:empresa.value});
            try{
              const r=await fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:datos});
              if(!r.ok)throw new Error('Error de conexión');
              const j=await r.json();
              if(n!==secuencia)return;
              if(j.success){empresaValida=true;habilitar();estadoEmpresa('Empresa validada correctamente.','exito');cargarFaenas()}
              else{estadoEmpresa(j.data?.message||'Empresa no disponible.','error')}
            }catch(e){if(n===secuencia)estadoEmpresa('No fue posible validar la empresa. Inténtalo nuevamente.','error')}
          },450);
        });
        rut.addEventListener('input',()=>{rutValido=validar(rut.value);msgRut.textContent=rut.value?(rutValido?'RUT válido.':'El RUT ingresado no es válido.'):'';if(rutValido)rut.value=formato(rut.value);actualizar()});
        digitos.addEventListener('input',()=>{telefono();actualizar()});
        cedula.addEventListener('change',()=>{if(previewUrl){URL.revokeObjectURL(previewUrl);previewUrl=null}vista.hidden=true;const file=cedula.files?.[0];if(!file)return;if(file.size>5*1024*1024){mensaje('La fotografía no puede superar los 5 MB.');cedula.value='';actualizar();return}if(!['image/jpeg','image/png','image/webp'].includes(file.type)){mensaje('Selecciona una imagen JPG, PNG o WEBP.');cedula.value='';actualizar();return}previewUrl=URL.createObjectURL(file);imgVista.src=previewUrl;vista.hidden=false;mensaje('');actualizar()});
        f.addEventListener('input',actualizar);f.addEventListener('change',actualizar);
        f.addEventListener('submit',async ev=>{
          ev.preventDefault();
          if(enviando||f.hidden)return;
          mensaje('');telefono();
          if(!empresaValida||!rutValido||!f.checkValidity()){
            f.reportValidity();mensaje('Revisa los campos obligatorios, el RUT de empresa y tu RUT.');return;
          }
          enviando=true;actualizar();boton.textContent='Enviando solicitud...';
          const datos=new FormData(f);datos.append('sinacin_enviar_solicitud','1');
          try{
            const r=await fetch(window.location.href,{method:'POST',body:datos,headers:{'Accept':'application/json'}});
            if(!r.ok)throw new Error('Error al enviar');
            const j=await r.json();
            if(j.success){
              if(previewUrl){URL.revokeObjectURL(previewUrl);previewUrl=null}
              f.hidden=true;
              confirmacion.hidden=false;
              confirmacion.scrollIntoView({behavior:'smooth',block:'center'});
              return;
            }
            mensaje(j.data?.message||'No fue posible enviar la solicitud.');
          }catch(e){mensaje('Ocurrió un error al enviar la solicitud. Inténtalo nuevamente.')}
          finally{enviando=false;boton.textContent='Enviar solicitud de afiliación';actualizar()}
        });
        bloquear();
      }
      if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',iniciar);else iniciar();
    })();
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Devuelve faenas activas para la empresa validada.
 */
add_action( 'wp_ajax_sinacin_obtener_faenas_formulario', 'sinacin_obtener_faenas_formulario' );
add_action( 'wp_ajax_nopriv_sinacin_obtener_faenas_formulario', 'sinacin_obtener_faenas_formulario' );
function sinacin_obtener_faenas_formulario() {
    check_ajax_referer( 'sinacin_validar_empresa', 'nonce' );
    global $wpdb;
    $rut = isset( $_POST['rut_empresa'] ) ? sinacin_normalizar_rut_formulario( sanitize_text_field( wp_unslash( $_POST['rut_empresa'] ) ) ) : '';
    if ( ! sinacin_validar_rut_formulario( $rut ) ) {
        wp_send_json_error( array( 'message' => 'RUT de empresa inválido.' ) );
    }
    $empresas = $wpdb->prefix . 'sinacin_empresas';
    $faenas = $wpdb->prefix . 'sinacin_faenas';
    $empresa_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$empresas} WHERE REPLACE(REPLACE(REPLACE(UPPER(rut_empresa), '.', ''), '-', ''), ' ', '') = %s AND estado = 'ACTIVA' LIMIT 1", $rut ) );
    if ( ! $empresa_id ) {
        wp_send_json_error( array( 'message' => 'Empresa no disponible.' ) );
    }
    $filas = $wpdb->get_results( $wpdb->prepare( "SELECT id, nombre_faena FROM {$faenas} WHERE empresa_id = %d AND estado = 'ACTIVA' ORDER BY nombre_faena ASC", $empresa_id ) );
    $resultado = array();
    foreach ( (array) $filas as $fila ) {
        $resultado[] = array( 'id' => (int) $fila->id, 'nombre' => $fila->nombre_faena );
    }
    wp_send_json_success( array( 'faenas' => $resultado ) );
}


/**
 * ==========================================
 * PROCESAR SOLICITUD
 * ==========================================
 */

add_action(
    'init',
    'sinacin_procesar_solicitud'
);


function sinacin_procesar_solicitud() {

    if (
        ! isset(
            $_POST['sinacin_enviar_solicitud']
        )
    ) {

        return;

    }


    /**
     * ------------------------------------------
     * NONCE
     * ------------------------------------------
     */

    if (
        ! isset(
            $_POST['sinacin_nonce']
        )
        ||
        ! wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST['sinacin_nonce']
                )
            ),
            'sinacin_enviar_solicitud'
        )
    ) {

        sinacin_respuesta_error(
            'La sesión de seguridad no es válida. Recarga la página e inténtalo nuevamente.'
        );

    }


    /**
     * ------------------------------------------
     * OBTENER DATOS
     * ------------------------------------------
     */

    $rut_empresa =
        isset( $_POST['rut_empresa'] )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['rut_empresa']
                )
            )
            : '';


    $nombres =
        isset( $_POST['nombres'] )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['nombres']
                )
            )
            : '';


    $apellido_paterno =
        isset( $_POST['apellido_paterno'] )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['apellido_paterno']
                )
            )
            : '';


    $apellido_materno =
        isset( $_POST['apellido_materno'] )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['apellido_materno']
                )
            )
            : '';


    $rut =
        isset( $_POST['rut'] )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['rut']
                )
            )
            : '';


    $celular =
        isset( $_POST['celular'] )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['celular']
                )
            )
            : '';


    $correo =
        isset( $_POST['correo'] )
            ? sanitize_email(
                wp_unslash(
                    $_POST['correo']
                )
            )
            : '';


    $cargo =
        isset( $_POST['cargo'] )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['cargo']
                )
            )
            : '';


    $faena_id = isset( $_POST['faena_id'] ) ? absint( $_POST['faena_id'] ) : 0;

    $autoriza_descuento = isset( $_POST['autoriza_descuento'] ) ? 1 : 0;
    $reconoce_delegados = isset( $_POST['reconoce_delegados'] ) ? 1 : 0;

    $acepta_terminos =
        isset(
            $_POST['acepta_terminos']
        )
            ? 1
            : 0;


    /**
     * ------------------------------------------
     * NORMALIZAR RUT
     * ------------------------------------------
     */

    $rut_empresa =
        sinacin_normalizar_rut_formulario(
            $rut_empresa
        );


    $rut =
        sinacin_normalizar_rut_formulario(
            $rut
        );


    /**
     * ------------------------------------------
     * VALIDAR CAMPOS OBLIGATORIOS
     * ------------------------------------------
     */

    if (
        trim( $rut_empresa ) === ''
        ||
        trim( $nombres ) === ''
        ||
        trim( $apellido_paterno ) === ''
        ||
        trim( $apellido_materno ) === ''
        ||
        trim( $rut ) === ''
        ||
        trim( $celular ) === ''
        ||
        trim( $correo ) === ''
        ||
        trim( $cargo ) === ''
        ||
        $faena_id < 1
    ) {

        sinacin_respuesta_error(
            'Todos los campos son obligatorios.'
        );

    }


    /**
     * ------------------------------------------
     * VALIDAR RUT EMPRESA
     * ------------------------------------------
     */

    if (
        ! sinacin_validar_rut_formulario(
            $rut_empresa
        )
    ) {

        sinacin_respuesta_error(
            'El RUT de empresa ingresado no es válido.'
        );

    }


    /**
     * ------------------------------------------
     * VALIDAR RUT PERSONA
     * ------------------------------------------
     */

    if (
        ! sinacin_validar_rut_formulario(
            $rut
        )
    ) {

        sinacin_respuesta_error(
            'El RUT ingresado no es válido.'
        );

    }


    if ( ! preg_match( '/^\+56 9[0-9]{8}$/', $celular ) ) {
        sinacin_respuesta_error( 'Ingresa un celular válido con prefijo +56 9 y ocho dígitos.' );
    }

    /**
     * ------------------------------------------
     * VALIDAR CORREO
     * ------------------------------------------
     */

    if (
        ! is_email( $correo )
    ) {

        sinacin_respuesta_error(
            'El correo electrónico ingresado no es válido.'
        );

    }


    /**
     * ------------------------------------------
     * VALIDAR DECLARACIÓN
     * ------------------------------------------
     */

    if (
        $acepta_terminos !== 1 || $autoriza_descuento !== 1 || $reconoce_delegados !== 1
    ) {

        sinacin_respuesta_error(
            'Debes aceptar las tres declaraciones para continuar.'
        );

    }


    /**
     * ------------------------------------------
     * VALIDAR CÉDULA
     * ------------------------------------------
     */

    if (
        ! isset(
            $_FILES['cedula']
        )
        ||
        empty(
            $_FILES['cedula']['name']
        )
    ) {

        sinacin_respuesta_error(
            'Debes adjuntar una fotografía de tu cédula de identidad.'
        );

    }


    $archivo =
        $_FILES['cedula'];


    if (
        ! empty(
            $archivo['error']
        )
    ) {

        sinacin_respuesta_error(
            'Ocurrió un problema al cargar la fotografía de la cédula.'
        );

    }


    /**
     * ------------------------------------------
     * TAMAÑO MÁXIMO
     * ------------------------------------------
     */

    if (
        $archivo['size'] >
        5 * 1024 * 1024
    ) {

        sinacin_respuesta_error(
            'La fotografía de la cédula no puede superar los 5 MB.'
        );

    }


    /**
     * ------------------------------------------
     * MIME REAL
     * ------------------------------------------
     */

    $tipos_permitidos =
        array(
            'image/jpeg',
            'image/png',
            'image/webp',
        );


    $finfo =
        finfo_open(
            FILEINFO_MIME_TYPE
        );


    $mime =
        finfo_file(
            $finfo,
            $archivo['tmp_name']
        );


    finfo_close(
        $finfo
    );


    if (
        ! in_array(
            $mime,
            $tipos_permitidos,
            true
        )
    ) {

        sinacin_respuesta_error(
            'El archivo de cédula debe ser JPG, JPEG, PNG o WEBP.'
        );

    }


    /**
     * ------------------------------------------
     * TABLAS
     * ------------------------------------------
     */

    global $wpdb;


    $tabla_empresas =
        $wpdb->prefix .
        'sinacin_empresas';


    $tabla_personas =
        $wpdb->prefix .
        'sinacin_personas';


    $tabla_solicitudes =
        $wpdb->prefix .
        'sinacin_solicitudes';


    $tabla_afiliaciones =
        $wpdb->prefix .
        'sinacin_afiliaciones';


    $tabla_documentos =
        $wpdb->prefix .
        'sinacin_documentos';


    $tabla_historial =
        $wpdb->prefix .
        'sinacin_historial';


    /**
     * ------------------------------------------
     * BUSCAR EMPRESA ACTIVA
     *
     * AQUÍ ESTÁ LA CORRECCIÓN PRINCIPAL
     * ------------------------------------------
     */

    $empresa =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT id
                FROM {$tabla_empresas}
                WHERE REPLACE(
                        REPLACE(
                            REPLACE(
                                UPPER(rut_empresa),
                                '.',
                                ''
                            ),
                            '-',
                            ''
                        ),
                        ' ',
                        ''
                      ) = %s
                AND estado = 'ACTIVA'
                LIMIT 1
                ",
                $rut_empresa
            )
        );


    /**
     * ------------------------------------------
     * EMPRESA NO ENCONTRADA
     * ------------------------------------------
     */

    if ( ! $empresa ) {

        sinacin_respuesta_error(
            'No es posible continuar con el RUT de empresa ingresado.'
        );

    }


    $empresa_id =
        (int) $empresa->id;

    $tabla_faenas = $wpdb->prefix . 'sinacin_faenas';
    $faena_valida = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$tabla_faenas} WHERE id = %d AND empresa_id = %d AND estado = 'ACTIVA' LIMIT 1",
        $faena_id, $empresa_id
    ) );
    if ( ! $faena_valida ) {
        sinacin_respuesta_error( 'Debes seleccionar una faena activa de la empresa.' );
    }


    /**
     * ------------------------------------------
     * BUSCAR PERSONA
     * ------------------------------------------
     */

    $persona =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT *
                FROM {$tabla_personas}
                WHERE rut = %s
                LIMIT 1
                ",
                $rut
            )
        );


    /**
     * ------------------------------------------
     * VALIDAR AFILIACIÓN ACTIVA
     * ------------------------------------------
     */

    if ( $persona ) {

        $afiliacion_activa =
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT id
                    FROM {$tabla_afiliaciones}
                    WHERE persona_id = %d
                    AND estado = 'ACTIVA'
                    LIMIT 1
                    ",
                    $persona->id
                )
            );


        if (
            $afiliacion_activa
        ) {

            sinacin_respuesta_error(
                'Ya existe una afiliación vigente asociada a este RUT.'
            );

        }

    }


    /**
     * ------------------------------------------
     * VALIDAR SOLICITUD PENDIENTE
     * ------------------------------------------
     */

    if ( $persona ) {

        $solicitud_pendiente =
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT id
                    FROM {$tabla_solicitudes}
                    WHERE persona_id = %d
                    AND empresa_id = %d
                    AND estado = 'PENDIENTE'
                    LIMIT 1
                    ",
                    $persona->id,
                    $empresa_id
                )
            );


        if (
            $solicitud_pendiente
        ) {

            sinacin_respuesta_error(
                'Ya existe una solicitud pendiente asociada a este RUT.'
            );

        }

    }


    /**
     * ------------------------------------------
     * CARGAR FUNCIONES WORDPRESS
     * ------------------------------------------
     */

    require_once ABSPATH .
        'wp-admin/includes/file.php';


    require_once ABSPATH .
        'wp-admin/includes/media.php';


    require_once ABSPATH .
        'wp-admin/includes/image.php';


    /**
     * ------------------------------------------
     * SUBIR ARCHIVO
     * ------------------------------------------
     */

    $upload_overrides =
        array(
            'test_form' => false,

            'mimes' =>
                array(
                    'jpg|jpeg|jpe' =>
                        'image/jpeg',

                    'png' =>
                        'image/png',

                    'webp' =>
                        'image/webp',
                ),
        );


    $archivo_subido =
        wp_handle_upload(
            $archivo,
            $upload_overrides
        );


    if (
        isset(
            $archivo_subido['error']
        )
    ) {

        sinacin_respuesta_error(
            'No fue posible guardar la fotografía de la cédula.'
        );

    }


    /**
     * ------------------------------------------
     * CREAR ATTACHMENT
     * ------------------------------------------
     */

    $attachment =
        array(
            'post_mime_type' =>
                $archivo_subido['type'],

            'post_title' =>
                sanitize_file_name(
                    pathinfo(
                        $archivo['name'],
                        PATHINFO_FILENAME
                    )
                ),

            'post_content' => '',

            'post_status' =>
                'inherit',
        );


    $attachment_id =
        wp_insert_attachment(
            $attachment,
            $archivo_subido['file']
        );


    if (
        is_wp_error(
            $attachment_id
        )
    ) {

        if (
            file_exists(
                $archivo_subido['file']
            )
        ) {

            @unlink(
                $archivo_subido['file']
            );

        }


        sinacin_respuesta_error(
            'No fue posible registrar la fotografía de la cédula.'
        );

    }


    /**
     * ------------------------------------------
     * GENERAR METADATA
     * ------------------------------------------
     */

    $attachment_metadata =
        wp_generate_attachment_metadata(
            $attachment_id,
            $archivo_subido['file']
        );


    if (
        ! empty(
            $attachment_metadata
        )
    ) {

        wp_update_attachment_metadata(
            $attachment_id,
            $attachment_metadata
        );

    }


    /**
     * ------------------------------------------
     * TRANSACCIÓN
     * ------------------------------------------
     */

    $wpdb->query(
        'START TRANSACTION'
    );


    try {


        /**
         * --------------------------------------
         * CREAR / ACTUALIZAR PERSONA
         * --------------------------------------
         */

        if (
            $persona
        ) {

            $resultado_persona =
                $wpdb->update(
                    $tabla_personas,

                    array(

                        'nombres' =>
                            $nombres,

                        'apellido_paterno' =>
                            $apellido_paterno,

                        'apellido_materno' =>
                            $apellido_materno,

                        'celular' =>
                            $celular,

                        'correo' =>
                            $correo,

                        'cargo' =>
                            $cargo,

                        'fecha_actualizacion' =>
                            current_time(
                                'mysql'
                            ),
                    ),

                    array(
                        'id' =>
                            $persona->id,
                    ),

                    array(
                        '%s',
                        '%s',
                        '%s',
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
                $resultado_persona === false
            ) {

                throw new Exception(
                    'No fue posible actualizar los datos de la persona.'
                );

            }


            $persona_id =
                (int) $persona->id;

        }
        else {

            $resultado_persona =
                $wpdb->insert(
                    $tabla_personas,

                    array(

                        'nombres' =>
                            $nombres,

                        'apellido_paterno' =>
                            $apellido_paterno,

                        'apellido_materno' =>
                            $apellido_materno,

                        'rut' =>
                            $rut,

                        'celular' =>
                            $celular,

                        'correo' =>
                            $correo,

                        'cargo' =>
                            $cargo,

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
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                    )
                );


            if (
                $resultado_persona === false
            ) {

                throw new Exception(
                    'No fue posible registrar los datos de la persona.'
                );

            }


            $persona_id =
                (int) $wpdb->insert_id;

        }


        /**
         * --------------------------------------
         * REGISTRAR DOCUMENTO
         * --------------------------------------
         */

        $resultado_documento =
            $wpdb->insert(
                $tabla_documentos,

                array(

                    'persona_id' =>
                        $persona_id,

                    'tipo_documento' =>
                        'CEDULA_IDENTIDAD',

                    'archivo_id' =>
                        $attachment_id,

                    'nombre_archivo' =>
                        sanitize_file_name(
                            $archivo['name']
                        ),

                    'estado' =>
                        'VIGENTE',

                    'fecha_subida' =>
                        current_time(
                            'mysql'
                        ),
                ),

                array(
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                )
            );


        if (
            $resultado_documento === false
        ) {

            throw new Exception(
                'No fue posible registrar el documento.'
            );

        }


        /**
         * Guardamos inmediatamente
         * el ID del documento.
         */

        $documento_id =
            (int) $wpdb->insert_id;


        /**
         * --------------------------------------
         * REGISTRAR SOLICITUD
         * --------------------------------------
         */

        $resultado_solicitud =
            $wpdb->insert(
                $tabla_solicitudes,

                array(

                    'persona_id' =>
                        $persona_id,

                    'empresa_id' =>
                        $empresa_id,

                    'faena_id' =>
                        $faena_id,

                    'estado' =>
                        'PENDIENTE',

                    'acepta_terminos' =>
                        1,

                    'version_terminos' =>
                        SINACIN_VERSION_TERMINOS,

                    'fecha_aceptacion' =>
                        current_time(
                            'mysql'
                        ),

                    'fecha_solicitud' =>
                        current_time(
                            'mysql'
                        ),
                ),

                array(
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                )
            );


        if (
            $resultado_solicitud === false
        ) {

            throw new Exception(
                'No fue posible registrar la solicitud.'
            );

        }


        $solicitud_id =
            (int) $wpdb->insert_id;


        /**
         * --------------------------------------
         * HISTORIAL
         * --------------------------------------
         */

        $ip =
            isset(
                $_SERVER['REMOTE_ADDR']
            )
                ? sanitize_text_field(
                    wp_unslash(
                        $_SERVER['REMOTE_ADDR']
                    )
                )
                : null;


        $resultado_historial =
            $wpdb->insert(
                $tabla_historial,

                array(

                    'usuario_id' =>
                        null,

                    'entidad' =>
                        'SOLICITUD',

                    'entidad_id' =>
                        $solicitud_id,

                    'accion' =>
                        'CREAR',

                    'descripcion' =>
                        'Solicitud de afiliación creada desde formulario público.',

                    'ip' =>
                        $ip,

                    'fecha' =>
                        current_time(
                            'mysql'
                        ),
                ),

                array(
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                )
            );


        if (
            $resultado_historial === false
        ) {

            throw new Exception(
                'No fue posible registrar el historial.'
            );

        }


        /**
         * --------------------------------------
         * COMMIT
         * --------------------------------------
         */

        $wpdb->query(
            'COMMIT'
        );


        /**
         * --------------------------------------
         * RESPUESTA
         * --------------------------------------
         */

        sinacin_respuesta_exito(
            'Tu solicitud de afiliación fue recibida correctamente. Será revisada por SINACIN.'
        );

    }
    catch (
        Exception $e
    ) {

        /**
         * --------------------------------------
         * ROLLBACK
         * --------------------------------------
         */

        $wpdb->query(
            'ROLLBACK'
        );


        /**
         * El archivo ya fue creado
         * fuera de la transacción.
         *
         * Lo eliminamos si la BD falla.
         */

        if (
            ! empty(
                $attachment_id
            )
        ) {

            wp_delete_attachment(
                $attachment_id,
                true
            );

        }


        sinacin_respuesta_error(
            'No fue posible registrar la solicitud. Inténtalo nuevamente.'
        );

    }

}


/**
 * ==========================================
 * NORMALIZAR RUT
 * ==========================================
 */

function sinacin_normalizar_rut_formulario(
    $rut
) {

    $rut =
        strtoupper(
            trim(
                $rut
            )
        );


    $rut =
        str_replace(
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
 * VALIDAR RUT
 * ==========================================
 */

function sinacin_validar_rut_formulario(
    $rut
) {

    $rut =
        sinacin_normalizar_rut_formulario(
            $rut
        );


    /**
     * Debe contener solamente
     * números y K como DV.
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
        strlen(
            $cuerpo
        ) < 1
    ) {

        return false;

    }


    $suma = 0;

    $multiplicador = 2;


    for (
        $i =
            strlen(
                $cuerpo
            ) - 1;

        $i >= 0;

        $i--
    ) {

        $suma +=
            intval(
                $cuerpo[$i]
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

        $dv_calculado =
            '0';

    }
    elseif (
        $resultado === 10
    ) {

        $dv_calculado =
            'K';

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
 * RESPUESTA ERROR
 * ==========================================
 */

function sinacin_respuesta_error(
    $mensaje
) {

    if (
        wp_doing_ajax()
        ||
        isset(
            $_POST['sinacin_enviar_solicitud']
        )
    ) {

        wp_send_json_error(
            array(
                'message' =>
                    $mensaje,
            )
        );

    }


    wp_die(
        esc_html(
            $mensaje
        )
    );

}


/**
 * ==========================================
 * RESPUESTA ÉXITO
 * ==========================================
 */

function sinacin_respuesta_exito(
    $mensaje
) {

    wp_send_json_success(
        array(
            'message' =>
                $mensaje,
        )
    );

}