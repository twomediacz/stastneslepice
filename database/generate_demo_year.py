#!/usr/bin/env python3
"""
Generátor demo dat za poslední rok → SQL soubor.

- climate_records: hodinové záznamy teploty a vlhkosti (coop + outdoor)
- egg_records:     denní snůška vajec

Spuštění:  python3 database/generate_demo_year.py
Výstup:    database/seed_demo_year.sql
"""

import math
import os
import zlib
from datetime import datetime, timedelta

OUTPUT = os.path.join(os.path.dirname(__file__), "seed_demo_year.sql")

# ── Pomocné funkce ──────────────────────────────────────────────────────────


def crc(s):
    return zlib.crc32(s.encode()) & 0xFFFFFFFF


def outdoor_temperature(day_of_year, hour, noise):
    year_phase = 2 * math.pi * (day_of_year - 15) / 365
    seasonal_base = 9.0 + 12.0 * math.sin(year_phase - math.pi / 2)

    daily_amplitude = 4.0 + 3.0 * math.sin(year_phase - math.pi / 2)
    hour_phase = 2 * math.pi * (hour - 4) / 24
    daily_variation = daily_amplitude * math.sin(hour_phase - math.pi / 2)

    return round(seasonal_base + daily_variation + noise, 1)


def outdoor_humidity(temperature, day_of_year, noise):
    base = 75.0 - temperature * 1.2
    year_phase = 2 * math.pi * (day_of_year - 15) / 365
    seasonal_offset = -5.0 * math.sin(year_phase - math.pi / 2)
    return round(max(30.0, min(98.0, base + seasonal_offset + noise)), 1)


def coop_temperature(outdoor_temp, day_of_year, noise):
    year_phase = 2 * math.pi * (day_of_year - 15) / 365
    offset = 8.0 - 4.0 * math.sin(year_phase - math.pi / 2)
    return round(outdoor_temp + offset + noise, 1)


def coop_humidity(outdoor_hum, noise):
    return round(max(35.0, min(95.0, outdoor_hum + 5.0 + noise)), 1)


def daily_egg_count(day_of_year, noise):
    year_phase = 2 * math.pi * (day_of_year - 15) / 365
    base = 4.0 + 2.5 * math.sin(year_phase - math.pi / 2)
    count = round(base + noise)
    return max(0, min(7, count))


EGG_NOTES = [
    "jedno vejce dvojžloutkové",
    "dvě slepice nenesly",
    "všechna velká",
    "jedno vejce měkké",
    "menší vejce",
    "slepice neklidné",
    "velmi teplý den",
    "silný vítr",
    "slepice byly venku celý den",
    "doplněno krmivo",
    "nová sláma v hnízdech",
    "kontrola zdraví – vše OK",
    "jedno vejce nalezeno mimo hnízdo",
    "dva žloutky v jednom vejci",
]


def egg_note(day_index):
    h = crc(f"note-{day_index}")
    if h % 100 > 15:
        return None
    return EGG_NOTES[h % len(EGG_NOTES)]


# ── Generování ──────────────────────────────────────────────────────────────

now = datetime.now().replace(minute=0, second=0, microsecond=0)
start = now - timedelta(days=365)

print("Generuji demo data za 1 rok...")

climate_rows = []
egg_rows = []

# Klimatická data – hodinově
current = start
while current <= now:
    day_of_year = current.timetuple().tm_yday
    hour = current.hour
    day_key = current.strftime("%Y-%m-%d-%H")

    noise_ot = (crc(f"ot-{day_key}") % 200 - 100) / 100.0 * 2.0
    noise_oh = (crc(f"oh-{day_key}") % 200 - 100) / 100.0 * 5.0
    noise_ct = (crc(f"ct-{day_key}") % 200 - 100) / 100.0 * 1.5
    noise_ch = (crc(f"ch-{day_key}") % 200 - 100) / 100.0 * 4.0

    out_t = outdoor_temperature(day_of_year, hour, noise_ot)
    out_h = outdoor_humidity(out_t, day_of_year, noise_oh)
    cp_t = coop_temperature(out_t, day_of_year, noise_ct)
    cp_h = coop_humidity(out_h, noise_ch)

    recorded_at = current.strftime("%Y-%m-%d %H:%M:%S")
    climate_rows.append(
        f"('{recorded_at}', 'outdoor', {out_t}, {out_h})"
    )
    climate_rows.append(
        f"('{recorded_at}', 'coop', {cp_t}, {cp_h})"
    )

    current += timedelta(hours=1)

# Snůška vajec – denně
current_day = start.date()
end_day = now.date()
day_index = 0
total_eggs = 0

while current_day <= end_day:
    day_of_year = current_day.timetuple().tm_yday
    noise_e = (crc(f"egg-{day_index}") % 200 - 100) / 100.0 * 1.5

    eggs = daily_egg_count(day_of_year, noise_e)
    total_eggs += eggs
    note = egg_note(day_index)
    note_sql = f"'{note}'" if note else "NULL"

    egg_rows.append(
        f"('{current_day.isoformat()}', {eggs}, {note_sql})"
    )

    day_index += 1
    current_day += timedelta(days=1)

# ── Zápis SQL ────────────────────────────────────────────────────────────────

# Rozdělíme velké INSERT do dávek po 500 řádcích (MariaDB/MySQL limit).
BATCH = 500

with open(OUTPUT, "w", encoding="utf-8") as f:
    f.write("-- Šťastné slepice – demo data za poslední rok\n")
    f.write("-- Vygenerováno skriptem generate_demo_year.py\n")
    f.write(f"-- Datum generování: {datetime.now().strftime('%Y-%m-%d %H:%M')}\n\n")

    f.write("-- Smazání starých záznamů\n")
    f.write("DELETE FROM climate_records;\n")
    f.write("DELETE FROM egg_records;\n\n")

    # Climate records
    f.write(f"-- Klimatická data ({len(climate_rows)} záznamů)\n")
    for i in range(0, len(climate_rows), BATCH):
        batch = climate_rows[i : i + BATCH]
        f.write(
            "INSERT INTO climate_records (recorded_at, location, temperature, humidity) VALUES\n"
        )
        f.write(",\n".join(f"    {row}" for row in batch))
        f.write(";\n\n")

    # Egg records
    f.write(f"-- Snůška vajec ({len(egg_rows)} záznamů, {total_eggs} vajec celkem)\n")
    for i in range(0, len(egg_rows), BATCH):
        batch = egg_rows[i : i + BATCH]
        f.write(
            "INSERT INTO egg_records (record_date, egg_count, note) VALUES\n"
        )
        f.write(",\n".join(f"    {row}" for row in batch))
        f.write("\n    ON DUPLICATE KEY UPDATE egg_count = VALUES(egg_count), note = VALUES(note);\n\n")

print(f"Klima: {len(climate_rows)} záznamů (2 lokace × {len(climate_rows) // 2} hodin)")
print(f"Vejce: {len(egg_rows)} denních záznamů ({total_eggs} vajec celkem)")
print(f"SQL soubor: {OUTPUT}")
print(f"Velikost: {os.path.getsize(OUTPUT) / 1024 / 1024:.1f} MB")
