-- Migration script to update maintenance_checklist_status table
-- This script changes the table to use id_fiche instead of id_vidange

-- Step 1: Add the new id_fiche column
ALTER TABLE maintenance_checklist_status ADD COLUMN id_fiche INT NULL AFTER id_bus;

-- Step 2: Populate the new id_fiche column based on existing data
UPDATE maintenance_checklist_status mcs 
INNER JOIN maintenance_records mr ON mcs.id_vidange = mr.id 
SET mcs.id_fiche = mr.fiche_id;

-- Step 3: Drop the old foreign key constraint for id_vidange
ALTER TABLE maintenance_checklist_status DROP FOREIGN KEY maintenance_checklist_status_ibfk_2;

-- Step 4: Drop the old id_vidange column
ALTER TABLE maintenance_checklist_status DROP COLUMN id_vidange;

-- Step 5: Add foreign key constraint for id_fiche
ALTER TABLE maintenance_checklist_status 
ADD CONSTRAINT fk_checklist_fiche 
FOREIGN KEY (id_fiche) REFERENCES fiche_entretien(id_fiche);

-- Step 6: Add unique constraint to prevent duplicate entries
ALTER TABLE maintenance_checklist_status 
ADD CONSTRAINT unique_checklist_entry 
UNIQUE (id_fiche, id_bus, checklist_item_id);
