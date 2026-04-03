-- ============================================================
-- MODULO ESAMI/QUIZ - Tabelle aggiuntive per regsx_class
-- ============================================================

-- Tabella principale degli esami
CREATE TABLE `esami` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titolo` varchar(255) NOT NULL,
  `ID_Classe` int(11) NOT NULL,
  `ID_Materia` int(11) DEFAULT NULL,
  `num_domande` int(11) NOT NULL,
  `durata_minuti` int(11) NOT NULL DEFAULT 30,
  `creato_da` int(11) NOT NULL,
  `data_creazione` datetime NOT NULL DEFAULT current_timestamp(),
  `attivo` tinyint(1) NOT NULL DEFAULT 0,
  `codice_accesso` varchar(20) DEFAULT NULL,
  `scadenza_codice` datetime DEFAULT NULL,
  `inserisci_in_voti` tinyint(1) NOT NULL DEFAULT 0,
  `ID_Materia_voto` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_esame_classe` (`ID_Classe`),
  KEY `fk_esame_materia` (`ID_Materia`),
  KEY `fk_esame_docente` (`creato_da`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Domande dell'esame
CREATE TABLE `esami_domande` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `esame_id` int(11) NOT NULL,
  `testo` text NOT NULL,
  `ordine` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_domanda_esame` (`esame_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Opzioni di risposta per ogni domanda
CREATE TABLE `esami_risposte` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domanda_id` int(11) NOT NULL,
  `testo` varchar(500) NOT NULL,
  `corretta` tinyint(1) NOT NULL DEFAULT 0,
  `lettera` char(1) NOT NULL DEFAULT 'A',
  PRIMARY KEY (`id`),
  KEY `fk_risposta_domanda` (`domanda_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sessioni d'esame dei candidati
CREATE TABLE `esami_sessioni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `esame_id` int(11) NOT NULL,
  `nome_candidato` varchar(100) NOT NULL,
  `cognome_candidato` varchar(100) NOT NULL,
  `ID_Studente` int(11) DEFAULT NULL,
  `iniziato_il` datetime NOT NULL DEFAULT current_timestamp(),
  `terminato_il` datetime DEFAULT NULL,
  `punteggio` decimal(5,2) DEFAULT NULL,
  `completato` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_sessione_esame` (`esame_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Risposte date dai candidati
CREATE TABLE `esami_risposte_candidati` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sessione_id` int(11) NOT NULL,
  `domanda_id` int(11) NOT NULL,
  `risposta_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sessione_domanda` (`sessione_id`, `domanda_id`),
  KEY `fk_rc_sessione` (`sessione_id`),
  KEY `fk_rc_domanda` (`domanda_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Vincoli di integrità referenziale
ALTER TABLE `esami`
  ADD CONSTRAINT `fk_esame_classe` FOREIGN KEY (`ID_Classe`) REFERENCES `classi` (`ID_Classe`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_esame_docente` FOREIGN KEY (`creato_da`) REFERENCES `docente` (`ID_Docente`);

ALTER TABLE `esami_domande`
  ADD CONSTRAINT `fk_domanda_esame` FOREIGN KEY (`esame_id`) REFERENCES `esami` (`id`) ON DELETE CASCADE;

ALTER TABLE `esami_risposte`
  ADD CONSTRAINT `fk_risposta_domanda` FOREIGN KEY (`domanda_id`) REFERENCES `esami_domande` (`id`) ON DELETE CASCADE;

ALTER TABLE `esami_sessioni`
  ADD CONSTRAINT `fk_sessione_esame` FOREIGN KEY (`esame_id`) REFERENCES `esami` (`id`) ON DELETE CASCADE;

ALTER TABLE `esami_risposte_candidati`
  ADD CONSTRAINT `fk_rc_sessione` FOREIGN KEY (`sessione_id`) REFERENCES `esami_sessioni` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rc_domanda` FOREIGN KEY (`domanda_id`) REFERENCES `esami_domande` (`id`) ON DELETE CASCADE;
