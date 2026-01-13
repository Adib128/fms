<?php
require_once 'config.php';

try {
    // Table ordre
    $sqlOrdre = "CREATE TABLE IF NOT EXISTS ordre (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero VARCHAR(50) NOT NULL UNIQUE,
        id_demande INT NOT NULL,
        date DATE NOT NULL,
        id_atelier INT NOT NULL,
        etat ENUM('Ouvert', 'Validé', 'Fermé') DEFAULT 'Ouvert',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_demande) REFERENCES demande(id) ON DELETE CASCADE,
        FOREIGN KEY (id_atelier) REFERENCES atelier(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $db->exec($sqlOrdre);
    echo "Table 'ordre' created successfully.\n";

    // Table ordre_intervention
    $sqlOrdreIntervention = "CREATE TABLE IF NOT EXISTS ordre_intervention (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_intervention INT NOT NULL,
        id_ordre INT NOT NULL,
        FOREIGN KEY (id_intervention) REFERENCES intervention(id),
        FOREIGN KEY (id_ordre) REFERENCES ordre(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $db->exec($sqlOrdreIntervention);
    echo "Table 'ordre_intervention' created successfully.\n";

    // Table ordre_operation
    $sqlOrdreOperation = "CREATE TABLE IF NOT EXISTS ordre_operation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_ordre INT NOT NULL,
        id_operation INT NOT NULL,
        id_technicien INT NOT NULL,
        id_article INT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        commentaire TEXT NULL,
        FOREIGN KEY (id_ordre) REFERENCES ordre(id) ON DELETE CASCADE,
        FOREIGN KEY (id_operation) REFERENCES operation(id),
        FOREIGN KEY (id_technicien) REFERENCES maintenance(id),
        FOREIGN KEY (id_article) REFERENCES article(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    $db->exec($sqlOrdreOperation);
    echo "Table 'ordre_operation' created successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
