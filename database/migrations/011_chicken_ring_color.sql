-- Šťastné slepice – migrace 011: barva rozlišovacího kroužku slepice

USE d42462_slepice;

ALTER TABLE chickens
    ADD COLUMN ring_color CHAR(7) DEFAULT NULL AFTER color;
