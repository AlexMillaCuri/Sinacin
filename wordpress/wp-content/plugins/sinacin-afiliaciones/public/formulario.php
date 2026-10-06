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
     * OBTENER FAENAS ACTIVAS DE LA EMPRESA
     * ------------------------------------------
     */

    $tabla_faenas =
        $wpdb->prefix . 'sinacin_faenas';

    $faenas = $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT
                id,
                nombre_faena
            FROM {$tabla_faenas}
            WHERE empresa_id = %d
            AND estado = 'ACTIVA'
            ORDER BY nombre_faena ASC
            ",
            (int) $empresa->id
        ),
        ARRAY_A
    );


    /**
     * ------------------------------------------
     * EMPRESA VÁLIDA
     * ------------------------------------------
     */

    wp_send_json_success(
        array(
            'message' =>
                'RUT de empresa validado correctamente.',

            'empresa_id' =>
                (int) $empresa->id,

            'faenas' =>
                is_array( $faenas )
                    ? $faenas
                    : array(),
        )
    );

}


/**
 * ==========================================
 * MOSTRAR FORMULARIO
 * ==========================================
 */

function sinacin_mostrar_formulario() {

    ob_start();

    ?>

    <div class="sinacin-formulario-container">

        <form
            id="sinacin-formulario-afiliacion"
            method="post"
            enctype="multipart/form-data"
            novalidate
        >

            <?php

            wp_nonce_field(
                'sinacin_enviar_solicitud',
                'sinacin_nonce'
            );

            ?>


            <!-- ==========================================
                 RUT EMPRESA
                 ========================================== -->

            <div class="sinacin-campo">

                <label for="rut_empresa">
                    RUT de empresa *
                </label>

                <input
                    type="text"
                    id="rut_empresa"
                    name="rut_empresa"
                    placeholder="Ej: 76.123.456-7"
                    maxlength="12"
                    required
                    autocomplete="off"
                >

                <small id="sinacin-empresa-mensaje"></small>

            </div>


            <!-- ==========================================
                 FAENA / OBRA
                 ========================================== -->

            <div class="sinacin-campo">

                <label for="faena_id">
                    Faena / Obra *
                </label>

                <select
                    id="faena_id"
                    name="faena_id"
                    required
                    disabled
                >
                    <option value="">
                        Selecciona una faena / obra
                    </option>
                </select>

                <small id="sinacin-faena-mensaje"></small>

            </div>


            <!-- ==========================================
                 NOMBRES
                 ========================================== -->

            <div class="sinacin-campo">

                <label for="nombres">
                    Nombres *
                </label>

                <input
                    type="text"
                    id="nombres"
                    name="nombres"
                    maxlength="100"
                    required
                    disabled
                >

            </div>


            <!-- ==========================================
                 APELLIDO PATERNO
                 ========================================== -->

            <div class="sinacin-campo">

                <label for="apellido_paterno">
                    Apellido paterno *
                </label>

                <input
                    type="text"
                    id="apellido_paterno"
                    name="apellido_paterno"
                    maxlength="100"
                    required
                    disabled
                >

            </div>


            <!-- ==========================================
                 APELLIDO MATERNO
                 ========================================== -->

            <div class="sinacin-campo">

                <label for="apellido_materno">
                    Apellido materno *
                </label>

                <input
                    type="text"
                    id="apellido_materno"
                    name="apellido_materno"
                    maxlength="100"
                    required
                    disabled
                >

            </div>


            <!-- ==========================================
                 RUT PERSONA
                 ========================================== -->

            <div class="sinacin-campo">

                <label for="rut">
                    RUT *
                </label>

                <input
                    type="text"
                    id="rut"
                    name="rut"
                    placeholder="Ej: 12.345.678-5"
                    maxlength="12"
                    required
                    disabled
                    autocomplete="off"
                >

                <small id="sinacin-rut-mensaje"></small>

            </div>


            <!-- ==========================================
                 CELULAR
                 ========================================== -->

            <div class="sinacin-campo">

                <label for="celular">
                    Celular *
                </label>

                <input
                    type="tel"
                    id="celular"
                    name="celular"
                    maxlength="30"
                    required
                    disabled
                >

            </div>


            <!-- ==========================================
                 CORREO
                 ========================================== -->

            <div class="sinacin-campo">

                <label for="correo">
                    Correo electrónico *
                </label>

                <input
                    type="email"
                    id="correo"
                    name="correo"
                    maxlength="190"
                    required
                    disabled
                >

            </div>


            <!-- ==========================================
                 CARGO
                 ========================================== -->

            <div class="sinacin-campo">

                <label for="cargo">
                    Cargo *
                </label>

                <input
                    type="text"
                    id="cargo"
                    name="cargo"
                    maxlength="150"
                    required
                    disabled
                >

            </div>


            <!-- ==========================================
                 CÉDULA
                 ========================================== -->

            <div class="sinacin-campo">

                <label for="cedula">
                    Fotografía de cédula de identidad *
                </label>

                <input
                    type="file"
                    id="cedula"
                    name="cedula"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    required
                    disabled
                >

                <small>
                    Formatos permitidos: JPG, JPEG, PNG o WEBP.
                    Tamaño máximo: 5 MB.
                </small>

            </div>


            <!-- ==========================================
                 DECLARACIÓN LEGAL
                 ========================================== -->

            <div class="sinacin-declaracion">

                <label
                    class="sinacin-checkbox-label"
                    for="acepta_terminos"
                >

                    <input
                        type="checkbox"
                        id="acepta_terminos"
                        name="acepta_terminos"
                        value="1"
                        required
                        disabled
                    >

                    <span>

                        En conformidad con el Código del Trabajo, declaro libre y
                        voluntariamente mi decisión de afiliarme al Sindicato
                        Interempresa Nacional de la Construcción Industrial y
                        Actividades Anexas - SINACIN.

                        <br><br>

                        Autorizo expresamente a mi empleador para descontar de mis
                        remuneraciones la cuota sindical ordinaria y aquellas
                        extraordinarias aprobadas conforme a la ley y a los estatutos
                        sindicales, debiendo enterarse dichos montos al sindicato.

                    </span>

                </label>

            </div>


            <!-- ==========================================
                 BOTÓN
                 ========================================== -->

            <div class="sinacin-boton-container">

                <button
                    type="submit"
                    id="sinacin-boton-enviar"
                    disabled
                >
                    Enviar solicitud de afiliación
                </button>

            </div>


            <!-- ==========================================
                 MENSAJE
                 ========================================== -->

            <div
                id="sinacin-mensaje-general"
                role="alert"
                aria-live="polite"
            ></div>

        </form>

    </div>


    <style>

        .sinacin-formulario-container {
            max-width: 700px;
            margin: 30px auto;
        }

        .sinacin-campo {
            margin-bottom: 20px;
        }

        .sinacin-campo label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .sinacin-campo input[type="text"],
        .sinacin-campo input[type="email"],
        .sinacin-campo input[type="tel"],
        .sinacin-campo input[type="file"],
        .sinacin-campo select {
            width: 100%;
            box-sizing: border-box;
            padding: 11px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .sinacin-campo input:disabled,
        .sinacin-campo select:disabled {
            background: #f4f4f4;
            cursor: not-allowed;
        }

        .sinacin-campo small {
            display: block;
            margin-top: 6px;
            font-size: 13px;
        }

        #sinacin-empresa-mensaje,
        #sinacin-rut-mensaje {
            min-height: 18px;
        }

        .sinacin-declaracion {
            margin: 25px 0;
            padding: 18px;
            background: #fafafa;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .sinacin-checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            line-height: 1.5;
        }

        .sinacin-checkbox-label input {
            margin-top: 5px;
            flex-shrink: 0;
        }

        .sinacin-boton-container {
            margin-top: 25px;
        }

        #sinacin-boton-enviar {
            padding: 12px 22px;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        #sinacin-boton-enviar:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        #sinacin-mensaje-general {
            margin-top: 20px;
            font-weight: 600;
        }

    </style>


    <script>

    document.addEventListener(
        'DOMContentLoaded',
        function() {

            const formulario =
                document.getElementById(
                    'sinacin-formulario-afiliacion'
                );


            const rutEmpresa =
                document.getElementById(
                    'rut_empresa'
                );


            const rutPersona =
                document.getElementById(
                    'rut'
                );


            const mensajeEmpresa =
                document.getElementById(
                    'sinacin-empresa-mensaje'
                );


            const mensajeRut =
                document.getElementById(
                    'sinacin-rut-mensaje'
                );


            const faenaSelect =
                document.getElementById(
                    'faena_id'
                );


            const mensajeFaena =
                document.getElementById(
                    'sinacin-faena-mensaje'
                );


            const botonEnviar =
                document.getElementById(
                    'sinacin-boton-enviar'
                );


            const mensajeGeneral =
                document.getElementById(
                    'sinacin-mensaje-general'
                );


            const camposBloqueables =
                formulario.querySelectorAll(
                    'input:not(#rut_empresa), select:not(#rut_empresa)'
                );


            let empresaValida = false;

            let rutPersonaValido = false;


            /**
             * ==========================================
             * NORMALIZAR RUT
             * ==========================================
             */

            function normalizarRut(rut) {

                return rut
                    .replace(/\./g, '')
                    .replace(/-/g, '')
                    .replace(/\s/g, '')
                    .toUpperCase();

            }


            /**
             * ==========================================
             * VALIDAR RUT
             * ==========================================
             */

            function validarRut(rut) {

                rut =
                    normalizarRut(rut);


                if (
                    rut.length < 2 ||
                    rut.length > 9
                ) {

                    return false;

                }


                const cuerpo =
                    rut.slice(
                        0,
                        -1
                    );


                const dv =
                    rut.slice(-1);


                if (
                    !/^\d+$/.test(
                        cuerpo
                    )
                ) {

                    return false;

                }


                let suma = 0;

                let multiplicador = 2;


                for (
                    let i =
                        cuerpo.length - 1;

                    i >= 0;

                    i--
                ) {

                    suma +=
                        parseInt(
                            cuerpo.charAt(i),
                            10
                        ) *
                        multiplicador;


                    multiplicador++;


                    if (
                        multiplicador > 7
                    ) {

                        multiplicador = 2;

                    }

                }


                const resto =
                    suma % 11;


                const resultado =
                    11 - resto;


                let dvCalculado;


                if (
                    resultado === 11
                ) {

                    dvCalculado = '0';

                }
                else if (
                    resultado === 10
                ) {

                    dvCalculado = 'K';

                }
                else {

                    dvCalculado =
                        String(
                            resultado
                        );

                }


                return (
                    dv ===
                    dvCalculado
                );

            }


            /**
             * ==========================================
             * FORMATEAR RUT
             * ==========================================
             */

            function formatearRut(rut) {

                rut =
                    normalizarRut(rut);


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


                let cuerpoFormateado = '';

                let contador = 0;


                for (
                    let i =
                        cuerpo.length - 1;

                    i >= 0;

                    i--
                ) {

                    cuerpoFormateado =
                        cuerpo.charAt(i) +
                        cuerpoFormateado;


                    contador++;


                    if (
                        contador === 3 &&
                        i !== 0
                    ) {

                        cuerpoFormateado =
                            '.' +
                            cuerpoFormateado;

                        contador = 0;

                    }

                }


                return (
                    cuerpoFormateado +
                    '-' +
                    dv
                );

            }


            /**
             * ==========================================
             * HABILITAR CAMPOS
             * ==========================================
             */

            function habilitarFormulario() {

                camposBloqueables.forEach(
                    function(campo) {

                        campo.disabled = false;

                    }
                );

            }


            /**
             * ==========================================
             * DESHABILITAR CAMPOS
             * ==========================================
             */

            function deshabilitarFormulario() {

                camposBloqueables.forEach(
                    function(campo) {

                        campo.disabled = true;

                    }
                );


                botonEnviar.disabled = true;

                rutPersonaValido = false;

            }


            /**
             * ==========================================
             * CARGAR FAENAS
             * ==========================================
             */

            function cargarFaenas(faenas) {

                faenaSelect.innerHTML = '';

                faenaSelect.disabled = true;

                mensajeFaena.textContent = '';

                const opcionInicial = document.createElement('option');

                opcionInicial.value = '';
                opcionInicial.textContent =
                    'Selecciona una faena / obra';

                faenaSelect.appendChild(opcionInicial);

                if ( ! Array.isArray( faenas ) || faenas.length === 0 ) {

                    mensajeFaena.textContent =
                        'La empresa no tiene faenas u obras activas registradas.';

                    return;

                }

                faenas.forEach(function(faena) {

                    const opcion = document.createElement('option');

                    opcion.value = faena.id;
                    opcion.textContent = faena.nombre_faena;

                    faenaSelect.appendChild(opcion);

                });

                faenaSelect.disabled = false;

            }


            /**
             * ==========================================
             * VALIDAR RUT EMPRESA
             * ==========================================
             */

            let temporizadorEmpresa = null;


            rutEmpresa.addEventListener(
                'input',
                function() {

                    empresaValida = false;

                    deshabilitarFormulario();

                    faenaSelect.innerHTML =
                        '<option value="">Selecciona una faena / obra</option>';
                    faenaSelect.disabled = true;
                    mensajeFaena.textContent = '';

                    mensajeEmpresa.textContent = '';

                    const valor =
                        rutEmpresa.value.trim();


                    if (
                        !valor
                    ) {

                        return;

                    }


                    if (
                        !validarRut(valor)
                    ) {

                        mensajeEmpresa.textContent =
                            'El RUT de empresa no es válido.';

                        return;

                    }


                    rutEmpresa.value =
                        formatearRut(
                            valor
                        );


                    clearTimeout(
                        temporizadorEmpresa
                    );


                    temporizadorEmpresa =
                        setTimeout(
                            function() {

                                const datos =
                                    new URLSearchParams();


                                datos.append(
                                    'action',
                                    'sinacin_validar_empresa'
                                );


                                datos.append(
                                    'nonce',
                                    '<?php echo esc_js( wp_create_nonce( 'sinacin_validar_empresa' ) ); ?>'
                                );


                                datos.append(
                                    'rut_empresa',
                                    rutEmpresa.value
                                );


                                fetch(
                                    '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                                    {
                                        method: 'POST',

                                        headers: {
                                            'Content-Type':
                                                'application/x-www-form-urlencoded'
                                        },

                                        body:
                                            datos.toString()
                                    }
                                )
                                .then(
                                    response =>
                                        response.json()
                                )
                                .then(
                                    function(data) {

                                        if (
                                            data.success
                                        ) {

                                            empresaValida = true;

                                            habilitarFormulario();

                                            cargarFaenas(
                                                data.data.faenas || []
                                            );

                                            mensajeEmpresa.textContent =
                                                'RUT de empresa validado correctamente.';

                                        }
                                        else {

                                            empresaValida = false;

                                            deshabilitarFormulario();


                                            mensajeEmpresa.textContent =
                                                data.data.message ||
                                                'No es posible continuar con el RUT de empresa ingresado.';

                                        }

                                    }
                                )
                                .catch(
                                    function() {

                                        empresaValida = false;

                                        deshabilitarFormulario();


                                        mensajeEmpresa.textContent =
                                            'No fue posible validar el RUT de empresa. Inténtalo nuevamente.';

                                    }
                                );

                            },
                            500
                        );

                }
            );


            /**
             * ==========================================
             * VALIDAR RUT PERSONA
             * ==========================================
             */

            rutPersona.addEventListener(
                'input',
                function() {

                    rutPersonaValido = false;


                    const valor =
                        rutPersona.value.trim();


                    if (
                        !valor
                    ) {

                        mensajeRut.textContent = '';

                        actualizarEstadoBoton();

                        return;

                    }


                    if (
                        !validarRut(valor)
                    ) {

                        mensajeRut.textContent =
                            'El RUT ingresado no es válido.';

                        actualizarEstadoBoton();

                        return;

                    }


                    rutPersona.value =
                        formatearRut(
                            valor
                        );


                    rutPersonaValido = true;


                    mensajeRut.textContent =
                        'RUT válido.';


                    actualizarEstadoBoton();

                }
            );


            /**
             * ==========================================
             * ACTUALIZAR BOTÓN
             * ==========================================
             */

            function actualizarEstadoBoton() {

                botonEnviar.disabled =
                    !(
                        empresaValida &&
                        rutPersonaValido &&
                        formulario.checkValidity()
                    );

            }


            /**
             * ==========================================
             * CAMBIOS EN FORMULARIO
             * ==========================================
             */

            formulario.addEventListener(
                'input',
                function() {

                    actualizarEstadoBoton();

                }
            );


            formulario.addEventListener(
                'change',
                function() {

                    actualizarEstadoBoton();

                }
            );


            /**
             * ==========================================
             * ENVÍO
             * ==========================================
             */

            formulario.addEventListener(
                'submit',
                function(event) {

                    event.preventDefault();


                    mensajeGeneral.textContent = '';


                    if (
                        !empresaValida
                    ) {

                        mensajeGeneral.textContent =
                            'Debes validar primero el RUT de empresa.';

                        return;

                    }


                    if (
                        !rutPersonaValido
                    ) {

                        mensajeGeneral.textContent =
                            'Debes ingresar un RUT válido.';

                        rutPersona.focus();

                        return;

                    }


                    if (
                        !formulario.checkValidity()
                    ) {

                        formulario.reportValidity();

                        return;

                    }


                    botonEnviar.disabled = true;


                    mensajeGeneral.textContent =
                        'Enviando solicitud...';


                    const formData =
                        new FormData(
                            formulario
                        );


                    formData.append(
                        'sinacin_enviar_solicitud',
                        '1'
                    );


                    fetch(
                        window.location.href,
                        {
                            method: 'POST',

                            body:
                                formData
                        }
                    )
                    .then(
                        response =>
                            response.json()
                    )
                    .then(
                        function(data) {

                            if (
                                data.success
                            ) {

                                mensajeGeneral.textContent =
                                    data.data.message;


                                formulario.reset();


                                empresaValida = false;

                                rutPersonaValido = false;


                                deshabilitarFormulario();


                                mensajeEmpresa.textContent = '';

                                mensajeRut.textContent = '';

                                faenaSelect.innerHTML =
                                    '<option value="">Selecciona una faena / obra</option>';
                                faenaSelect.disabled = true;
                                mensajeFaena.textContent = '';

                            }
                            else {

                                mensajeGeneral.textContent =
                                    data.data.message ||
                                    'No fue posible enviar la solicitud.';


                                botonEnviar.disabled = false;

                            }

                        }
                    )
                    .catch(
                        function() {

                            mensajeGeneral.textContent =
                                'Ocurrió un error al enviar la solicitud. Inténtalo nuevamente.';


                            botonEnviar.disabled = false;

                        }
                    );

                }
            );

        }
    );

    </script>

    <?php

    return ob_get_clean();

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


    $faena_id =
        isset( $_POST['faena_id'] )
            ? absint( $_POST['faena_id'] )
            : 0;


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
        $faena_id <= 0
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
        $acepta_terminos !== 1
    ) {

        sinacin_respuesta_error(
            'Debes aceptar la declaración para continuar.'
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


    $tabla_faenas =
        $wpdb->prefix .
        'sinacin_faenas';


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


    /**
     * ------------------------------------------
     * VALIDAR FAENA ACTIVA
     * ------------------------------------------
     */

    $faena =
        $wpdb->get_row(
            $wpdb->prepare(
                "
                SELECT id
                FROM {$tabla_faenas}
                WHERE id = %d
                AND empresa_id = %d
                AND estado = 'ACTIVA'
                LIMIT 1
                ",
                $faena_id,
                $empresa_id
            )
        );


    if ( ! $faena ) {

        sinacin_respuesta_error(
            'La faena u obra seleccionada no es válida para la empresa ingresada.'
        );

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