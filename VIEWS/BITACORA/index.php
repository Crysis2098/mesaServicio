<div id="cam_titulo">
    <span>Bitácora - Desarrollo</span>
</div>

<div id="contenido_formulario">
    <p class="descripcion_modulo">
        Registro interno de incidencias y actividades capturadas directamente por el equipo de desarrollo.
    </p>

    <div id="mensaje_ajax" class="mensaje_ajax"></div>

    <form id="form_guardar_incidencia" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="id_empleado" id="id_empleado" value="<?php echo (int)$id_empleado; ?>">
        <input type="hidden" name="fecha_captura" value="<?php echo htmlspecialchars($fecha_captura); ?>">
        <input type="hidden" name="nombre_captura_aux" value="<?php echo htmlspecialchars($nombre_captura); ?>">

        <section class="form-card">
            <div class="form-card-header">
                <div>
                    <h2>Registrar incidencia interna</h2>
                    <p>Uso interno del equipo de desarrollo para documentar actividades, correcciones o incidencias capturadas manualmente.</p>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-field">
                    <label for="usuario_captura">Usuario captura</label>
                    <input type="text" id="usuario_captura" class="caja-inputs" value="<?php echo htmlspecialchars($nombre_captura); ?>" readonly>
                </div>

                <div class="form-field">
                    <label for="fecha_reporte">Fecha de reporte</label>
                    <input type="date" name="fecha_reporte" id="fecha_reporte" class="caja-inputs" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-field">
                    <label for="tiempo_resolucion">Tiempo de resolución (min)</label>
                    <input type="number" name="tiempo_resolucion" id="tiempo_resolucion" class="caja-inputs" min="0" placeholder="Ej. 30">
                </div>

                <div class="form-field">
                    <label for="fuenteSolicitud">Fuente de solicitud</label>
                    <select name="fuenteSolicitud" id="fuenteSolicitud" class="caja-select" required>
                        <option value="" selected disabled>Seleccionar</option>
                        <option value="Email">Email</option>
                        <option value="Llamada">Llamada</option>
                        <option value="Chat">Chat</option>
                        <option value="Avance/Estatus Proyecto">Avance/Estatus Proyecto</option>
                        <option value="Mesa de ayuda">Mesa de ayuda</option>
                    </select>
                </div>

                <div class="form-field form-field-full">
                    <label for="incidenciaReportada">Incidencia / actividad</label>
                    <input type="text" name="incidenciaReportada" id="incidenciaReportada" class="caja-inputs" placeholder="Ej. Ajuste en reporte" required>
                </div>

                <div class="form-field form-field-full">
                    <label for="descripcion">Descripción</label>
                    <textarea name="descripcion" id="descripcion" class="caja-inputs" rows="4" placeholder="Describe brevemente qué se reportó o qué se realizó."></textarea>
                </div>

                <div class="form-field">
                    <label for="nombreReportaInci">Nombre quien reporta</label>
                    <input type="text" name="nombreReportaInci" id="nombreReportaInci" class="caja-inputs" required>
                </div>

                <div class="form-field">
                    <label for="capturaSolicitanteImg">Captura / evidencia</label>
                    <input type="file" name="capturaSolicitanteImg" id="capturaSolicitanteImg" class="caja-inputs" accept="image/*">
                </div>

                <div class="form-field check-solucionado">
                    <input type="checkbox" name="solucionado" id="solucionado">
                    <label for="solucionado">¿Solucionado?</label>
                </div>

                <div class="form-field">
                    <label for="fecha_resolucion">Fecha de resolución</label>
                    <input type="date" name="fecha_resolucion" id="fecha_resolucion" class="caja-inputs" disabled>
                </div>

                <div class="form-field form-field-full">
                    <label for="porcentaje_avance">Estatus / avance (%)</label>
                    <div class="avance-form">
                        <input type="number" name="porcentaje_avance" id="porcentaje_avance" class="caja-inputs" min="0" max="100" value="0">
                        <span id="label_porcentaje">0%</span>
                    </div>
                    <div class="progress-container">
                        <div id="barra_progreso" class="progress-bar"></div>
                    </div>
                </div>

                <div class="acciones_formulario">
                    <button type="submit" class="btn_principal">Guardar incidencia</button>
                </div>
            </div>
        </section>
    </form>

    <section class="bloque-tabla">
        <div class="cabecera_bloque">
            <div>
                <h3>Últimas incidencias</h3>
                <p><?php echo $es_supervisor ? 'Mostrando incidencias del equipo asignado.' : 'Mostrando incidencias capturadas por tu usuario.'; ?></p>
            </div>
            <a class="btn_secundario" href="index.php?vista=registro_semanal">Ver registro semanal</a>
        </div>

        <div class="table-responsive">
            <table class="tabla-datos">
                <thead>
                    <tr>
                        <th>F. Reporte</th>
                        <th>Usuario</th>
                        <th>Incidencia</th>
                        <th>Fuente</th>
                        <th>Tiempo</th>
                        <th>Avance</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = sqlsrv_fetch_array($stmt_incidencias, SQLSRV_FETCH_ASSOC)): ?>
                        <?php
                            $es_solucionado = ((int)$row['solucionado'] === 1 || (int)$row['avance_status'] === 100);
                            $fecha_reporte = $row['fecha_reporte_inci'] ? $row['fecha_reporte_inci']->format('Y-m-d') : '-';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fecha_reporte); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre_captura']); ?></td>
                            <td><?php echo htmlspecialchars($row['incidencia_reportada']); ?></td>
                            <td><?php echo htmlspecialchars($row['fuente_solicitud']); ?></td>
                            <td><?php echo (int)($row['tiempo_resolucion'] ?? 0); ?> min</td>
                            <td><span class="avance-mini"><?php echo (int)$row['avance_status']; ?>%</span></td>
                            <td>
                                <span class="status-badge <?php echo $es_solucionado ? 'status-solved' : 'status-pending'; ?>">
                                    <?php echo $es_solucionado ? 'Resuelto' : 'Pendiente'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script src="JS/mesa_trabajo.js?v=mesa-servicio-1"></script>
