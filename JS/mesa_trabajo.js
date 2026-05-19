function asegurarToastContainer() {
    let contenedor = document.getElementById('toast_container');
    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'toast_container';
        contenedor.className = 'toast_container';
        document.body.appendChild(contenedor);
    }
    return contenedor;
}

function mostrarToast(tipo, texto, duracion = null) {
    const contenedor = asegurarToastContainer();
    const esOk = tipo === 'ok' || tipo === 'success';
    const esWarning = tipo === 'warning' || tipo === 'aviso';
    const claseTipo = esOk ? 'toast_ok' : (esWarning ? 'toast_warning' : 'toast_error');

    const tiempoToast = duracion ?? (
        esOk ? 2500 :
        esWarning ? 3500 :
        4000
    );

    const toast = document.createElement('div');
    toast.className = 'toast_mesa ' + claseTipo;

    const icono = document.createElement('span');
    icono.className = 'toast_icono';
    icono.textContent = esOk ? '✓' : '!';

    const contenido = document.createElement('div');
    contenido.className = 'toast_contenido';

    const titulo = document.createElement('strong');
    titulo.textContent = esOk ? 'Operación exitosa' : (esWarning ? 'Aviso' : 'Error');

    const mensaje = document.createElement('p');
    mensaje.textContent = texto || (esOk ? 'Acción realizada correctamente.' : 'Revisa la información e inténtalo nuevamente.');

    const cerrar = document.createElement('button');
    cerrar.type = 'button';
    cerrar.className = 'toast_cerrar';
    cerrar.setAttribute('aria-label', 'Cerrar mensaje');
    cerrar.textContent = '×';

    contenido.appendChild(titulo);
    contenido.appendChild(mensaje);
    toast.appendChild(icono);
    toast.appendChild(contenido);
    toast.appendChild(cerrar);
    contenedor.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('toast_visible'));

    const cerrarToast = () => {
        toast.classList.remove('toast_visible');
        toast.classList.add('toast_saliendo');
        setTimeout(() => toast.remove(), 260);
    };

    cerrar.addEventListener('click', cerrarToast);
    setTimeout(cerrarToast, tiempoToast);
}

function bloquearBoton(boton, textoCarga = 'Procesando...') {
    if (!boton) return;
    if (!boton.dataset.textoOriginal) {
        boton.dataset.textoOriginal = boton.innerHTML;
    }
    boton.disabled = true;
    boton.classList.add('btn-bloqueado');
    boton.innerHTML = textoCarga;
}

function desbloquearBoton(boton) {
    if (!boton) return;
    boton.disabled = false;
    boton.classList.remove('btn-bloqueado');
    if (boton.dataset.textoOriginal) {
        boton.innerHTML = boton.dataset.textoOriginal;
        delete boton.dataset.textoOriginal;
    }
}

function mostrarMensajeElemento(el, tipo, texto) {
    mostrarToast(tipo, texto);
    if (!el) return;
    el.className = 'mensaje_ajax ' + (tipo === 'ok' ? 'ok' : 'error');
    el.textContent = texto;
    el.style.display = 'block';
}

function clampInt(n, min, max) {
    const v = Number.parseInt(n, 10);
    if (Number.isNaN(v)) return min;
    return Math.max(min, Math.min(max, v));
}

