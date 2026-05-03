-- Šťastné slepice – demo data: evidence slepic

USE d42462_slepice;

INSERT INTO chickens (name, breed, color, ring_color, birth_date, acquired_date, status, note) VALUES
    ('Běla', 'Dominant Kropenatý', 'bílá s hnědými skvrnami', '#f2c94c', '2025-07-26', '2025-09-15', 'active', 'Nejvíce nese, vůdkyně hejna'),
    ('Kvočna', 'Dominant Kropenatý', 'hnědá kropenatá', '#eb5757', '2025-07-26', '2025-09-15', 'active', 'Ráda sedí na vejcích'),
    ('Zrzka', 'Dominant Kropenatý', 'rezavě hnědá', '#f2994a', '2025-07-26', '2025-09-15', 'active', NULL),
    ('Černuška', 'Dominant Kropenatý', 'černá s bílým náprsníkem', '#2f80ed', '2025-07-26', '2025-09-15', 'active', 'Nejkrotší, nechá se pohladit'),
    ('Pepina', 'Dominant Kropenatý', 'světle hnědá', '#27ae60', '2025-07-26', '2025-09-15', 'active', NULL),
    ('Líza', 'Dominant Kropenatý', 'tmavě hnědá', '#9b51e0', '2025-07-26', '2025-09-15', 'sick', 'Kulhá na levou nohu od 20.03.'),
    ('Kulička', 'Dominant Kropenatý', 'bílá', '#56ccf2', '2025-07-26', '2025-09-15', 'active', 'Nejmenší z hejna');
