-- Neue, saubere Tabelle für Disziplinanforderungen
CREATE TABLE sportabzeichen_requirements (
    id              SERIAL PRIMARY KEY,
    nummer          INTEGER NOT NULL,           -- aus CSV (z. B. 1)
    jahr            INTEGER NOT NULL,           -- Jahr (z. B. 2025)
    altersklasse    TEXT NOT NULL,              -- z. B. AC0607
    geschlecht      TEXT NOT NULL,              -- MALE / FEMALE
    auswahlnummer   INTEGER NOT NULL,           -- Disziplinnummer
    disziplin       TEXT NOT NULL,              -- z. B. 800 m Lauf
    kategorie       TEXT NOT NULL,              -- ENDURANCE, STRENGTH, SPEED, etc.
    bronze          REAL,
    silber          REAL,
    gold            REAL,
    abzeichen       TEXT,
    einheit         TEXT,
    schwimmnachweis BOOLEAN DEFAULT false,
    berechnungsart  TEXT DEFAULT 'GREATER',
    created_at      TIMESTAMPTZ DEFAULT NOW(),

    CONSTRAINT unique_requirement UNIQUE (
        jahr, nummer, altersklasse, geschlecht, auswahlnummer, disziplin
    )
);

-- Berechtigungen für Symfony/IServ ORM
GRANT USAGE, SELECT ON sportabzeichen_requirements_id_seq TO symfony;
GRANT SELECT, INSERT, UPDATE, DELETE ON sportabzeichen_requirements TO symfony;
