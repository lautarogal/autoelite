-- ============================================================================
-- AutoElite Motors: BASE COMPLETA EN UN SOLO ARCHIVO (para empezar de cero).
-- Incluye, en orden: 1) base y tablas  2) migración v3  3) columnas de crédito
--                    4) datos de prueba (empleados, vehículos y clientes ficticios).
-- Importar SOLO este archivo en phpMyAdmin (pestaña Importar). Requiere MariaDB (XAMPP).
-- Si ya tenés una base con datos, NO uses este archivo: usá migracion_v3.sql.
-- ============================================================================

-- ---------- 1) BASE Y TABLAS ----------
SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS concesionaria DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE concesionaria;

CREATE TABLE Dueno (
    ID_Dueno INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Presupuesto_Inversion DECIMAL(12,2),
    Relacion_Planta VARCHAR(150)
);

CREATE TABLE Cliente (
    ID_Cliente INT AUTO_INCREMENT PRIMARY KEY,
    Nombre_Completo_Razon_Social VARCHAR(150) NOT NULL,
    RFC VARCHAR(20),
    CURP VARCHAR(20),
    Direccion VARCHAR(200),
    Telefono VARCHAR(30),
    Correo_Electronico VARCHAR(100),
    Historial_Crediticio TEXT,
    Referencias_Bancarias TEXT,
    Preferencias_CRM TEXT,
    Tipo_Cliente ENUM('Particular','Enviado por Seguro','Empresa/Flotas') NOT NULL
);

