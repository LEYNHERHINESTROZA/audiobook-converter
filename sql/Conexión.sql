CREATE DATABASE audiobook_converter;

USE audiobook_converter;

CREATE TABLE conversiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_archivo VARCHAR(255),
    tipo_archivo VARCHAR(10),
    nombre_audio VARCHAR(255),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(20)
);