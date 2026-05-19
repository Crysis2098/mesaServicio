<div id="cam_titulo">
    <span>Mesa de Servicio</span>
</div>

<div id="contenido_formulario">
    <p class="descripcion_modulo">
        Levanta un ticket para que el equipo de desarrollo lo revise, lo clasifique y lo asigne a un responsable.
    </p>

    <div id="mensaje_ticket" class="mensaje_ajax"></div>

    <form id="form_reportar_ticket" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="id_empleado" value="<?php echo (int)$id_empleado; ?>">

        <section class="form-card">
            <div class="form-card-header">
                <div>
                    <h2>Reportar incidencia / ticket</h2>
                    <p>Formulario para ejecutivos, administrativos, supervisores y usuarios internos.</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-field">
                    <label for="nombre_reporta">Nombre de quien reporta</label>
                    <input type="text" name="nombre_reporta" id="nombre_reporta" class="caja-inputs" value="<?php echo htmlspecialchars($nombre_reporta); ?>" required>
                </div>

                <div class="form-field">
                    <label for="area_reporta">Área / departamento</label>
                    <input type="text" name="area_reporta" id="area_reporta" class="caja-inputs" placeholder="Ej. Ventas, Administración, Supervisor">
                </div>

                <div class="form-field">
                    <label for="categoria">Categoría</label>
                    <select name="categoria" id="categoria" class="caja-select" required>
                        <option value="" selected disabled>Seleccionar</option>
                        <option value="Error de sistema">Error de sistema</option>
                        <option value="Solicitud de cambio">Solicitud de cambio</option>
                        <option value="Reporte / consulta">Reporte / consulta</option>
                        <option value="Acceso / permisos">Acceso / permisos</option>
                        <option value="Datos / información">Datos / información</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="prioridad">Prioridad</label>
                    <select name="prioridad" id="prioridad" class="caja-select" required>
                        <option value="Media" selected>Media</option>
                        <option value="Alta">Alta</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="fuente_solicitud_ticket">Fuente de solicitud</label>
                    <select name="fuente_solicitud" id="fuente_solicitud_ticket" class="caja-select">
                        <option value="Mesa de Servicio" selected>Mesa de Servicio</option>
                        <option value="Email">Email</option>
                        <option value="Chat">Chat</option>
                        <option value="Llamada">Llamada</option>
                        <option value="Presencial">Presencial</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="modulo_sistema">Módulo / sistema afectado</label>
                    <input type="text" name="modulo_sistema" id="modulo_sistema" class="caja-inputs" placeholder="Ej. Activaciones, Ciudades, Reportes">
                </div>

                <div class="form-field form-field-full">
                    <label for="titulo_ticket">Título del ticket</label>
                    <input type="text" name="titulo_ticket" id="titulo_ticket" class="caja-inputs" placeholder="Ej. No permite guardar una activación" required>
                </div>

                <div class="form-field form-field-full">
                    <label for="descripcion_ticket">Descripción del problema o solicitud</label>
                    <textarea name="descripcion_ticket" id="descripcion_ticket" class="caja-inputs" rows="5" placeholder="Explica qué ocurrió, desde cuándo pasa, qué usuario lo reporta, pantalla afectada y pasos para reproducirlo." required></textarea>
                </div>

                <div class="form-field form-field-full">
                    <label for="captura_ticket">Evidencia / captura</label>
                    <input type="file" name="captura_ticket" id="captura_ticket" class="caja-inputs" accept="image/*">
                </div>

                <div class="acciones_formulario">
                    <button type="submit" class="btn_principal">Enviar ticket</button>
                </div>
            </div>
        </section>
    </form>
</div>

<script src="JS/mesa_trabajo.js?v=mesa-servicio-1"></script>
