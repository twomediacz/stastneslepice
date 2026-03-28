-- Šťastné slepice – demo data: finance
-- Ostatní náklady a prodej/darování vajec

-- Smazání starých záznamů
DELETE FROM expenses;
DELETE FROM egg_transactions;

-- Nastavení ceny vejce v obchodě
INSERT INTO settings (setting_key, setting_value) VALUES
    ('egg_market_price', '5.50')
ON DUPLICATE KEY UPDATE setting_value = '5.50';

-- ==========================================
-- Ostatní náklady
-- ==========================================
INSERT INTO expenses (expense_date, category, amount, note) VALUES
    -- Podestýlka
    (DATE_SUB(CURDATE(), INTERVAL 190 DAY), 'bedding', 180.00, 'Sláma 2 balíky - při pořízení slepic'),
    (DATE_SUB(CURDATE(), INTERVAL 150 DAY), 'bedding', 90.00, 'Sláma 1 balík'),
    (DATE_SUB(CURDATE(), INTERVAL 110 DAY), 'bedding', 90.00, 'Sláma 1 balík'),
    (DATE_SUB(CURDATE(), INTERVAL 70 DAY), 'bedding', 95.00, 'Sláma 1 balík (zdražení)'),
    (DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'bedding', 95.00, 'Sláma 1 balík'),

    -- Veterina
    (DATE_SUB(CURDATE(), INTERVAL 180 DAY), 'vet', 350.00, 'Vstupní prohlídka hejna'),
    (DATE_SUB(CURDATE(), INTERVAL 100 DAY), 'vet', 120.00, 'Odčervení - přípravek'),
    (DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'vet', 250.00, 'Vyšetření Lízy - kulhání'),

    -- Vybavení
    (DATE_SUB(CURDATE(), INTERVAL 192 DAY), 'equipment', 2500.00, 'Krmítko a napáječka'),
    (DATE_SUB(CURDATE(), INTERVAL 192 DAY), 'equipment', 450.00, 'Hřad a snáškové boxy'),
    (DATE_SUB(CURDATE(), INTERVAL 160 DAY), 'equipment', 180.00, 'Nové korýtko na vodu'),
    (DATE_SUB(CURDATE(), INTERVAL 80 DAY), 'equipment', 350.00, 'Automatické dvířka'),
    (DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'equipment', 120.00, 'Náhradní napáječka'),

    -- Ostatní
    (DATE_SUB(CURDATE(), INTERVAL 170 DAY), 'other', 60.00, 'Dezinfekce kurníku'),
    (DATE_SUB(CURDATE(), INTERVAL 90 DAY), 'other', 45.00, 'Dezinfekce + kreolín'),
    (DATE_SUB(CURDATE(), INTERVAL 40 DAY), 'other', 35.00, 'Vitamíny do vody');

-- ==========================================
-- Prodej / darování vajec
-- ==========================================
INSERT INTO egg_transactions (transaction_date, type, quantity, price_total, recipient, note) VALUES
    -- Září - říjen (začátek, málo vajec)
    (DATE_SUB(CURDATE(), INTERVAL 175 DAY), 'gift', 6, 0.00, 'Sousedka Marta', 'První vajíčka'),
    (DATE_SUB(CURDATE(), INTERVAL 165 DAY), 'gift', 10, 0.00, 'Rodiče', NULL),

    -- Listopad
    (DATE_SUB(CURDATE(), INTERVAL 145 DAY), 'sale', 10, 40.00, 'Kolegyně Jana', '4 Kč/ks'),
    (DATE_SUB(CURDATE(), INTERVAL 138 DAY), 'gift', 10, 0.00, 'Sousedka Marta', NULL),
    (DATE_SUB(CURDATE(), INTERVAL 130 DAY), 'sale', 15, 60.00, 'Kolegyně Jana', NULL),

    -- Prosinec
    (DATE_SUB(CURDATE(), INTERVAL 115 DAY), 'sale', 20, 80.00, 'Kolegyně Jana', NULL),
    (DATE_SUB(CURDATE(), INTERVAL 108 DAY), 'gift', 10, 0.00, 'Rodiče', 'Vánoční dárek'),
    (DATE_SUB(CURDATE(), INTERVAL 100 DAY), 'sale', 10, 40.00, 'Soused Pavel', NULL),

    -- Leden
    (DATE_SUB(CURDATE(), INTERVAL 85 DAY), 'sale', 15, 60.00, 'Kolegyně Jana', NULL),
    (DATE_SUB(CURDATE(), INTERVAL 75 DAY), 'gift', 10, 0.00, 'Sousedka Marta', NULL),
    (DATE_SUB(CURDATE(), INTERVAL 68 DAY), 'sale', 20, 90.00, 'Kolegyně Jana', '4,50 Kč/ks - zvýšená poptávka'),

    -- Únor
    (DATE_SUB(CURDATE(), INTERVAL 55 DAY), 'sale', 20, 90.00, 'Kolegyně Jana', NULL),
    (DATE_SUB(CURDATE(), INTERVAL 48 DAY), 'gift', 10, 0.00, 'Rodiče', NULL),
    (DATE_SUB(CURDATE(), INTERVAL 40 DAY), 'sale', 15, 67.50, 'Soused Pavel', NULL),
    (DATE_SUB(CURDATE(), INTERVAL 33 DAY), 'sale', 10, 45.00, 'Kamarádka Eva', 'Nová zákaznice'),

    -- Březen
    (DATE_SUB(CURDATE(), INTERVAL 22 DAY), 'sale', 20, 90.00, 'Kolegyně Jana', NULL),
    (DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'gift', 10, 0.00, 'Sousedka Marta', NULL),
    (DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'sale', 15, 67.50, 'Soused Pavel', NULL),
    (DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'sale', 10, 45.00, 'Kamarádka Eva', NULL);
