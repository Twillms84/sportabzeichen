-- Deutsche Sportabzeichen Prüfungsdaten
-- Verknüpft mit sportabzeichen_participants über participant_id

CREATE TABLE sportabzeichen_exams (
    id                      SERIAL PRIMARY KEY,
    import_id               TEXT NOT NULL REFERENCES sportabzeichen_participants(import_id) ON DELETE CASCADE,
    participant_id          INTEGER NOT NULL REFERENCES sportabzeichen_participants(id) ON DELETE CASCADE,
    jahr                    INTEGER NOT NULL,
    klasse                  TEXT,
    koordination_disziplin  TEXT,
    koordination_wert       TEXT,
    ausdauer_disziplin      TEXT,
    ausdauer_wert           TEXT,
    schnelligkeit_disziplin TEXT,
    schnelligkeit_wert      TEXT,
    kraft_disziplin         TEXT,
    kraft_wert              TEXT,
    schwimmnachweis         BOOLEAN DEFAULT false,
    gesamtpunkte            INTEGER DEFAULT 0,
    updated_at              TIMESTAMPTZ DEFAULT NOW()
);

GRANT USAGE, SELECT ON sportabzeichen_exams_id_seq TO symfony;
GRANT SELECT, INSERT, UPDATE, DELETE ON sportabzeichen_exams TO symfony;
