USE TeleVenta;
GO

IF OBJECT_ID('dbo.tbl_mesa_servicio_tickets', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tbl_mesa_servicio_tickets (
        id_ticket INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_empleado_reporta INT NOT NULL,
        nombre_reporta NVARCHAR(150) NOT NULL,
        area_reporta NVARCHAR(150) NULL,
        fecha_reporte DATETIME NOT NULL DEFAULT GETDATE(),
        titulo_ticket NVARCHAR(255) NOT NULL,
        descripcion_ticket NVARCHAR(MAX) NOT NULL,
        modulo_sistema NVARCHAR(150) NULL,
        categoria NVARCHAR(80) NOT NULL,
        prioridad NVARCHAR(20) NOT NULL DEFAULT 'Media',
        fuente_solicitud NVARCHAR(80) NOT NULL DEFAULT 'Mesa de Servicio',
        captura_o_imagen NVARCHAR(255) NULL,
        estatus_ticket NVARCHAR(30) NOT NULL DEFAULT 'NUEVO',
        avance_status INT NOT NULL DEFAULT 0,
        id_desarrollador_asignado INT NULL,
        nombre_desarrollador_asignado NVARCHAR(150) NULL,
        fecha_asignacion DATETIME NULL,
        asignado_por INT NULL,
        comentario_asignacion NVARCHAR(MAX) NULL,
        solucion_ticket NVARCHAR(MAX) NULL,
        fecha_resolucion DATETIME NULL,
        eliminado BIT NOT NULL DEFAULT 0,
        fecha_actualizacion DATETIME NULL,
        actualizado_por INT NULL,
        CONSTRAINT FK_mesa_servicio_reporta FOREIGN KEY (id_empleado_reporta) REFERENCES dbo.tbl_empleados(id_empleado),
        CONSTRAINT FK_mesa_servicio_desarrollador FOREIGN KEY (id_desarrollador_asignado) REFERENCES dbo.tbl_empleados(id_empleado),
        CONSTRAINT CK_mesa_servicio_prioridad CHECK (prioridad IN ('Baja','Media','Alta')),
        CONSTRAINT CK_mesa_servicio_estatus CHECK (estatus_ticket IN ('NUEVO','ASIGNADO','EN_PROCESO','RESUELTO','CANCELADO')),
        CONSTRAINT CK_mesa_servicio_avance CHECK (avance_status BETWEEN 0 AND 100)
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_mesa_servicio_estatus' AND object_id = OBJECT_ID('dbo.tbl_mesa_servicio_tickets'))
    CREATE INDEX IX_mesa_servicio_estatus ON dbo.tbl_mesa_servicio_tickets(estatus_ticket);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_mesa_servicio_desarrollador' AND object_id = OBJECT_ID('dbo.tbl_mesa_servicio_tickets'))
    CREATE INDEX IX_mesa_servicio_desarrollador ON dbo.tbl_mesa_servicio_tickets(id_desarrollador_asignado, estatus_ticket);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_mesa_servicio_fecha' AND object_id = OBJECT_ID('dbo.tbl_mesa_servicio_tickets'))
    CREATE INDEX IX_mesa_servicio_fecha ON dbo.tbl_mesa_servicio_tickets(fecha_reporte);
GO
