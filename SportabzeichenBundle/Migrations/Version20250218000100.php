<?php

declare(strict_types=1);

namespace PulsR\SportabzeichenBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250218000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema for Sportabzeichen module including exams, participants, requirements, exam_participants and exam_results.';
    }

    public function up(Schema $schema): void
    {
        // ---------------------------------------------------------
        // sportabzeichen_exams
        // ---------------------------------------------------------
        $this->addSql("
            CREATE TABLE sportabzeichen_exams (
                id SERIAL PRIMARY KEY,
                exam_name   TEXT,
                exam_date   DATE,
                exam_year   INT NOT NULL,
                created_at  TIMESTAMPTZ DEFAULT NOW(),
                updated_at  TIMESTAMPTZ DEFAULT NOW()
            );
        ");

        // ---------------------------------------------------------
        // sportabzeichen_participants
        // ---------------------------------------------------------
        $this->addSql("
            CREATE TABLE sportabzeichen_participants (
                id              SERIAL PRIMARY KEY,
                import_id       TEXT NOT NULL UNIQUE,
                vorname         TEXT,
                nachname        TEXT,
                geschlecht      TEXT,
                geburtsdatum    DATE,
                updated_at      TIMESTAMPTZ DEFAULT NOW()
            );
        ");

        // ---------------------------------------------------------
        // sportabzeichen_requirements
        // Dies ist deine bestehende Tabelle, angepasst auf vollständiges Schema
        // ---------------------------------------------------------
        $this->addSql("
            CREATE TABLE sportabzeichen_requirements (
                id              SERIAL PRIMARY KEY,
                jahr            INT NOT NULL,
                altersklasse    TEXT NOT NULL,
                geschlecht      TEXT NOT NULL,
                auswahlnummer   INT NOT NULL,
                disziplin       TEXT NOT NULL,
                kategorie       TEXT NOT NULL,
                bronze          DOUBLE PRECISION,
                silber          DOUBLE PRECISION,
                gold            DOUBLE PRECISION,
                einheit         TEXT,
                schwimmnachweis BOOLEAN DEFAULT FALSE,
                berechnungsart  TEXT DEFAULT 'GREATER'
            );
        ");

        // ---------------------------------------------------------
        // sportabzeichen_exam_participants
        // ---------------------------------------------------------
        $this->addSql("
            CREATE TABLE sportabzeichen_exam_participants (
                id              SERIAL PRIMARY KEY,
                exam_id         INT NOT NULL REFERENCES sportabzeichen_exams(id) ON DELETE CASCADE,
                participant_id  INT NOT NULL REFERENCES sportabzeichen_participants(id) ON DELETE CASCADE,
                age_year        INT NOT NULL,
                UNIQUE (exam_id, participant_id)
            );
        ");

        // ---------------------------------------------------------
        // sportabzeichen_exam_results
        // ---------------------------------------------------------
        $this->addSql("
            CREATE TABLE sportabzeichen_exam_results (
                id              SERIAL PRIMARY KEY,
                ep_id           INT NOT NULL REFERENCES sportabzeichen_exam_participants(id) ON DELETE CASCADE,
                disziplin       TEXT NOT NULL,
                kategorie       TEXT NOT NULL,
                auswahlnummer   INT NOT NULL,
                leistung        DOUBLE PRECISION,
                stufe           TEXT
            );
        ");

        // ---------------------------------------------------------
        // GRANTS für IServ / Symfony
        // ---------------------------------------------------------

        // Sequences
        $this->addSql("GRANT USAGE, SELECT ON SEQUENCE sportabzeichen_exams_id_seq TO symfony;");
        $this->addSql("GRANT USAGE, SELECT ON SEQUENCE sportabzeichen_participants_id_seq TO symfony;");
        $this->addSql("GRANT USAGE, SELECT ON SEQUENCE sportabzeichen_requirements_id_seq TO symfony;");
        $this->addSql("GRANT USAGE, SELECT ON SEQUENCE sportabzeichen_exam_participants_id_seq TO symfony;");
        $this->addSql("GRANT USAGE, SELECT ON SEQUENCE sportabzeichen_exam_results_id_seq TO symfony;");

        // Tabellen
        $this->addSql("GRANT SELECT, INSERT, UPDATE, DELETE ON sportabzeichen_exams TO symfony;");
        $this->addSql("GRANT SELECT, INSERT, UPDATE, DELETE ON sportabzeichen_participants TO symfony;");
        $this->addSql("GRANT SELECT, INSERT, UPDATE, DELETE ON sportabzeichen_requirements TO symfony;");
        $this->addSql("GRANT SELECT, INSERT, UPDATE, DELETE ON sportabzeichen_exam_participants TO symfony;");
        $this->addSql("GRANT SELECT, INSERT, UPDATE, DELETE ON sportabzeichen_exam_results TO symfony;");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS sportabzeichen_exam_results;");
        $this->addSql("DROP TABLE IF EXISTS sportabzeichen_exam_participants;");
        $this->addSql("DROP TABLE IF EXISTS sportabzeichen_requirements;");
        $this->addSql("DROP TABLE IF EXISTS sportabzeichen_participants;");
        $this->addSql("DROP TABLE IF EXISTS sportabzeichen_exams;");
    }
}
