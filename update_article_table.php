<?php
require_once __DIR__ . '/config.php';

try {
    $db->exec("ALTER TABLE article 
        ADD COLUMN etat ENUM('Neuf', 'Récupérer', 'Rénover') DEFAULT 'Neuf',
        ADD COLUMN etat_pourcentage INT DEFAULT 100,
        ADD COLUMN valeur DECIMAL(15, 2) DEFAULT 0.00,
        ADD COLUMN id_vehicule INT NULL,
        ADD CONSTRAINT fk_article_vehicule FOREIGN KEY (id_vehicule) REFERENCES bus(id_bus) ON DELETE SET NULL
    ");
    echo "Table article mise à jour avec succès.\n";
} catch (PDOException $e) {
    echo "Erreur lors de la mise à jour de la table article : " . $e->getMessage() . "\n";
}