(function inicializarFormularioIncidencia() {
    const form = document.getElementById('form_guardar_incidencia');
    if (!form) return;

    const mensaje = document.getElementById('mensaje_ajax');
    const inputAvance = document.getElementById('porcentaje_avance');
    const barraProgreso = document.getElementById('barra_progreso');
    const labelPorcentaje = document.getElementById('label_porcentaje');
    const chkSolucionado = document.getElementById('solucionado');
    const inputFechaResol = document.getElementById('fecha_resolucion');

    function actualizarProgreso(valor) {
        const v = clampInt(valor, 0, 100);
        inputAvance.value = v;
        barraProgreso.style.width = v + '%';
        labelPorcentaje.textContent = v + '%';
        chkSolucionado.checked = v === 100;
        inputFechaResol.disabled = v !== 100;
        inputFechaResol.required = v === 100;

        if (v === 100 && !inputFechaResol.value) inputFechaResol.value = new Date().toISOString().split('T')[0];
        if (v !== 100) inputFechaResol.value = '';
    }

    chkSolucionado.addEventListener('change', function () { actualizarProgreso(this.checked ? 100 : 0); });
    inputAvance.addEventListener('input', function () { actualizarProgreso(this.value); });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        inputAvance.disabled = false;
        inputFechaResol.disabled = false;

        const boton = form.querySelector('button[type="submit"]');
        bloquearBoton(boton, 'Guardando...');

        try {
            const res = await fetch('AJAX/incidencias/guardar.php', { method: 'POST', body: new FormData(form) });
            const data = await res.json().catch(() => null);
            if (!res.ok || !data || data.ok !== true) throw new Error((data && data.mensaje) ? data.mensaje : 'No se pudo guardar la incidencia.');
            mostrarMensajeElemento(mensaje, 'ok', data.mensaje || 'Incidencia registrada correctamente.');
            form.reset();
            actualizarProgreso(0);
            setTimeout(() => { window.location.href = 'index.php?vista=bitacora_desarrollo'; }, 3200);
        } catch (error) {
            mostrarMensajeElemento(mensaje, 'error', error.message || 'Ocurrió un error al guardar.');
        } finally {
            desbloquearBoton(boton);
        }
    });
})();

(function inicializarFormularioTicket() {
    const form = document.getElementById('form_reportar_ticket');
    if (!form) return;
    const mensaje = document.getElementById('mensaje_ticket');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const boton = form.querySelector('button[type="submit"]');
        bloquearBoton(boton, 'Enviando...');

        try {
            const res = await fetch('AJAX/tickets/guardar.php', { method: 'POST', body: new FormData(form) });
            const data = await res.json().catch(() => null);
            if (!res.ok || !data || data.ok !== true) throw new Error((data && data.mensaje) ? data.mensaje : 'No se pudo enviar el ticket.');
            mostrarMensajeElemento(mensaje, 'ok', data.mensaje || 'Ticket enviado correctamente.');
            form.reset();
        } catch (error) {
            mostrarMensajeElemento(mensaje, 'error', error.message || 'Ocurrió un error al enviar el ticket.');
        } finally {
            desbloquearBoton(boton);
        }
    });
})();

(function inicializarFiltrosRegistroSemanal() {
    const contenedor = document.getElementById('filtrosYM');
    if (!contenedor) return;

    const yearSelect = document.getElementById('yearSelect');
    const tabs = document.querySelectorAll('.month-tab');

    function navegar(month) {
        const year = yearSelect ? yearSelect.value : contenedor.dataset.year;
        window.location.href = 'index.php?vista=registro_semanal&year=' + encodeURIComponent(year) + '&month=' + encodeURIComponent(month);
    }

    if (yearSelect) yearSelect.addEventListener('change', function () { navegar(contenedor.dataset.month || new Date().getMonth() + 1); });
    tabs.forEach((btn) => btn.addEventListener('click', function () { navegar(this.dataset.month); }));
})();

function abrirVisor(ruta) {
    const modal = document.getElementById('visorImagen');
    const img = document.getElementById('imgGrande');
    if (!modal || !img) return;
    img.src = ruta;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarVisor(e) {
    const modal = document.getElementById('visorImagen');
    const img = document.getElementById('imgGrande');
    if (!modal || !img) return;
    if (e && e.target && e.target.closest) {
        const dentro = e.target.closest('.modal-img-content');
        const botonCerrar = e.target.classList && e.target.classList.contains('modal-close');
        if (dentro && !botonCerrar) return;
    }
    modal.style.display = 'none';
    img.src = '';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarVisor(); });

