-- Šťastné slepice – demo data: evidence slepic

USE d42462_slepice;

INSERT INTO chickens (name, breed, color, birth_date, acquired_date, status, note) VALUES
    ('Běla', 'Dominant Kropenatý', 'bílá s hnědými skvrnami', '2025-07-26', '2025-09-15', 'active', 'Nejvíce nese, vůdkyně hejna'),
    ('Kvočna', 'Dominant Kropenatý', 'hnědá kropenatá', '2025-07-26', '2025-09-15', 'active', 'Ráda sedí na vejcích'),
    ('Zrzka', 'Dominant Kropenatý', 'rezavě hnědá', '2025-07-26', '2025-09-15', 'active', NULL),
    ('Černuška', 'Dominant Kropenatý', 'černá s bílým náprsníkem', '2025-07-26', '2025-09-15', 'active', 'Nejkrotší, nechá se pohladit'),
    ('Pepina', 'Dominant Kropenatý', 'světle hnědá', '2025-07-26', '2025-09-15', 'active', NULL),
    ('Líza', 'Dominant Kropenatý', 'tmavě hnědá', '2025-07-26', '2025-09-15', 'sick', 'Kulhá na levou nohu od 20.03.'),
    ('Kulička', 'Dominant Kropenatý', 'bílá', '2025-07-26', '2025-09-15', 'active', 'Nejmenší z hejna');
