CREATE TABLE IF NOT EXISTS ordre_intervention_technicien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_ordre_intervention INT NOT NULL,
    id_technicien INT NOT NULL,
    FOREIGN KEY (id_ordre_intervention) REFERENCES ordre_intervention(id) ON DELETE CASCADE,
    FOREIGN KEY (id_technicien) REFERENCES maintenance(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ordre_intervention_article (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_ordre_intervention INT NOT NULL,
    id_article INT NOT NULL,
    quantite INT DEFAULT 1,
    FOREIGN KEY (id_ordre_intervention) REFERENCES ordre_intervention(id) ON DELETE CASCADE,
    FOREIGN KEY (id_article) REFERENCES article(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
