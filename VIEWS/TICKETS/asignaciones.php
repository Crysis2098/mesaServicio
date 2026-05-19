<div id="cam_titulo">
    <span>Asignaciones</span>
</div>

<div id="contenido_formulario">
    <p class="descripcion_modulo">
        Tickets asignados para seguimiento del equipo de desarrollo. Cada desarrollador puede actualizar avance, estatus y solución.
    </p>

    <div id="mensaje_asignaciones" class="mensaje_ajax"></div>

    <section class="bloque-tabla">
        <div class="cabecera_bloque">
            <div>
                <h3><?php echo $es_supervisor ? 'Asignaciones del equipo' : 'Mis asignaciones'; ?></h3>
                <p><?php echo $es_supervisor ? 'Vista general de tickets asignados.' : 'Tickets asignados a tu usuario.'; ?></p>
            </div>
            <a class="btn_secundario" href="index.php?vista=asignar">Ir a asignar</a>
        </div>

        <div class="table-responsive">
            <table class="tabla-datos tabla-editable" id="tablaAsignaciones">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Reporta</th>
                        <th>Prioridad</th>
                        <th>Ticket</th>
                        <th>Descripción</th>
                        <th>Asignado a</th>
                        <th>Avance</th>
                        <th>Estatus</th>
                        <th>Solución / comentario</th>
                        <th>Evidencia</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $hay_asignaciones = false; ?>
                    <?php while ($row = sqlsrv_fetch_array($stmt_asignaciones, SQLSRV_FETCH_ASSOC)): ?>
                        <?php
                            $hay_asignaciones = true;
                            $fecha = $row['fecha_reporte'] ? $row['fecha_reporte']->format('Y-m-d H:i') : '-';
                            $imagen = trim((string)($row['captura_o_imagen'] ?? ''));
                        ?>
                        <tr data-ticket="<?php echo (int)$row['id_ticket']; ?>">
                            <td>#<?php echo (int)$row['id_ticket']; ?></td>
                            <td><?php echo htmlspecialchars($fecha); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre_reporta']); ?></td>
                            <td><span class="prioridad prioridad-<?php echo strtolower(htmlspecialchars($row['prioridad'])); ?>"><?php echo htmlspecialchars($row['prioridad']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['titulo_ticket']); ?></td>
                            <td class="desc-cell"><?php echo nl2br(htmlspecialchars($row['descripcion_ticket'])); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['nombre_desarrollador_asignado']); ?></td>
                            <td><input type="number" class="caja-inputs ticket-field input-avance" data-field="avance_status" min="0" max="100" value="<?php echo (int)$row['avance_status']; ?>" disabled></td>
                            <td>
                                <select class="caja-select ticket-field" data-field="estatus_ticket" disabled>
                                    <?php foreach (['ASIGNADO','EN_PROCESO','RESUELTO','CANCELADO'] as $estatus): ?>
                                        <option value="<?php echo $estatus; ?>" <?php echo ($row['estatus_ticket'] === $estatus) ? 'selected' : ''; ?>><?php echo $estatus; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><textarea class="caja-inputs ticket-field textarea-inline" data-field="solucion_ticket" disabled><?php echo htmlspecialchars((string)($row['solucion_ticket'] ?? '')); ?></textarea></td>
                            <td>
                                <?php if ($imagen !== ''): ?>
                                    <a href="#" class="link-tabla" onclick="abrirVisor('UPLOADS/tickets/<?php echo htmlspecialchars($imagen); ?>');return false;">Ver</a>
                                <?php else: ?>- <?php endif; ?>
                            </td>
                            <td>
                                <div class="row-actions-ticket">
                                    <button type="button" class="btn_tabla btn_edit btn_ticket_edit">Editar</button>
                                    <button type="button" class="btn_tabla btn_save btn_ticket_save" style="display:none;">Guardar</button>
                                    <button type="button" class="btn_tabla btn_cancel btn_ticket_cancel" style="display:none;">Cancelar</button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if (!$hay_asignaciones): ?>
                        <tr><td colspan="12">No hay tickets asignados todavía.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="modal-img" id="visorImagen" style="display:none;" onclick="cerrarVisor(event)">
    <div class="modal-img-content">
        <button type="button" class="modal-close" onclick="cerrarVisor(event)">×</button>
        <img id="imgGrande" alt="Evidencia ticket">
    </div>
</div>

<script src="JS/mesa_trabajo.js?v=mesa-servicio-1"></script>
