<div id="cam_titulo">
    <span>Registro Semanal</span>
</div>

<div id="contenido_formulario">
    <p class="descripcion_modulo">
        Seguimiento mensual agrupado por semana. En el control mensual ahora se pueden editar todas las columnas operativas de la bitácora mediante AJAX.
    </p>

    <?php $meses = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"]; ?>

    <section class="bloque-tabla">
        <div class="cabecera_bloque">
            <div>
                <h3>Control mensual</h3>
                <p><?php echo $es_supervisor ? 'Vista de equipo asignado.' : 'Vista de tus incidencias de desarrollo.'; ?></p>
            </div>
            <a class="btn_secundario" href="index.php?vista=bitacora_desarrollo">Regresar a bitácora</a>
        </div>

        <div class="toolbar-filtros" id="filtrosYM" data-year="<?php echo (int)$year; ?>" data-month="<?php echo (int)$month; ?>">
            <select id="yearSelect" class="caja-select">
                <?php for ($y = $anio_actual - 2; $y <= $anio_actual + 1; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo ($y === $year) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>

            <div class="filtro-meses" id="monthsTabs">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <button type="button" class="month-tab <?php echo ($i === $month) ? 'active' : ''; ?>" data-month="<?php echo $i; ?>">
                        <?php echo $meses[$i - 1]; ?>
                    </button>
                <?php endfor; ?>
            </div>
        </div>

        <p class="filtros-caption">
            Mostrando registros de <strong><?php echo $meses[$month - 1] . ' ' . $year; ?></strong>.
        </p>

        <div class="table-responsive">
            <table class="tabla-datos tabla-editable" id="incidenciasTable">
                <thead>
                    <tr>
                        <th>Semana</th>
                        <th>F. Reporte</th>
                        <th>Usuario</th>
                        <th>Incidencia</th>
                        <th>Descripción</th>
                        <th>Reporta</th>
                        <th>Fuente</th>
                        <th>Tiempo</th>
                        <th>Evidencia</th>
                        <th>F. Resolución</th>
                        <th>Avance</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $semana_actual = null;
                    $hay_registros = false;
                    while ($row = sqlsrv_fetch_array($stmt_incidencias, SQLSRV_FETCH_ASSOC)):
                        $hay_registros = true;
                        $semana = (int)$row['semana_mes'];
                        if ($semana_actual !== $semana):
                            $semana_actual = $semana;
                            $total_semana = isset($tiempos_por_semana[$semana]) ? (int)$tiempos_por_semana[$semana] : 0;
                    ?>
                        <tr class="fila-semana">
                            <td colspan="13">Semana <?php echo $semana; ?> · Tiempo total: <?php echo $total_semana; ?> min</td>
                        </tr>
                    <?php endif; ?>

                    <?php
                        $fecha_reporte = $row['fecha_reporte_inci'] ? $row['fecha_reporte_inci']->format('Y-m-d') : '';
                        $fecha_resolucion = $row['fecha_resol_inci'] ? $row['fecha_resol_inci']->format('Y-m-d') : '';
                        $avance = (int)$row['avance_status'];
                        $es_solucionado = ((int)$row['solucionado'] === 1 || $avance === 100);
                        $imagen = trim((string)($row['captura_o_imagen'] ?? ''));
                    ?>
                        <tr data-id="<?php echo (int)$row['id_inci']; ?>">
                            <td><?php echo $semana; ?></td>
                            <td><input type="date" class="caja-inputs inline-field" data-field="fecha_reporte_inci" value="<?php echo htmlspecialchars($fecha_reporte); ?>" disabled></td>
                            <td><input type="text" class="caja-inputs inline-field input-sm" data-field="nombre_captura" value="<?php echo htmlspecialchars($row['nombre_captura']); ?>" disabled></td>
                            <td><input type="text" class="caja-inputs inline-field input-md" data-field="incidencia_reportada" value="<?php echo htmlspecialchars($row['incidencia_reportada']); ?>" disabled></td>
                            <td><textarea class="caja-inputs inline-field textarea-inline" data-field="descripcion_incidencia" disabled><?php echo htmlspecialchars((string)$row['descripcion_incidencia']); ?></textarea></td>
                            <td><input type="text" class="caja-inputs inline-field input-sm" data-field="nombre_quien_reporta" value="<?php echo htmlspecialchars($row['nombre_quien_reporta']); ?>" disabled></td>
                            <td>
                                <select class="caja-select inline-field" data-field="fuente_solicitud" disabled>
                                    <?php $fuenteActual = (string)$row['fuente_solicitud']; ?>
                                    <?php foreach (['Email','Llamada','Chat','Avance/Estatus Proyecto','Mesa de ayuda','Mesa de Servicio','Otro'] as $fuente): ?>
                                        <option value="<?php echo htmlspecialchars($fuente); ?>" <?php echo ($fuenteActual === $fuente) ? 'selected' : ''; ?>><?php echo htmlspecialchars($fuente); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="number" class="caja-inputs inline-field input-tiempo" data-field="tiempo_resolucion" min="0" value="<?php echo (int)($row['tiempo_resolucion'] ?? 0); ?>" disabled></td>
                            <td>
                                <?php if ($imagen !== ''): ?>
                                    <a href="#" class="link-tabla" onclick="abrirVisor('UPLOADS/incidencias/<?php echo htmlspecialchars($imagen); ?>');return false;">Ver</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><input type="date" class="caja-inputs inline-field" data-field="fecha_resol_inci" value="<?php echo htmlspecialchars($fecha_resolucion); ?>" disabled></td>
                            <td><input type="number" class="caja-inputs inline-field input-avance" data-field="avance_status" min="0" max="100" value="<?php echo $avance; ?>" disabled></td>
                            <td>
                                <select class="caja-select inline-field status-select" data-field="solucionado" disabled>
                                    <option value="0" <?php echo !$es_solucionado ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="1" <?php echo $es_solucionado ? 'selected' : ''; ?>>Resuelto</option>
                                </select>
                                <span class="status-badge <?php echo $es_solucionado ? 'status-solved' : 'status-pending'; ?>">
                                    <?php echo $es_solucionado ? 'Resuelto' : 'Pendiente'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <button type="button" class="btn_tabla btn_edit">Editar</button>
                                    <button type="button" class="btn_tabla btn_save" style="display:none;">Guardar</button>
                                    <button type="button" class="btn_tabla btn_cancel" style="display:none;">Cancelar</button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if (!$hay_registros): ?>
                        <tr>
                            <td colspan="13">No se encontraron registros para este periodo.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="modal-img" id="visorImagen" style="display:none;" onclick="cerrarVisor(event)">
    <div class="modal-img-content">
        <button type="button" class="modal-close" onclick="cerrarVisor(event)">×</button>
        <img id="imgGrande" alt="Captura incidencia">
    </div>
</div>

<script src="JS/mesa_trabajo.js?v=mesa-servicio-1"></script>
