-- Tablas de las cuentas de usuario (cuenta/*.php, documento.php).
-- Se ejecuta una vez en phpMyAdmin, en la base de datos creada para el sitio.

CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  rut VARCHAR(12) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  confirmado_en DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_ingreso DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enlaces de un solo uso: confirmar cuenta y restablecer contraseña (se guarda solo el hash)
CREATE TABLE tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  tipo ENUM('confirmar', 'restablecer') NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expira_en DATETIME NOT NULL,
  usado_en DATETIME NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quién abrió qué documento
CREATE TABLE descargas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  registro VARCHAR(30) NOT NULL,
  archivo VARCHAR(100) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (fecha),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Límite de intentos de ingreso, registro y recuperación (se borran solos después de un día)
CREATE TABLE intentos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  accion VARCHAR(20) NOT NULL,
  clave VARCHAR(190) NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (accion, clave, creado_en),
  INDEX (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
