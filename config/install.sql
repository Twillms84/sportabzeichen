-- SPORTABZEICHEN MODULE DATABASE SETUP

CREATE TABLE IF NOT EXISTS sportabzeichen_disciplines (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    kategorie TEXT NOT NULL,
    einheit TEXT NOT NULL,
    berechnungsart TEXT DEFAULT 'GREATER',
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS sportabzeichen_exams (
    id SERIAL PRIMARY KEY,
    exam_name TEXT,
    exam_date DATE,
    exam_year INT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS sportabzeichen_participants (
    id SERIAL PRIMARY KEY,
    import_id TEXT NOT NULL UNIQUE,
    vorname TEXT,
    nachname TEXT,
    geschlecht TEXT,
    geburtsdatum DATE,
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS sportabzeichen_requirements (
    id SERIAL PRIMARY KEY,
    discipline_id INT NOT NULL REFERENCES sportabzeichen_disciplines(id) ON DELETE CASCADE,
    jahr INT NOT NULL,
    altersklasse TEXT NOT NULL,
    geschlecht TEXT NOT NULL,
    bronze DOUBLE PRECISION,
    silber DOUBLE PRECISION,
    gold DOUBLE PRECISION,
    schwimmnachweis BOOLEAN DEFAULT FALSE
);

CREATE UNIQUE INDEX IF NOT EXISTS uniq_req 
ON sportabzeichen_requirements (discipline_id, jahr, altersklasse, geschlecht);

CREATE TABLE IF NOT EXISTS sportabzeichen_exam_participants (
    id SERIAL PRIMARY KEY,
    exam_id INT NOT NULL REFERENCES sportabzeichen_exams(id) ON DELETE CASCADE,
    participant_id INT NOT NULL REFERENCES sportabzeichen_participants(id) ON DELETE CASCADE,
    UNIQUE (exam_id, participant_id)
);

CREATE TABLE IF NOT EXISTS sportabzeichen_exam_results (
    id SERIAL PRIMARY KEY,
    ep_id INT NOT NULL REFERENCES sportabzeichen_exam_participants(id) ON DELETE CASCADE,
    discipline_id INT NOT NULL REFERENCES sportabzeichen_disciplines(id),
    leistung DOUBLE PRECISION,
    stufe TEXT,
    points INT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);