CREATE TABLE Asesor_Ventas (
    ID_Vendedor INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Cartera_Prospectos INT DEFAULT 0,
    Meta_Ventas_Mensual INT DEFAULT 0,
    Unidades_Vendidas INT DEFAULT 0,
    ID_Dueno INT,
    FOREIGN KEY (ID_Dueno) REFERENCES Dueno(ID_Dueno)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE Gerente_FI (
    ID_FI INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Perfiles_Crediticios TEXT,
    Polizas_Vendidas INT DEFAULT 0,
    Tasas_Vigentes DECIMAL(5,2),
    ID_Dueno INT,
    FOREIGN KEY (ID_Dueno) REFERENCES Dueno(ID_Dueno)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE Asesor_Servicio (
    ID_Asesor_Srv INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    No_Orden_Reparacion INT DEFAULT 0,
    Puntaje_Satisfaccion DECIMAL(5,2),
    Fecha_Prometida DATE,
    ID_Dueno INT,
    FOREIGN KEY (ID_Dueno) REFERENCES Dueno(ID_Dueno)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE Mecanico (
    ID_Mecanico INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Especialidad_Tecnica VARCHAR(100),
    Horas_Disponibles DECIMAL(5,2) DEFAULT 0,
    Certificacion_Marca VARCHAR(100),
    ID_Dueno INT,
    FOREIGN KEY (ID_Dueno) REFERENCES Dueno(ID_Dueno)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE Vehiculo (
    VIN VARCHAR(30) PRIMARY KEY,
    Marca VARCHAR(50) NOT NULL,
    Modelo VARCHAR(100) NOT NULL,
    Anio YEAR,
    Kilometraje INT DEFAULT 0,
    Tipo_Vehiculo ENUM('Nuevo','Usado') NOT NULL,
    Precio DECIMAL(12,2) NOT NULL,
    Garantia VARCHAR(200),
    Estado ENUM('Disponible','Reservado','Vendido','En Reparacion')
        DEFAULT 'Disponible'
);

CREATE TABLE Venta (
    ID_Venta INT AUTO_INCREMENT PRIMARY KEY,
    Fecha DATE NOT NULL,
    Precio_Venta DECIMAL(12,2) NOT NULL,
    Impuestos DECIMAL(12,2) DEFAULT 0,
    Margen_Utilidad DECIMAL(12,2),
    Metodo_Pago ENUM('Contado','Credito') NOT NULL,
    ID_Cliente INT NOT NULL,
    VIN VARCHAR(30) NOT NULL,
    ID_Vendedor INT NOT NULL,
    ID_FI INT,
    FOREIGN KEY (ID_Cliente) REFERENCES Cliente(ID_Cliente)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (VIN) REFERENCES Vehiculo(VIN)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (ID_Vendedor) REFERENCES Asesor_Ventas(ID_Vendedor)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (ID_FI) REFERENCES Gerente_FI(ID_FI)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE Orden_Reparacion (
    ID_OR INT AUTO_INCREMENT PRIMARY KEY,
    Fecha_Apertura DATE NOT NULL,
    Fecha_Cierre DATE,
    Diagnostico TEXT,
    Estado ENUM('Pendiente','En Diagnostico','En Reparacion','Finalizada','Entregada')
        DEFAULT 'Pendiente',
    Costo_Total DECIMAL(12,2) DEFAULT 0,
    ID_Cliente INT NOT NULL,
    VIN VARCHAR(30) NOT NULL,
    ID_Asesor_Srv INT NOT NULL,
    ID_Mecanico INT NOT NULL,
    FOREIGN KEY (ID_Cliente) REFERENCES Cliente(ID_Cliente)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (VIN) REFERENCES Vehiculo(VIN)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (ID_Asesor_Srv) REFERENCES Asesor_Servicio(ID_Asesor_Srv)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (ID_Mecanico) REFERENCES Mecanico(ID_Mecanico)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ---------- 2) MIGRACIÓN v3 ----------
USE concesionaria;

-- Clientes: CURP -> DNI
ALTER TABLE Cliente CHANGE COLUMN IF EXISTS CURP DNI VARCHAR(20) NULL;
ALTER TABLE Cliente ADD COLUMN IF NOT EXISTS DNI VARCHAR(20) NULL;

-- Empleados: datos personales y laborales
ALTER TABLE Asesor_Ventas   ADD COLUMN IF NOT EXISTS DNI VARCHAR(20) NULL, ADD COLUMN IF NOT EXISTS Edad TINYINT UNSIGNED NULL, ADD COLUMN IF NOT EXISTS Direccion VARCHAR(200) NULL, ADD COLUMN IF NOT EXISTS Horario_Trabajo VARCHAR(100) NULL;
ALTER TABLE Gerente_FI      ADD COLUMN IF NOT EXISTS DNI VARCHAR(20) NULL, ADD COLUMN IF NOT EXISTS Edad TINYINT UNSIGNED NULL, ADD COLUMN IF NOT EXISTS Direccion VARCHAR(200) NULL, ADD COLUMN IF NOT EXISTS Horario_Trabajo VARCHAR(100) NULL;
ALTER TABLE Asesor_Servicio ADD COLUMN IF NOT EXISTS DNI VARCHAR(20) NULL, ADD COLUMN IF NOT EXISTS Edad TINYINT UNSIGNED NULL, ADD COLUMN IF NOT EXISTS Direccion VARCHAR(200) NULL, ADD COLUMN IF NOT EXISTS Horario_Trabajo VARCHAR(100) NULL;
ALTER TABLE Mecanico        ADD COLUMN IF NOT EXISTS DNI VARCHAR(20) NULL, ADD COLUMN IF NOT EXISTS Edad TINYINT UNSIGNED NULL, ADD COLUMN IF NOT EXISTS Direccion VARCHAR(200) NULL, ADD COLUMN IF NOT EXISTS Horario_Trabajo VARCHAR(100) NULL;

-- El DNI de un empleado no puede repetirse (los NULL no cuentan)
CREATE UNIQUE INDEX IF NOT EXISTS uq_asesor_ventas_dni    ON Asesor_Ventas (DNI);
CREATE UNIQUE INDEX IF NOT EXISTS uq_gerente_fi_dni       ON Gerente_FI (DNI);
CREATE UNIQUE INDEX IF NOT EXISTS uq_asesor_servicio_dni  ON Asesor_Servicio (DNI);
CREATE UNIQUE INDEX IF NOT EXISTS uq_mecanico_dni         ON Mecanico (DNI);

-- Reparaciones: ya no se solicita el asesor de servicio
ALTER TABLE Orden_Reparacion MODIFY ID_Asesor_Srv INT NULL;

-- ---------- 3) CRÉDITO (cuotas y recargo en Venta) ----------
ALTER TABLE Venta ADD COLUMN IF NOT EXISTS Cuotas INT NULL, ADD COLUMN IF NOT EXISTS Recargo DECIMAL(12,2) NOT NULL DEFAULT 0;

-- ---------- 4) DATOS DE PRUEBA ----------
SET NAMES utf8mb4;
USE concesionaria;

-- ---------------------------- EMPLEADOS ------------------------------------
INSERT INTO Asesor_Ventas (Nombre, DNI, Edad, Direccion, Horario_Trabajo)
SELECT * FROM (
  SELECT 'Martín Ledesma' AS Nombre, '28451736' AS DNI, 34 AS Edad, 'Av. Mitre 1520, Quilmes' AS Direccion, 'Lunes a Viernes de 9:00 a 18:00' AS Horario_Trabajo
  UNION ALL SELECT 'Camila Rojas', '33109482', 27, 'Calle 14 N° 872, Berazategui', 'Martes a Sábado de 10:00 a 19:00'
) t WHERE NOT EXISTS (SELECT 1 FROM Asesor_Ventas a WHERE a.DNI = t.DNI);

INSERT INTO Gerente_FI (Nombre, DNI, Edad, Direccion, Horario_Trabajo)
SELECT * FROM (
  SELECT 'Rodrigo Benítez' AS Nombre, '25873641' AS DNI, 41 AS Edad, 'Av. Calchaquí 3340, Florencio Varela' AS Direccion, 'Lunes a Viernes de 9:00 a 17:00' AS Horario_Trabajo
  UNION ALL SELECT 'Lucía Fernández', '31568294', 36, 'Calle 25 de Mayo 455, Bernal', 'Lunes a Viernes de 10:00 a 18:00'
) t WHERE NOT EXISTS (SELECT 1 FROM Gerente_FI a WHERE a.DNI = t.DNI);

INSERT INTO Asesor_Servicio (Nombre, DNI, Edad, Direccion, Horario_Trabajo)
SELECT * FROM (
  SELECT 'Nicolás Paredes' AS Nombre, '35204817' AS DNI, 29 AS Edad, 'Av. 7 N° 1210, Berazategui' AS Direccion, 'Lunes a Viernes de 8:00 a 16:00' AS Horario_Trabajo
  UNION ALL SELECT 'Valeria Acosta', '30746125', 38, 'Calle Belgrano 678, Ranelagh', 'Lunes a Sábado de 8:00 a 14:00'
) t WHERE NOT EXISTS (SELECT 1 FROM Asesor_Servicio a WHERE a.DNI = t.DNI);

INSERT INTO Mecanico (Nombre, DNI, Edad, Direccion, Horario_Trabajo)
SELECT * FROM (
  SELECT 'Héctor Villalba' AS Nombre, '22984573' AS DNI, 52 AS Edad, 'Calle 148 N° 2315, Berazategui' AS Direccion, 'Lunes a Viernes de 7:30 a 16:30' AS Horario_Trabajo
  UNION ALL SELECT 'Gastón Medina', '36419052', 26, 'Av. Güemes 905, Hudson', 'Lunes a Viernes de 8:00 a 17:00'
) t WHERE NOT EXISTS (SELECT 1 FROM Mecanico a WHERE a.DNI = t.DNI);

-- ---------------------------- VEHÍCULOS ------------------------------------
-- VIN = patente. La garantía solo corresponde a vehículos 0 km (Nuevo).
INSERT INTO Vehiculo (VIN, Marca, Modelo, Anio, Kilometraje, Tipo_Vehiculo, Precio, Garantia, Estado)
SELECT * FROM (
  SELECT 'AF214MK' AS VIN, 'Toyota' AS Marca, 'Hilux SRV 4x4 AT' AS Modelo, 2024 AS Anio, 0 AS Kilometraje, 'Nuevo' AS Tipo_Vehiculo, 52900000.00 AS Precio, '3 años o 100.000 km' AS Garantia, 'Disponible' AS Estado
  UNION ALL SELECT 'AG508PL', 'Volkswagen', 'Amarok V6 Extreme', 2024, 0, 'Nuevo', 61500000.00, '3 años o 100.000 km', 'Disponible'
  UNION ALL SELECT 'AE930RD', 'Fiat', 'Cronos Drive 1.3', 2023, 0, 'Nuevo', 21800000.00, '2 años o 60.000 km', 'Disponible'
  UNION ALL SELECT 'AD377TC', 'Renault', 'Kangoo Life 1.6', 2023, 0, 'Nuevo', 24300000.00, '2 años o 60.000 km', 'Disponible'
  UNION ALL SELECT 'AC662HB', 'Chevrolet', 'Cruze LTZ 1.4T', 2021, 42000, 'Usado', 24900000.00, NULL, 'Disponible'
  UNION ALL SELECT 'AB145VN', 'Ford', 'Ranger XLT 3.2 4x4', 2019, 87500, 'Usado', 33800000.00, NULL, 'Disponible'
  UNION ALL SELECT 'AA781GZ', 'Peugeot', '208 Allure 1.6', 2020, 61300, 'Usado', 16900000.00, NULL, 'Disponible'
  UNION ALL SELECT 'AD093FS', 'Volkswagen', 'Polo Trendline 1.6', 2022, 28700, 'Usado', 19800000.00, NULL, 'Disponible'
  UNION ALL SELECT 'AE455JL', 'Toyota', 'Corolla XEi 2.0 CVT', 2022, 35200, 'Usado', 29900000.00, NULL, 'Disponible'
  UNION ALL SELECT 'AB318WQ', 'Honda', 'HR-V EXL 1.8', 2018, 98400, 'Usado', 22500000.00, NULL, 'Disponible'
) t WHERE NOT EXISTS (SELECT 1 FROM Vehiculo v WHERE v.VIN = t.VIN);

-- ----------------------------- CLIENTES ------------------------------------
INSERT INTO Cliente (Nombre_Completo_Razon_Social, DNI, Direccion, Telefono, Correo_Electronico, Tipo_Cliente)
SELECT * FROM (
  SELECT 'Ricardo Gómez' AS n, '27345918' AS d, 'Calle 9 N° 1450, Berazategui' AS dir, '11 5234-8890' AS tel, 'ricardo.gomez@example.com' AS mail, 'Particular' AS tipo
  UNION ALL SELECT 'Marina Sosa', '33872046', 'Av. San Martín 220, Quilmes', '11 6021-3345', 'marina.sosa@example.com', 'Particular'
  UNION ALL SELECT 'Transportes del Sur S.R.L.', NULL, 'Ruta 2 km 38, Berazategui', '11 4256-7710', 'flota@transportesdelsur.example.com', 'Empresa/Flotas'
) t WHERE NOT EXISTS (SELECT 1 FROM Cliente c WHERE c.Nombre_Completo_Razon_Social = t.n);