(function inicializarEdicionControlMensual() {
    const tabla = document.getElementById('incidenciasTable');
    if (!tabla) return;

    const urlActualizar = 'AJAX/incidencias/actualizar_inline.php';
    let accionesActivas = null;

    function qs(el, sel) { return el ? el.querySelector(sel) : null; }
    function qsa(el, sel) { return el ? Array.from(el.querySelectorAll(sel)) : []; }
    function rowDesdeAcciones(acciones) { return acciones.closest('tr'); }
    function camposFila(row) { return qsa(row, '.inline-field'); }

    function snapshot(row) {
        const snap = {};
        camposFila(row).forEach((campo) => { snap[campo.dataset.field] = campo.value; });
        return snap;
    }

    function restore(row, snap) {
        camposFila(row).forEach((campo) => {
            if (Object.prototype.hasOwnProperty.call(snap, campo.dataset.field)) campo.value = snap[campo.dataset.field];
        });
    }

    function setEdicion(acciones, editando) {
        const row = rowDesdeAcciones(acciones);
        const btnEdit = qs(acciones, '.btn_edit');
        const btnSave = qs(acciones, '.btn_save');
        const btnCancel = qs(acciones, '.btn_cancel');
        if (btnEdit) btnEdit.style.display = editando ? 'none' : '';
        if (btnSave) btnSave.style.display = editando ? '' : 'none';
        if (btnCancel) btnCancel.style.display = editando ? '' : 'none';
        camposFila(row).forEach((campo) => { campo.disabled = !editando; });
    }

    function bloquearOtrasFilas(actual, bloqueado) {
        qsa(tabla, '.row-actions').forEach((acciones) => {
            if (acciones === actual) return;
            const btnEdit = qs(acciones, '.btn_edit');
            if (btnEdit) btnEdit.disabled = bloqueado;
        });
    }

    tabla.addEventListener('click', async function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;

        const acciones = btn.closest('.row-actions');
        const row = acciones ? rowDesdeAcciones(acciones) : null;
        if (!acciones || !row) return;

        if (btn.classList.contains('btn_edit')) {
            if (accionesActivas && accionesActivas !== acciones) return;
            acciones.dataset.snap = JSON.stringify(snapshot(row));
            accionesActivas = acciones;
            setEdicion(acciones, true);
            bloquearOtrasFilas(acciones, true);
            return;
        }

        if (btn.classList.contains('btn_cancel')) {
            restore(row, JSON.parse(acciones.dataset.snap || '{}'));
            setEdicion(acciones, false);
            bloquearOtrasFilas(acciones, false);
            accionesActivas = null;
            return;
        }

        if (btn.classList.contains('btn_save')) {
            const id = row.dataset.id;
            const campos = {};
            camposFila(row).forEach((campo) => {
                campos[campo.dataset.field] = campo.value;
            });
            if (campos.avance_status !== undefined) campos.avance_status = clampInt(campos.avance_status, 0, 100);
            if (campos.tiempo_resolucion !== undefined) campos.tiempo_resolucion = Math.max(0, Number.parseInt(campos.tiempo_resolucion || '0', 10));

            bloquearBoton(btn, 'Guardando...');

            try {
                const res = await fetch(urlActualizar, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, campos })
                });
                const data = await res.json().catch(() => null);
                if (!res.ok || !data || data.ok !== true) throw new Error((data && data.mensaje) ? data.mensaje : 'No se pudo actualizar el registro.');
                setEdicion(acciones, false);
                bloquearOtrasFilas(acciones, false);
                accionesActivas = null;
                mostrarToast('ok', data.mensaje || 'Registro actualizado correctamente.', 2500);
            } catch (error) {
                mostrarToast('error', error.message || 'Error al actualizar.', 4000);
            } finally {
                desbloquearBoton(btn);
            }
        }
    });
})();

