CREATE DATABASE IF NOT EXISTS ver2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ver2;

CREATE TABLE veterinarias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dni VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_client_email (email)
);

CREATE TABLE mascotas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  animal_type VARCHAR(80) NOT NULL,
  name VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pet_client FOREIGN KEY (client_id) REFERENCES clientes(id) ON DELETE CASCADE,
  INDEX idx_pet_client (client_id)
);

CREATE TABLE historiales_medicos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pet_id INT NOT NULL,
  vet_id INT NOT NULL,
  visit_date DATE NOT NULL,
  title VARCHAR(180) NOT NULL,
  details TEXT NOT NULL,
  medication VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_record_pet FOREIGN KEY (pet_id) REFERENCES mascotas(id) ON DELETE CASCADE,
  CONSTRAINT fk_record_vet FOREIGN KEY (vet_id) REFERENCES veterinarias(id),
  INDEX idx_record_pet_date (pet_id, visit_date)
);

CREATE TABLE productos_stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vet_id INT NOT NULL,
  name VARCHAR(180) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  expiry_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_stock_vet FOREIGN KEY (vet_id) REFERENCES veterinarias(id) ON DELETE CASCADE,
  INDEX idx_stock_vet (vet_id)
);

CREATE TABLE registro_actividad (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  pet_id INT NOT NULL,
  vet_id INT NOT NULL,
  action_text VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_pet FOREIGN KEY (pet_id) REFERENCES mascotas(id) ON DELETE CASCADE,
  CONSTRAINT fk_audit_vet FOREIGN KEY (vet_id) REFERENCES veterinarias(id)
);
