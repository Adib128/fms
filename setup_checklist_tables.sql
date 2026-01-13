-- Create checklist_items table if it doesn't exist
CREATE TABLE IF NOT EXISTS checklist_items (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code varchar(255) NOT NULL,
  label varchar(255) NOT NULL,
  parti enum('MOTEUR','TRANSMISSION','FREINAGE','SUSPENSION ET DIRECTION','ELECTRICITE AUTO','CARROSSERIE') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Create maintenance_checklist_status table if it doesn't exist
CREATE TABLE IF NOT EXISTS maintenance_checklist_status (
  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_bus int NOT NULL,
  id_vidange int NOT NULL,
  checklist_item_id int NOT NULL,
  is_checked tinyint(1) NOT NULL DEFAULT '0',
  checked_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_bus) REFERENCES bus(id_bus),
  FOREIGN KEY (id_vidange) REFERENCES maintenance_records(id),
  FOREIGN KEY (checklist_item_id) REFERENCES checklist_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Insert sample checklist items (optional)
INSERT IGNORE INTO checklist_items (code, label, parti) VALUES
-- MOTEUR
('M001', 'Vérification niveau d\'huile moteur', 'MOTEUR'),
('M002', 'Contrôle fuite d\'huile', 'MOTEUR'),
('M003', 'Vérification du filtre à air', 'MOTEUR'),
('M004', 'Contrôle de la courroie de distribution', 'MOTEUR'),
('M005', 'Vérification des bougies d\'allumage', 'MOTEUR'),

-- TRANSMISSION
('T001', 'Vérification niveau de boîte de vitesse', 'TRANSMISSION'),
('T002', 'Contrôle fuite transmission', 'TRANSMISSION'),
('T003', 'Vérification embrayage', 'TRANSMISSION'),
('T004', 'Contrôle cardans', 'TRANSMISSION'),

-- FREINAGE
('F001', 'Vérification plaquettes de frein', 'FREINAGE'),
('F002', 'Contrôle disques de frein', 'FREINAGE'),
('F003', 'Vérification niveau liquide de frein', 'FREINAGE'),
('F004', 'Contrôle flexible de frein', 'FREINAGE'),

-- SUSPENSION ET DIRECTION
('S001', 'Vérification amortisseurs', 'SUSPENSION ET DIRECTION'),
('S002', 'Contrôle rotules de direction', 'SUSPENSION ET DIRECTION'),
('S003', 'Vérification géométrie', 'SUSPENSION ET DIRECTION'),
('S004', 'Contrôle silentblocs', 'SUSPENSION ET DIRECTION'),

-- ELECTRICITE AUTO
('E001', 'Vérification batterie', 'ELECTRICITE AUTO'),
('E002', 'Contrôle faisceau électrique', 'ELECTRICITE AUTO'),
('E003', 'Vérification éclairage', 'ELECTRICITE AUTO'),
('E004', 'Contrôle alternateur', 'ELECTRICITE AUTO'),

-- CARROSSERIE
('C001', 'Vérification état carrosserie', 'CARROSSERIE'),
('C002', 'Contrôle corrosion', 'CARROSSERIE'),
('C003', 'Vérification vitrages', 'CARROSSERIE'),
('C004', 'Contrôle fermetures', 'CARROSSERIE');
