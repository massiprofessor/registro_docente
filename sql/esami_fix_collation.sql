-- Fix collation per compatibilità con MariaDB moderno
-- Esegui questo in phpMyAdmin → regsx_class → SQL

ALTER TABLE `esami`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;

ALTER TABLE `esami_domande`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;

ALTER TABLE `esami_risposte`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;

ALTER TABLE `esami_sessioni`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;

ALTER TABLE `esami_risposte_candidati`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;

-- Verifica finale
SELECT TABLE_NAME, TABLE_COLLATION 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'regsx_class'
ORDER BY TABLE_NAME;
