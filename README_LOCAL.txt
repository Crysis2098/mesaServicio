MESA_SERVICIO - versión local basada en ACTIVACIONES_SL

Estructura respetada:
- index.php como routing/controlador simple.
- AJAX/ para endpoints usados con Fetch API.
- CSS/ con styleadmon.css y mesa_trabajo.css.
- JS/ con mesa_trabajo.js.
- MODELS/ con funciones SQL Server mediante sqlsrv.
- VIEWS/ con pantallas separadas.
- FUNCIONES/ para roles/permisos.

Conexión:
El proyecto apunta a la conexión global de TVTAS:
../CONEXION_CON_UTF8.PHP

Vistas principales:
1. Reportar Ticket
   index.php?vista=reportar_ticket
   Pantalla general para ejecutivos, administrativos, supervisores y usuarios internos.

2. Bitácora - Desarrollo
   index.php?vista=bitacora_desarrollo
   Pantalla interna para que el equipo de desarrollo registre incidencias o actividades manualmente.

3. Registro Semanal
   index.php?vista=registro_semanal
   Control mensual de bitácora. Todas las columnas operativas son editables por AJAX.

4. Asignaciones
   index.php?vista=asignaciones
   Tickets asignados al desarrollador. El supervisor ve las asignaciones del equipo.

5. Asignar
   index.php?vista=asignar
   Llegan los tickets reportados en Mesa de Servicio. El supervisor puede asignar y el desarrollador puede autoasignarse.

SQL requerido:
- Si ya tienes tbl_empleados, tbl_empleados_super y tbl_bitacora_incidencias_desarrollo, solo ejecuta:
  SQL/02_mesa_servicio_tickets.sql

Usuarios locales detectados en tus tablas:
- 5274 puede ser desarrollador.
- 6000 puede ser supervisor si aparece como id_super en tbl_empleados_super.

Configuración de desarrolladores:
Modificar FUNCIONES/roles.php en la función obtener_desarrolladores_mesa_servicio().
Por ahora contiene: [5274]

Prueba rápida:
http://localhost/TVTAS/MESA_TRABAJO_MVC/index.php
http://localhost/TVTAS/MESA_TRABAJO_MVC/index.php


ACTUALIZACIÓN TOAST
- Se agregó sistema de mensajes tipo toast en JS/mesa_trabajo.js.
- Los formularios AJAX y actualizaciones inline muestran notificaciones flotantes en lugar de alertas básicas.
- Los estilos están en CSS/mesa_trabajo.css bajo la sección 'Toast estilo ACTIVACIONES_SL / Mesa de Servicio'.
- Si no se ven los cambios, recarga con Ctrl + F5 por caché del navegador.


V4_TOAST_BOTTOM_UP:
- Toast corregido para salir desde abajo hacia arriba, como en ACTIVACIONES_SL.
- Se actualizó la versión del CSS para evitar caché.

CAMBIO V5 - TOAST Y BOTONES
- Los toast ahora usan duración diferenciada: éxito aprox. 2.5s, aviso aprox. 3.5s y error aprox. 4s.
- Se agregó bloqueo visual y funcional de botones durante operaciones AJAX para evitar doble clic o registros duplicados.
- Funciones agregadas en JS/mesa_trabajo.js: bloquearBoton() y desbloquearBoton().


V6:
- index.php adaptado al patrón de ACTIVACIONES_SL: variables locales de sesión simuladas en el index.
- Se agregó FUNCIONES/usuarios.php.
- roles.php ahora recibe arreglos desde usuarios.php y mantiene compatibilidad con AJAX.
- Usuarios locales considerados: 5274 desarrollador por id_perfil 15, 5279 ejecutivo por id_perfil 1, 6000 supervisor por tbl_empleados_super.
- Recargas después de éxito aumentadas a 3.2 segundos para que el toast se alcance a leer.


CAMBIOS V7 - SIMULACION PRODUCCION / SEGURIDAD DE VISTAS
----------------------------------------------------------
1. Se eliminó el cambio de usuario por URL (?user=...).
   Para probar otro usuario local modifica directamente estas variables al inicio de index.php:

   $id_empleado = 5274;
   $callcenter = 1;
   $id_perfil = 15;

2. index.php ahora valida la vista solicitada contra el tipo de usuario antes del switch.
   Aunque alguien escriba manualmente index.php?vista=asignar, si no tiene permiso se muestra acceso_denegado.php.

3. FUNCIONES/roles.php ahora contiene:
   - obtener_vistas_permitidas_mesa_servicio()
   - obtener_vista_inicial_mesa_servicio()

4. Los endpoints AJAX ya no aceptan id_empleado desde POST para decidir identidad.
   La identidad se toma desde $_SESSION['id_empleado'], igual que en un flujo más parecido a producción.
