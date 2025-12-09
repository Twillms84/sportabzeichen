-- Sportabzeichen-Modul: Teilnehmer-Tabelle
-- Enthält Zuordnung zwischen Import-ID (aus Schulverwaltung) und IServ-Benutzerdaten.

CREATE TABLE sportabzeichen_participants (
    id              SERIAL PRIMARY KEY,
    import_id       TEXT NOT NULL UNIQUE,
    vorname         TEXT,
    nachname        TEXT,
    geschlecht      TEXT,
    geburtsdatum    DATE,
    updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- Berechtigungen für Symfony / IServ-Core
GRANT USAGE, SELECT ON sportabzeichen_participants_id_seq TO symfony;
GRANT SELECT, INSERT, UPDATE, DELETE ON sportabzeichen_participants TO symfony;
