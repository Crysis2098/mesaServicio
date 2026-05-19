/* =========================================================
   MESA_TRABAJO_LOCAL - Base local de pruebas
   Ejecutar en SQL Server Management Studio.
   ========================================================= */

IF DB_ID('MESA_TRABAJO_LOCAL') IS NULL
BEGIN
    CREATE DATABASE MESA_TRABAJO_LOCAL;
END;
GO

USE MESA_TRABAJO_LOCAL;
GO

IF OBJECT_ID('dbo.tbl_empleados_super', 'U') IS NOT NULL DROP TABLE dbo.tbl_empleados_super;
IF OBJECT_ID('dbo.tbl_bitacora_incidencias_desarrollo', 'U') IS NOT NULL DROP TABLE dbo.tbl_bitacora_incidencias_desarrollo;
IF OBJECT_ID('dbo.tbl_empleados', 'U') IS NOT NULL DROP TABLE dbo.tbl_empleados;
GO

CREATE TABLE dbo.tbl_empleados (
    id_empleado INT NOT NULL PRIMARY KEY,
    nombre NVARCHAR(150) NOT NULL,
    id_perfil INT NOT NULL,
    activo BIT NOT NULL DEFAULT 1
);
GO

CREATE TABLE dbo.tbl_empleados_super (
    id_empleado INT NOT NULL,
    id_super INT NOT NULL,
    CONSTRAINT PK_tbl_empleados_super PRIMARY KEY (id_empleado, id_super),
    CONSTRAINT FK_tbl_empleados_super_empleado FOREIGN KEY (id_empleado) REFERENCES dbo.tbl_empleados(id_empleado),
    CONSTRAINT FK_tbl_empleados_super_super FOREIGN KEY (id_super) REFERENCES dbo.tbl_empleados(id_empleado)
);
GO

CREATE TABLE dbo.tbl_bitacora_incidencias_desarrollo (
    id_inci INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    id_empleado_captura INT NOT NULL,
    nombre_captura NVARCHAR(150) NOT NULL,
    fecha_captura DATETIME NOT NULL DEFAULT GETDATE(),
    fecha_reporte_inci DATE NOT NULL,
    fecha_resol_inci DATE NULL,
    tiempo_resolucion INT NOT NULL DEFAULT 0,
    incidencia_reportada NVARCHAR(255) NOT NULL,
    descripcion_incidencia NVARCHAR(MAX) NULL,
    nombre_quien_reporta NVARCHAR(150) NOT NULL,
    fuente_solicitud NVARCHAR(80) NOT NULL,
    captura_o_imagen NVARCHAR(255) NULL,
    solucionado BIT NOT NULL DEFAULT 0,
    avance_status INT NOT NULL DEFAULT 0,
    eliminado BIT NOT NULL DEFAULT 0,
    fecha_actualizacion DATETIME NULL,
    actualizado_por INT NULL,
    CONSTRAINT FK_bitacora_empleado FOREIGN KEY (id_empleado_captura) REFERENCES dbo.tbl_empleados(id_empleado),
    CONSTRAINT CK_bitacora_avance CHECK (avance_status BETWEEN 0 AND 100),
    CONSTRAINT CK_bitacora_tiempo CHECK (tiempo_resolucion >= 0)
);
GO

CREATE INDEX IX_bitacora_empleado_fecha ON dbo.tbl_bitacora_incidencias_desarrollo(id_empleado_captura, fecha_reporte_inci);
CREATE INDEX IX_bitacora_fecha ON dbo.tbl_bitacora_incidencias_desarrollo(fecha_reporte_inci);
CREATE INDEX IX_bitacora_avance ON dbo.tbl_bitacora_incidencias_desarrollo(avance_status);
GO

INSERT INTO dbo.tbl_empleados (id_empleado, nombre, id_perfil, activo) VALUES
(5274, N'Desarrollador Demo 5274', 1, 1),
(1573, N'Desarrollador Demo 1573', 1, 1),
(5321, N'Desarrollador Demo 5321', 1, 1),
(1971, N'Supervisor Demo 1971', 2, 1),
(9001, N'Gerente Demo', 3, 1);
GO

INSERT INTO dbo.tbl_empleados_super (id_empleado, id_super) VALUES
(5274, 1971),
(1573, 1971),
(5321, 1971),
(1971, 9001);
GO

INSERT INTO dbo.tbl_bitacora_incidencias_desarrollo
(id_empleado_captura, nombre_captura, fecha_captura, fecha_reporte_inci, fecha_resol_inci, tiempo_resolucion, incidencia_reportada, descripcion_incidencia, nombre_quien_reporta, fuente_solicitud, captura_o_imagen, solucionado, avance_status)
VALUES
(5274, N'Desarrollador Demo 5274', GETDATE(), CONVERT(date, GETDATE()), NULL, 35, N'Ajuste en formulario', N'Se reportó corrección en campo obligatorio.', N'Usuario prueba', N'Email', NULL, 0, 40),
(5274, N'Desarrollador Demo 5274', GETDATE(), DATEADD(day,-2,CONVERT(date,GETDATE())), CONVERT(date, GETDATE()), 60, N'Revisión de reporte semanal', N'Se validó agrupación por semana y filtros.', N'Supervisor prueba', N'Chat', NULL, 1, 100),
(1573, N'Desarrollador Demo 1573', GETDATE(), DATEADD(day,-4,CONVERT(date,GETDATE())), NULL, 25, N'Error de captura', N'El usuario reportó error al registrar evidencia.', N'Usuario prueba 2', N'Llamada', NULL, 0, 20);
GO
