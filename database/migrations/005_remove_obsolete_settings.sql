-- Odstranění nastavení, která jsou nyní v tabulce chickens
DELETE FROM settings WHERE setting_key IN ('chicken_count', 'breed', 'chickens_birth_date');
