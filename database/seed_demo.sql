-- Šťastné slepice – demo data za poslední týden
-- Spustit v phpMyAdmin proti databázi d42462_slepice

USE d42462_slepice;

-- Zápisy vajec (posledních 7 dní)
INSERT INTO egg_records (record_date, egg_count, note) VALUES
    (DATE_SUB(CURDATE(), INTERVAL 6 DAY), 5, NULL),
    (DATE_SUB(CURDATE(), INTERVAL 5 DAY), 6, 'jedno vejce dvojžloutkové'),
    (DATE_SUB(CURDATE(), INTERVAL 4 DAY), 7, NULL),
    (DATE_SUB(CURDATE(), INTERVAL 3 DAY), 4, 'dvě slepice nenesly'),
    (DATE_SUB(CURDATE(), INTERVAL 2 DAY), 6, NULL),
    (DATE_SUB(CURDATE(), INTERVAL 1 DAY), 7, 'všechna velká'),
    (CURDATE(), 7, '2 menší')
ON DUPLICATE KEY UPDATE egg_count = VALUES(egg_count), note = VALUES(note);

-- Klimatická data – kurník (3x denně po 7 dní)
INSERT INTO climate_records (recorded_at, location, temperature, humidity) VALUES
    -- den -6
    (DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 7 HOUR, 'coop', 18.5, 62.0),
    (DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 13 HOUR, 'coop', 23.0, 55.0),
    (DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 20 HOUR, 'coop', 20.2, 58.0),
    -- den -5
    (DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 7 HOUR, 'coop', 17.8, 64.0),
    (DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 13 HOUR, 'coop', 22.5, 56.0),
    (DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 20 HOUR, 'coop', 19.8, 60.0),
    -- den -4
    (DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 7 HOUR, 'coop', 19.0, 60.0),
    (DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 13 HOUR, 'coop', 24.2, 52.0),
    (DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 20 HOUR, 'coop', 21.0, 57.0),
    -- den -3
    (DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 7 HOUR, 'coop', 16.5, 68.0),
    (DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 13 HOUR, 'coop', 21.0, 58.0),
    (DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 20 HOUR, 'coop', 18.5, 63.0),
    -- den -2
    (DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 7 HOUR, 'coop', 18.0, 61.0),
    (DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 13 HOUR, 'coop', 23.5, 54.0),
    (DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 20 HOUR, 'coop', 20.5, 58.0),
    -- den -1
    (DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 7 HOUR, 'coop', 19.2, 59.0),
    (DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 13 HOUR, 'coop', 24.8, 51.0),
    (DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 20 HOUR, 'coop', 21.5, 56.0),
    -- dnes
    (CURDATE() + INTERVAL 7 HOUR, 'coop', 18.8, 60.0),
    (CURDATE() + INTERVAL 12 HOUR, 'coop', 24.0, 55.0);

-- Klimatická data – venku (3x denně po 7 dní)
INSERT INTO climate_records (recorded_at, location, temperature, humidity) VALUES
    -- den -6
    (DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 7 HOUR, 'outdoor', 4.5, 78.0),
    (DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 13 HOUR, 'outdoor', 9.0, 65.0),
    (DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 20 HOUR, 'outdoor', 5.5, 75.0),
    -- den -5
    (DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 7 HOUR, 'outdoor', 3.0, 82.0),
    (DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 13 HOUR, 'outdoor', 8.5, 68.0),
    (DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 20 HOUR, 'outdoor', 4.8, 77.0),
    -- den -4
    (DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 7 HOUR, 'outdoor', 5.2, 75.0),
    (DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 13 HOUR, 'outdoor', 11.0, 60.0),
    (DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 20 HOUR, 'outdoor', 6.8, 72.0),
    -- den -3
    (DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 7 HOUR, 'outdoor', 2.0, 85.0),
    (DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 13 HOUR, 'outdoor', 7.5, 70.0),
    (DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 20 HOUR, 'outdoor', 3.5, 80.0),
    -- den -2
    (DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 7 HOUR, 'outdoor', 6.0, 72.0),
    (DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 13 HOUR, 'outdoor', 12.0, 58.0),
    (DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 20 HOUR, 'outdoor', 7.2, 70.0),
    -- den -1
    (DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 7 HOUR, 'outdoor', 5.5, 74.0),
    (DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 13 HOUR, 'outdoor', 11.5, 62.0),
    (DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 20 HOUR, 'outdoor', 6.5, 71.0),
    -- dnes
    (CURDATE() + INTERVAL 7 HOUR, 'outdoor', 4.8, 76.0),
    (CURDATE() + INTERVAL 12 HOUR, 'outdoor', 10.0, 70.0);

-- Poznámky
INSERT INTO notes (note_date, content) VALUES
    (DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Doplněna voda a krmivo'),
    (DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Jedna slepice kulhá – sledovat'),
    (DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Kurník uklizený, nová sláma'),
    (DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Slepice co kulhala je v pořádku'),
    (DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Kurník uklizený'),
    (CURDATE(), '2 vejce byla menší');
