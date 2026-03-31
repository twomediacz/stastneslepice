-- Rollback migrace 010_events.sql
-- Pořadí je důležité kvůli foreign key závislostem

DROP TABLE IF EXISTS event_wod_images;
DROP TABLE IF EXISTS event_wod_videos;
DROP TABLE IF EXISTS event_wods;
DROP TABLE IF EXISTS event_categories;
DROP TABLE IF EXISTS event_infoboxes;
DROP TABLE IF EXISTS events;