(function inicializarAsignarTickets() {
    const tabla = document.getElementById('tablaAsignarTickets');
    if (!tabla) return;
    const mensaje = document.getElementById('mensaje_asignar');

    tabla.addEventListener('click', async function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;

        const row = btn.closest('tr');
        if (!row) return;

        const idTicket = row.dataset.ticket;
        const selectDev = row.querySelector('.select-dev');
        const comentario = row.querySelector('.comentario-asignacion');
        const auto = btn.classList.contains('btn_autoasignar_ticket') ? 1 : 0;
        const idDev = auto ? 0 : (selectDev ? selectDev.value : 0);

        if (!auto && !idDev) {
            mostrarMensajeElemento(mensaje, 'error', 'Selecciona un desarrollador.');
            return;
        }

        bloquearBoton(btn, 'Guardando...');

        try {
            const res = await fetch('AJAX/tickets/asignar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_ticket: idTicket,
                    id_desarrollador: idDev,
                    auto: auto,
                    comentario_asignacion: comentario ? comentario.value : ''
                })
            });
            const data = await res.json().catch(() => null);
            if (!res.ok || !data || data.ok !== true) throw new Error((data && data.mensaje) ? data.mensaje : 'No se pudo asignar el ticket.');
            mostrarMensajeElemento(mensaje, 'ok', data.mensaje || 'Ticket asignado correctamente.');
            setTimeout(() => window.location.reload(), 3200);
        } catch (error) {
            mostrarMensajeElemento(mensaje, 'error', error.message || 'Error al asignar.');
        } finally {
            desbloquearBoton(btn);
        }
    });
})();

(function inicializarAsignaciones() {
    const tabla = document.getElementById('tablaAsignaciones');
    if (!tabla) return;
    const mensaje = document.getElementById('mensaje_asignaciones');
    let activa = null;

    function qsa(el, sel) { return el ? Array.from(el.querySelectorAll(sel)) : []; }
    function campos(row) { return qsa(row, '.ticket-field'); }
    function snap(row) {
        const s = {};
        campos(row).forEach((campo) => { s[campo.dataset.field] = campo.value; });
        return s;
    }
    function restore(row, s) {
        campos(row).forEach((campo) => { if (Object.prototype.hasOwnProperty.call(s, campo.dataset.field)) campo.value = s[campo.dataset.field]; });
    }
    function set(row, editando) {
        campos(row).forEach((campo) => { campo.disabled = !editando; });
        row.querySelector('.btn_ticket_edit').style.display = editando ? 'none' : '';
        row.querySelector('.btn_ticket_save').style.display = editando ? '' : 'none';
        row.querySelector('.btn_ticket_cancel').style.display = editando ? '' : 'none';
    }

    tabla.addEventListener('click', async function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        const row = btn.closest('tr');
        if (!row) return;

        if (btn.classList.contains('btn_ticket_edit')) {
            if (activa && activa !== row) return;
            row.dataset.snap = JSON.stringify(snap(row));
            activa = row;
            set(row, true);
            return;
        }

        if (btn.classList.contains('btn_ticket_cancel')) {
            restore(row, JSON.parse(row.dataset.snap || '{}'));
            set(row, false);
            activa = null;
            return;
        }

        if (btn.classList.contains('btn_ticket_save')) {
            const body = { id_ticket: row.dataset.ticket };
            campos(row).forEach((campo) => { body[campo.dataset.field] = campo.value; });
            body.avance_status = clampInt(body.avance_status, 0, 100);

            bloquearBoton(btn, 'Guardando...');
            try {
                const res = await fetch('AJAX/tickets/actualizar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                const data = await res.json().catch(() => null);
                if (!res.ok || !data || data.ok !== true) throw new Error((data && data.mensaje) ? data.mensaje : 'No se pudo actualizar el ticket.');
                mostrarMensajeElemento(mensaje, 'ok', data.mensaje || 'Ticket actualizado correctamente.');
                set(row, false);
                activa = null;
            } catch (error) {
                mostrarMensajeElemento(mensaje, 'error', error.message || 'Error al actualizar ticket.');
            } finally {
                desbloquearBoton(btn);
            }
        }
    });
})();
