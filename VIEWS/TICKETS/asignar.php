<div id="cam_titulo">
    <span>Asignar Tickets</span>
</div>

<div id="contenido_formulario">
    <p class="descripcion_modulo">
        En esta vista llegan los tickets reportados desde Mesa de Servicio. El supervisor puede asignarlos al equipo de desarrollo y los desarrolladores pueden autoasignarse tickets cuando sea necesario.
    </p>

    <div id="mensaje_asignar" class="mensaje_ajax"></div>

    <section class="bloque-tabla">
        <div class="cabecera_bloque">
            <div>
                <h3>Tickets recibidos</h3>
                <p>Tickets nuevos, asignados o en proceso.</p>
            </div>
            <a class="btn_secundario" href="index.php?vista=asignaciones">Ver mis asignaciones</a>
        </div>

        <div class="table-responsive">
            <table class="tabla-datos" id="tablaAsignarTickets">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Reporta</th>
                        <th>Categoría</th>
                        <th>Prioridad</th>
                        <th>Módulo</th>
                        <th>Ticket</th>
                        <th>Descripción</th>
                        <th>Evidencia</th>
                        <th>Asignado a</th>
                        <th>Estatus</th>
                        <th>Asignar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $hay_tickets = false; ?>
                    <?php while ($row = sqlsrv_fetch_array($stmt_tickets, SQLSRV_FETCH_ASSOC)): ?>
                        <?php
                            $hay_tickets = true;
                            $fecha = $row['fecha_reporte'] ? $row['fecha_reporte']->format('Y-m-d H:i') : '-';
                            $imagen = trim((string)($row['captura_o_imagen'] ?? ''));
                        ?>
                        <tr data-ticket="<?php echo (int)$row['id_ticket']; ?>">
                            <td>#<?php echo (int)$row['id_ticket']; ?></td>
                            <td><?php echo htmlspecialchars($fecha); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre_reporta']); ?></td>
                            <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                            <td><span class="prioridad prioridad-<?php echo strtolower(htmlspecialchars($row['prioridad'])); ?>"><?php echo htmlspecialchars($row['prioridad']); ?></span></td>
                            <td><?php echo htmlspecialchars((string)$row['modulo_sistema']); ?></td>
                            <td><?php echo htmlspecialchars($row['titulo_ticket']); ?></td>
                            <td class="desc-cell"><?php echo nl2br(htmlspecialchars($row['descripcion_ticket'])); ?></td>
                            <td>
                                <?php if ($imagen !== ''): ?>
                                    <a href="#" class="link-tabla" onclick="abrirVisor('UPLOADS/tickets/<?php echo htmlspecialchars($imagen); ?>');return false;">Ver</a>
                                <?php else: ?>- <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars((string)($row['nombre_desarrollador_asignado'] ?? 'Sin asignar')); ?></td>
                            <td><span class="status-badge status-pending"><?php echo htmlspecialchars($row['estatus_ticket']); ?></span></td>
                            <td>
                                <div class="asignar-box">
                                    <select class="caja-select select-dev">
                                        <option value="">Seleccionar</option>
                                        <?php foreach ($desarrolladores_mesa as $dev): ?>
                                            <option value="<?php echo (int)$dev['id_empleado']; ?>" <?php echo ((int)($row['id_desarrollador_asignado'] ?? 0) === (int)$dev['id_empleado']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dev['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" class="caja-inputs comentario-asignacion" placeholder="Comentario opcional">
                                    <button type="button" class="btn_tabla btn_save btn_asignar_ticket">Asignar</button>
                                    <?php if ($es_desarrollador): ?>
                                        <button type="button" class="btn_tabla btn_edit btn_autoasignar_ticket">Autoasignarme</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if (!$hay_tickets): ?>
                        <tr><td colspan="12">No hay tickets pendientes para asignar.</td></tr>
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
