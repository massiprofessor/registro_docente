-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Creato il: Dic 18, 2024 alle 23:15
-- Versione del server: 11.4.3-MariaDB-1
-- Versione PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `regsx_class`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `argomenti`
--

CREATE TABLE `argomenti` (
  `id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `argomenti`
--

INSERT INTO `argomenti` (`id`, `materia_id`, `nome`) VALUES
(2, 7, 'Capitolo 1'),
(5, 7, 'Capitolo 2'),
(6, 7, 'Capitolo 3'),
(7, 7, 'Capitolo 4'),
(11, 7, 'Capitolo 5'),
(12, 7, 'Capitolo 6'),
(13, 7, 'Capitolo 7'),
(14, 7, 'Esercitazioni'),
(15, 7, 'Esercitazioni VLAN'),
(16, 7, 'Capitolo 7'),
(17, 7, 'Capitolo 8'),
(18, 7, 'Capitolo 9'),
(19, 7, 'Capitolo 10'),
(20, 7, 'Capitolo 11'),
(21, 7, 'Capitolo 12'),
(22, 7, 'Capitolo 13'),
(23, 7, 'Esercitazioni Sicurezza'),
(24, 7, 'Esercitazioni Routing'),
(25, 7, 'Capitolo 14'),
(26, 7, 'Capitolo 15'),
(27, 7, 'Capitolo 16'),
(28, 7, 'Ripetizione'),
(29, 7, 'Esame CCNA2');

-- --------------------------------------------------------

--
-- Struttura della tabella `classi`
--

CREATE TABLE `classi` (
  `ID_Classe` int(11) NOT NULL,
  `Nome` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `classi`
--

INSERT INTO `classi` (`ID_Classe`, `Nome`) VALUES
(16, 'ITBAR1');

-- --------------------------------------------------------

--
-- Struttura della tabella `classi_docenti`
--

CREATE TABLE `classi_docenti` (
  `ID` int(11) NOT NULL,
  `ID_D` int(11) NOT NULL,
  `ID_C` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `classi_docenti`
--

INSERT INTO `classi_docenti` (`ID`, `ID_D`, `ID_C`) VALUES
(19, 6, 16);

-- --------------------------------------------------------

--
-- Struttura della tabella `classi_materie`
--

CREATE TABLE `classi_materie` (
  `id` int(11) NOT NULL,
  `ID_C` int(11) NOT NULL,
  `ID_M` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `classi_materie`
--

INSERT INTO `classi_materie` (`id`, `ID_C`, `ID_M`) VALUES
(73, 16, 3),
(74, 16, 4),
(75, 16, 5),
(76, 16, 6),
(77, 16, 7),
(78, 16, 8),
(79, 16, 9),
(80, 16, 10),
(81, 16, 11),
(82, 16, 18);

-- --------------------------------------------------------

--
-- Struttura della tabella `docente`
--

CREATE TABLE `docente` (
  `ID_Docente` int(11) NOT NULL,
  `Username` varchar(30) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `root` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `docente`
--

INSERT INTO `docente` (`ID_Docente`, `Username`, `Email`, `Password`, `root`) VALUES
(6, 'massi.professor', 'massi.professor@gmail.com', '$2y$10$QD9lj7gvuzbTSwttHaGoheg0HcXuIT/Vhw/AjmOOUn2zUeThS.cEy', 'SI'),
(8, 'Avella', 'avella@gmail.com', '$2y$10$LknrbAmEuCqDzJwB0VheDu33GqL7lg97/lbEbvGpd03OGxC3uHvri', 'NO');

-- --------------------------------------------------------

--
-- Struttura della tabella `impostazioni_classe`
--

CREATE TABLE `impostazioni_classe` (
  `ID_Setting` int(11) NOT NULL,
  `ID_Classe` int(11) NOT NULL,
  `Ore_Mattina` int(11) NOT NULL,
  `Ore_Pomeriggio` int(11) NOT NULL,
  `Ore_Totali_Corso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `impostazioni_classe`
--

INSERT INTO `impostazioni_classe` (`ID_Setting`, `ID_Classe`, `Ore_Mattina`, `Ore_Pomeriggio`, `Ore_Totali_Corso`) VALUES
(3, 16, 4, 4, 662);

-- --------------------------------------------------------

--
-- Struttura della tabella `lezioni`
--

CREATE TABLE `lezioni` (
  `id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `numero_lezione` int(11) NOT NULL,
  `scheduling_id` int(11) NOT NULL,
  `argomento_id` int(11) DEFAULT NULL,
  `completato` tinyint(1) DEFAULT 0,
  `commento` text DEFAULT NULL,
  `classe_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `lezioni`
--

INSERT INTO `lezioni` (`id`, `materia_id`, `numero_lezione`, `scheduling_id`, `argomento_id`, `completato`, `commento`, `classe_id`) VALUES
(105, 7, 1, 1, NULL, 1, '', 16),
(106, 7, 2, 1, NULL, 1, '', 16),
(107, 7, 3, 1, NULL, 1, '', 16),
(108, 7, 4, 1, NULL, 1, '', 16),
(109, 7, 5, 1, NULL, 1, '', 16),
(110, 7, 6, 1, NULL, 1, '', 16),
(111, 7, 7, 1, NULL, 1, '', 16),
(112, 7, 8, 1, NULL, 1, '', 16),
(113, 7, 9, 1, NULL, 1, '', 16),
(114, 7, 10, 1, NULL, 1, '', 16),
(115, 7, 11, 1, NULL, 1, '', 16),
(116, 7, 12, 1, NULL, 1, '', 16),
(117, 7, 13, 1, NULL, 0, '', 16),
(118, 7, 14, 1, NULL, 0, '', 16);

-- --------------------------------------------------------

--
-- Struttura della tabella `lezioni_argomenti`
--

CREATE TABLE `lezioni_argomenti` (
  `id` int(11) NOT NULL,
  `lezione_id` int(11) NOT NULL,
  `scheduling_id` int(11) NOT NULL,
  `argomento_id` int(11) NOT NULL,
  `completato` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `lezioni_argomenti`
--

INSERT INTO `lezioni_argomenti` (`id`, `lezione_id`, `scheduling_id`, `argomento_id`, `completato`) VALUES
(31, 105, 1, 2, 1),
(32, 105, 1, 5, 1),
(33, 107, 1, 6, 1),
(34, 107, 1, 7, 1),
(35, 108, 1, 11, 1),
(36, 108, 1, 12, 1),
(37, 106, 1, 14, 1),
(38, 109, 1, 15, 1),
(39, 110, 1, 13, 1),
(40, 110, 1, 17, 1),
(41, 111, 1, 15, 1),
(42, 112, 1, 18, 1),
(43, 112, 1, 19, 1),
(44, 112, 1, 20, 1),
(45, 113, 1, 23, 1),
(46, 114, 1, 25, 1),
(47, 114, 1, 26, 1),
(48, 115, 1, 24, 1),
(49, 116, 1, 21, 1),
(50, 116, 1, 22, 1),
(51, 116, 1, 27, 1),
(52, 117, 1, 28, 0),
(53, 118, 1, 29, 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `materie`
--

CREATE TABLE `materie` (
  `ID_Materia` int(11) NOT NULL,
  `Materia` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `materie`
--

INSERT INTO `materie` (`ID_Materia`, `Materia`) VALUES
(3, 'Linux'),
(4, 'Networking'),
(5, 'Security'),
(6, 'CCNA1'),
(7, 'CCNA2'),
(8, 'CCNA3'),
(9, 'Python'),
(10, 'AWS'),
(11, 'Database'),
(18, 'Cloud');

-- --------------------------------------------------------

--
-- Struttura della tabella `presenze`
--

CREATE TABLE `presenze` (
  `ID_Presenza` int(11) NOT NULL,
  `ID_Studente` int(11) NOT NULL,
  `ID_Classe` int(11) NOT NULL,
  `Data` date NOT NULL,
  `Presenza_Mattina` varchar(2) NOT NULL,
  `Presenza_Pomeriggio` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `presenze`
--

INSERT INTO `presenze` (`ID_Presenza`, `ID_Studente`, `ID_Classe`, `Data`, `Presenza_Mattina`, `Presenza_Pomeriggio`) VALUES
(12, 13, 16, '2024-12-10', '1', '0'),
(13, 14, 16, '2024-12-10', '1', '0'),
(14, 15, 16, '2024-12-10', '1', '0'),
(15, 16, 16, '2024-12-10', '1', '0'),
(16, 17, 16, '2024-12-10', '1', '0'),
(17, 18, 16, '2024-12-10', '1', '0'),
(18, 19, 16, '2024-12-10', '1', '0'),
(19, 20, 16, '2024-12-10', '1', '0'),
(20, 21, 16, '2024-12-10', '1', '0'),
(21, 22, 16, '2024-12-10', '1', '0'),
(22, 23, 16, '2024-12-10', '1', '0'),
(23, 24, 16, '2024-12-10', '1', '0'),
(24, 25, 16, '2024-12-10', '1', '0'),
(25, 26, 16, '2024-12-10', '1', '0'),
(26, 27, 16, '2024-12-10', '1', '0'),
(27, 28, 16, '2024-12-10', '1', '0'),
(28, 29, 16, '2024-12-10', '1', '0'),
(29, 13, 16, '2024-12-09', '1', '1'),
(30, 14, 16, '2024-12-09', '1', '1'),
(31, 15, 16, '2024-12-09', '1', '1'),
(32, 16, 16, '2024-12-09', '1', '1'),
(33, 17, 16, '2024-12-09', '1', '1'),
(34, 18, 16, '2024-12-09', '1', '1'),
(35, 19, 16, '2024-12-09', '1', '1'),
(36, 20, 16, '2024-12-09', '1', '1'),
(37, 21, 16, '2024-12-09', '1', '1'),
(38, 22, 16, '2024-12-09', '1', '1'),
(39, 23, 16, '2024-12-09', '1', '1'),
(40, 24, 16, '2024-12-09', '1', '1'),
(41, 25, 16, '2024-12-09', '1', '1'),
(42, 26, 16, '2024-12-09', '1', '1'),
(43, 27, 16, '2024-12-09', '1', '1'),
(44, 28, 16, '2024-12-09', '1', '1'),
(45, 29, 16, '2024-12-09', '1', '1'),
(46, 13, 16, '2024-12-06', '1', '1'),
(47, 14, 16, '2024-12-06', '1', '1'),
(48, 15, 16, '2024-12-06', '1', '1'),
(49, 16, 16, '2024-12-06', '1', '1'),
(50, 17, 16, '2024-12-06', '1', '1'),
(51, 18, 16, '2024-12-06', '1', '1'),
(52, 19, 16, '2024-12-06', '1', '1'),
(53, 20, 16, '2024-12-06', '1', '1'),
(54, 21, 16, '2024-12-06', '1', '0'),
(55, 22, 16, '2024-12-06', '0', '1'),
(56, 24, 16, '2024-12-06', '1', '1'),
(57, 25, 16, '2024-12-06', '1', '1'),
(58, 26, 16, '2024-12-06', '1', '1'),
(59, 27, 16, '2024-12-06', '1', '1'),
(60, 28, 16, '2024-12-06', '1', '1'),
(61, 29, 16, '2024-12-06', '1', '1'),
(62, 14, 16, '2024-12-05', '1', '0'),
(63, 15, 16, '2024-12-05', '1', '0'),
(64, 16, 16, '2024-12-05', '1', '0'),
(65, 17, 16, '2024-12-05', '1', '0'),
(66, 19, 16, '2024-12-05', '1', '0'),
(67, 20, 16, '2024-12-05', '1', '0'),
(68, 21, 16, '2024-12-05', '1', '0'),
(69, 22, 16, '2024-12-05', '1', '0'),
(70, 23, 16, '2024-12-05', '1', '0'),
(71, 24, 16, '2024-12-05', '1', '0'),
(72, 25, 16, '2024-12-05', '1', '0'),
(73, 26, 16, '2024-12-05', '1', '0'),
(74, 27, 16, '2024-12-05', '1', '0'),
(75, 28, 16, '2024-12-05', '1', '0'),
(76, 29, 16, '2024-12-05', '1', '0'),
(77, 13, 16, '2024-12-04', '1', '1'),
(78, 14, 16, '2024-12-04', '1', '1'),
(79, 15, 16, '2024-12-04', '1', '1'),
(80, 16, 16, '2024-12-04', '1', '1'),
(81, 17, 16, '2024-12-04', '1', '1'),
(82, 18, 16, '2024-12-04', '1', '1'),
(83, 19, 16, '2024-12-04', '1', '1'),
(84, 20, 16, '2024-12-04', '1', '1'),
(85, 21, 16, '2024-12-04', '1', '0'),
(86, 22, 16, '2024-12-04', '1', '1'),
(87, 23, 16, '2024-12-04', '1', '0'),
(88, 24, 16, '2024-12-04', '1', '1'),
(89, 25, 16, '2024-12-04', '1', '1'),
(90, 26, 16, '2024-12-04', '1', '1'),
(91, 27, 16, '2024-12-04', '1', '1'),
(92, 28, 16, '2024-12-04', '1', '1'),
(93, 29, 16, '2024-12-04', '0', '1'),
(94, 13, 16, '2024-12-03', '1', '0'),
(95, 14, 16, '2024-12-03', '1', '0'),
(96, 15, 16, '2024-12-03', '1', '0'),
(97, 16, 16, '2024-12-03', '1', '0'),
(98, 17, 16, '2024-12-03', '1', '0'),
(99, 18, 16, '2024-12-03', '1', '0'),
(100, 19, 16, '2024-12-03', '1', '0'),
(101, 20, 16, '2024-12-03', '1', '0'),
(102, 21, 16, '2024-12-03', '1', '0'),
(103, 22, 16, '2024-12-03', '1', '0'),
(104, 24, 16, '2024-12-03', '1', '0'),
(105, 25, 16, '2024-12-03', '1', '0'),
(106, 26, 16, '2024-12-03', '0', '1'),
(107, 27, 16, '2024-12-03', '0', '1'),
(108, 28, 16, '2024-12-03', '0', '1'),
(109, 29, 16, '2024-12-03', '0', '1'),
(110, 13, 16, '2024-12-02', '1', '1'),
(111, 14, 16, '2024-12-02', '1', '1'),
(112, 15, 16, '2024-12-02', '1', '1'),
(113, 16, 16, '2024-12-02', '1', '1'),
(114, 17, 16, '2024-12-02', '1', '1'),
(115, 18, 16, '2024-12-02', '1', '1'),
(116, 19, 16, '2024-12-02', '1', '1'),
(117, 20, 16, '2024-12-02', '1', '1'),
(118, 21, 16, '2024-12-02', '1', '1'),
(119, 22, 16, '2024-12-02', '1', '1'),
(120, 23, 16, '2024-12-02', '1', '1'),
(121, 24, 16, '2024-12-02', '1', '1'),
(122, 25, 16, '2024-12-02', '1', '1'),
(123, 26, 16, '2024-12-02', '1', '1'),
(124, 27, 16, '2024-12-02', '1', '1'),
(125, 28, 16, '2024-12-02', '1', '1'),
(126, 29, 16, '2024-12-02', '1', '1'),
(127, 13, 16, '2024-12-11', '1', '1'),
(128, 14, 16, '2024-12-11', '1', '1'),
(129, 15, 16, '2024-12-11', '1', '1'),
(130, 16, 16, '2024-12-11', '1', '1'),
(131, 17, 16, '2024-12-11', '1', '1'),
(132, 18, 16, '2024-12-11', '1', '1'),
(133, 19, 16, '2024-12-11', '1', '1'),
(134, 20, 16, '2024-12-11', '1', '1'),
(135, 21, 16, '2024-12-11', '1', '1'),
(136, 22, 16, '2024-12-11', '1', '1'),
(137, 23, 16, '2024-12-11', '1', '1'),
(138, 24, 16, '2024-12-11', '1', '1'),
(139, 25, 16, '2024-12-11', '1', '1'),
(140, 26, 16, '2024-12-11', '1', '1'),
(141, 27, 16, '2024-12-11', '1', '1'),
(142, 28, 16, '2024-12-11', '1', '1'),
(143, 29, 16, '2024-12-11', '1', '1'),
(144, 13, 16, '2024-12-16', '1', '0'),
(145, 14, 16, '2024-12-16', '1', '0'),
(146, 15, 16, '2024-12-16', '1', '0'),
(147, 16, 16, '2024-12-16', '1', '0'),
(148, 17, 16, '2024-12-16', '1', '0'),
(149, 18, 16, '2024-12-16', '1', '0'),
(150, 19, 16, '2024-12-16', '1', '0'),
(151, 20, 16, '2024-12-16', '1', '0'),
(152, 21, 16, '2024-12-16', '1', '0'),
(153, 22, 16, '2024-12-16', '1', '0'),
(154, 23, 16, '2024-12-16', '1', '0'),
(155, 24, 16, '2024-12-16', '1', '0'),
(156, 25, 16, '2024-12-16', '1', '0'),
(157, 26, 16, '2024-12-16', '1', '0'),
(158, 27, 16, '2024-12-16', '1', '0'),
(159, 28, 16, '2024-12-16', '1', '0'),
(160, 29, 16, '2024-12-16', '1', '0'),
(161, 13, 16, '2024-12-13', '1', '0'),
(162, 13, 16, '2024-12-17', '1', '1'),
(163, 14, 16, '2024-12-17', '1', '1'),
(164, 15, 16, '2024-12-17', '1', '1'),
(165, 16, 16, '2024-12-17', '1', '1'),
(166, 17, 16, '2024-12-17', '1', '1'),
(167, 18, 16, '2024-12-17', '1', '1'),
(168, 19, 16, '2024-12-17', '1', '1'),
(169, 20, 16, '2024-12-17', '1', '1'),
(170, 21, 16, '2024-12-17', '1', '1'),
(171, 22, 16, '2024-12-17', '1', '1'),
(172, 23, 16, '2024-12-17', '1', '1'),
(173, 24, 16, '2024-12-17', '1', '1'),
(174, 25, 16, '2024-12-17', '1', '1'),
(175, 26, 16, '2024-12-17', '1', '1'),
(176, 27, 16, '2024-12-17', '1', '1'),
(177, 28, 16, '2024-12-17', '1', '1'),
(178, 29, 16, '2024-12-17', '1', '1');

-- --------------------------------------------------------

--
-- Struttura della tabella `scheduling_groups`
--

CREATE TABLE `scheduling_groups` (
  `id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `classe_id` int(11) NOT NULL,
  `docente_id` int(11) NOT NULL,
  `nome_scheduling` varchar(255) NOT NULL,
  `data_creazione` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `scheduling_groups`
--

INSERT INTO `scheduling_groups` (`id`, `materia_id`, `classe_id`, `docente_id`, `nome_scheduling`, `data_creazione`) VALUES
(1, 7, 16, 6, 'CCNA2', '2024-12-16 16:26:05');

-- --------------------------------------------------------

--
-- Struttura della tabella `studenti`
--

CREATE TABLE `studenti` (
  `ID_Studente` int(30) NOT NULL,
  `ID_Classe` int(11) NOT NULL,
  `Nome` varchar(30) NOT NULL,
  `Cognome` varchar(30) NOT NULL,
  `Commento` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `studenti`
--

INSERT INTO `studenti` (`ID_Studente`, `ID_Classe`, `Nome`, `Cognome`, `Commento`) VALUES
(13, 16, 'Federico Giuseppe', 'Abbatepaolo', ''),
(14, 16, 'Pietro', 'Angiulli', ''),
(15, 16, 'Stefano', 'Barletta', ''),
(16, 16, 'Nicolò', 'Belviso', ''),
(17, 16, 'Francesco', 'Botta', ''),
(18, 16, 'Davide', 'Cannillo', ''),
(19, 16, 'Gaetano Francesco', 'Carelli', ''),
(20, 16, 'Emanuele', 'Catacchio', ''),
(21, 16, 'Damiano', 'Del Re', ''),
(22, 16, 'Gennaro', 'Falco', ''),
(23, 16, 'Antonio', 'Gadaleta', ''),
(24, 16, 'Walter', 'Giannuzzi', ''),
(25, 16, 'Claudio', 'Lella', ''),
(26, 16, 'Annarita', 'Pascazio', ''),
(27, 16, 'Domenico', 'Piluscio', ''),
(28, 16, 'Syria', 'Saracino', ''),
(29, 16, 'Piervito', 'Tortelli', '');

-- --------------------------------------------------------

--
-- Struttura della tabella `voti`
--

CREATE TABLE `voti` (
  `ID_Voto` int(11) NOT NULL,
  `ID_Studente` int(11) NOT NULL,
  `ID_Materia` int(11) NOT NULL,
  `Voto` int(11) NOT NULL,
  `Data` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `voti`
--

INSERT INTO `voti` (`ID_Voto`, `ID_Studente`, `ID_Materia`, `Voto`, `Data`) VALUES
(1, 11, 3, 8, '2024-12-03'),
(4, 11, 4, 10, '2024-12-03'),
(5, 12, 4, 10, '2024-12-03'),
(6, 12, 3, 10, '2024-12-03'),
(7, 13, 18, 4, '2024-12-09'),
(8, 14, 18, 4, '2024-12-09'),
(9, 15, 18, 5, '2024-12-09'),
(10, 16, 18, 4, '2024-12-09'),
(11, 17, 18, 4, '2024-12-09'),
(12, 18, 18, 4, '2024-12-09'),
(13, 19, 18, 4, '2024-12-09'),
(14, 20, 18, 4, '2024-12-09'),
(15, 21, 18, 3, '2024-12-09'),
(16, 22, 18, 3, '2024-12-09'),
(17, 23, 18, 3, '2024-12-09'),
(18, 24, 18, 4, '2024-12-09'),
(19, 25, 18, 3, '2024-12-09'),
(20, 26, 18, 4, '2024-12-09'),
(21, 27, 18, 4, '2024-12-09'),
(22, 28, 18, 4, '2024-12-09'),
(23, 29, 18, 4, '2024-12-09'),
(24, 13, 3, 4, '2024-12-09'),
(25, 14, 3, 4, '2024-12-09'),
(26, 15, 3, 4, '2024-12-09'),
(27, 16, 3, 4, '2024-12-09'),
(28, 17, 3, 4, '2024-12-09'),
(29, 18, 3, 5, '2024-12-09'),
(30, 19, 3, 4, '2024-12-09'),
(31, 20, 3, 4, '2024-12-09'),
(32, 21, 3, 3, '2024-12-09'),
(33, 22, 3, 3, '2024-12-09'),
(34, 23, 3, 2, '2024-12-09'),
(35, 24, 3, 4, '2024-12-09'),
(36, 25, 3, 3, '2024-12-09'),
(37, 26, 3, 3, '2024-12-09'),
(38, 27, 3, 4, '2024-12-09'),
(39, 28, 3, 4, '2024-12-09'),
(40, 29, 3, 4, '2024-12-09'),
(41, 13, 4, 4, '2024-12-09'),
(42, 14, 4, 4, '2024-12-09'),
(43, 15, 4, 4, '2024-12-09'),
(44, 16, 4, 4, '2024-12-09'),
(45, 17, 4, 4, '2024-12-09'),
(46, 18, 4, 5, '2024-12-09'),
(47, 19, 4, 4, '2024-12-09'),
(48, 20, 4, 4, '2024-12-09'),
(49, 21, 4, 3, '2024-12-09'),
(50, 22, 4, 3, '2024-12-09'),
(51, 23, 4, 2, '2024-12-09'),
(52, 24, 4, 4, '2024-12-09'),
(53, 25, 4, 3, '2024-12-09'),
(54, 26, 4, 3, '2024-12-09'),
(55, 27, 4, 4, '2024-12-09'),
(56, 28, 4, 4, '2024-12-09'),
(57, 29, 4, 4, '2024-12-09'),
(58, 13, 5, 4, '2024-12-09'),
(59, 14, 5, 4, '2024-12-09'),
(60, 15, 5, 4, '2024-12-09'),
(61, 16, 5, 4, '2024-12-09'),
(62, 17, 5, 4, '2024-12-09'),
(63, 18, 5, 5, '2024-12-09'),
(64, 19, 5, 4, '2024-12-09'),
(65, 20, 5, 4, '2024-12-09'),
(66, 21, 5, 3, '2024-12-09'),
(67, 22, 5, 3, '2024-12-09'),
(68, 23, 5, 2, '2024-12-09'),
(69, 24, 5, 4, '2024-12-09'),
(70, 25, 5, 3, '2024-12-09'),
(71, 26, 5, 3, '2024-12-09'),
(72, 27, 5, 4, '2024-12-09'),
(73, 28, 5, 4, '2024-12-09'),
(74, 29, 5, 4, '2024-12-09'),
(75, 13, 6, 4, '2024-12-09'),
(76, 14, 6, 4, '2024-12-09'),
(77, 15, 6, 3, '2024-12-09'),
(78, 16, 6, 4, '2024-12-09'),
(79, 17, 6, 3, '2024-12-09'),
(80, 18, 6, 5, '2024-12-09'),
(81, 19, 6, 4, '2024-12-09'),
(82, 20, 6, 4, '2024-12-09'),
(83, 21, 6, 3, '2024-12-09'),
(84, 22, 6, 3, '2024-12-09'),
(85, 23, 6, 1, '2024-12-09'),
(86, 24, 6, 4, '2024-12-09'),
(87, 25, 6, 3, '2024-12-09'),
(88, 26, 6, 3, '2024-12-09'),
(89, 27, 6, 4, '2024-12-09'),
(90, 28, 6, 4, '2024-12-09'),
(91, 29, 6, 4, '2024-12-09'),
(92, 13, 7, 4, '2024-12-17'),
(93, 14, 7, 4, '2024-12-17'),
(94, 15, 7, 4, '2024-12-17'),
(95, 16, 7, 4, '2024-12-17'),
(96, 17, 7, 4, '2024-12-17'),
(97, 18, 7, 5, '2024-12-17'),
(98, 19, 7, 4, '2024-12-17'),
(99, 20, 7, 4, '2024-12-17'),
(100, 21, 7, 3, '2024-12-17'),
(101, 22, 7, 3, '2024-12-17'),
(102, 23, 7, 1, '2024-12-17'),
(103, 24, 7, 4, '2024-12-17'),
(104, 25, 7, 3, '2024-12-17'),
(105, 26, 7, 3, '2024-12-17'),
(106, 27, 7, 4, '2024-12-17'),
(107, 28, 7, 4, '2024-12-17'),
(108, 29, 7, 4, '2024-12-17');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `argomenti`
--
ALTER TABLE `argomenti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `materia_id` (`materia_id`);

--
-- Indici per le tabelle `classi`
--
ALTER TABLE `classi`
  ADD PRIMARY KEY (`ID_Classe`);

--
-- Indici per le tabelle `classi_docenti`
--
ALTER TABLE `classi_docenti`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID_D` (`ID_D`) USING BTREE,
  ADD KEY `ID_C` (`ID_C`) USING BTREE;

--
-- Indici per le tabelle `classi_materie`
--
ALTER TABLE `classi_materie`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ID_C` (`ID_C`),
  ADD KEY `ID_M` (`ID_M`);

--
-- Indici per le tabelle `docente`
--
ALTER TABLE `docente`
  ADD PRIMARY KEY (`ID_Docente`) USING BTREE;

--
-- Indici per le tabelle `impostazioni_classe`
--
ALTER TABLE `impostazioni_classe`
  ADD PRIMARY KEY (`ID_Setting`),
  ADD KEY `ID_Classe` (`ID_Classe`);

--
-- Indici per le tabelle `lezioni`
--
ALTER TABLE `lezioni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `materia_id` (`materia_id`),
  ADD KEY `argomento_id` (`argomento_id`);

--
-- Indici per le tabelle `lezioni_argomenti`
--
ALTER TABLE `lezioni_argomenti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lezione_id` (`lezione_id`),
  ADD KEY `argomento_id` (`argomento_id`);

--
-- Indici per le tabelle `materie`
--
ALTER TABLE `materie`
  ADD PRIMARY KEY (`ID_Materia`);

--
-- Indici per le tabelle `presenze`
--
ALTER TABLE `presenze`
  ADD PRIMARY KEY (`ID_Presenza`);

--
-- Indici per le tabelle `scheduling_groups`
--
ALTER TABLE `scheduling_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `studenti`
--
ALTER TABLE `studenti`
  ADD PRIMARY KEY (`ID_Studente`),
  ADD KEY `ID_Classe` (`ID_Classe`) USING BTREE;

--
-- Indici per le tabelle `voti`
--
ALTER TABLE `voti`
  ADD PRIMARY KEY (`ID_Voto`),
  ADD KEY `ID_Studente` (`ID_Studente`) USING BTREE,
  ADD KEY `ID_Materia` (`ID_Materia`) USING BTREE;

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `argomenti`
--
ALTER TABLE `argomenti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT per la tabella `classi`
--
ALTER TABLE `classi`
  MODIFY `ID_Classe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT per la tabella `classi_docenti`
--
ALTER TABLE `classi_docenti`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT per la tabella `classi_materie`
--
ALTER TABLE `classi_materie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT per la tabella `docente`
--
ALTER TABLE `docente`
  MODIFY `ID_Docente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `impostazioni_classe`
--
ALTER TABLE `impostazioni_classe`
  MODIFY `ID_Setting` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `lezioni`
--
ALTER TABLE `lezioni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=138;

--
-- AUTO_INCREMENT per la tabella `lezioni_argomenti`
--
ALTER TABLE `lezioni_argomenti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT per la tabella `materie`
--
ALTER TABLE `materie`
  MODIFY `ID_Materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT per la tabella `presenze`
--
ALTER TABLE `presenze`
  MODIFY `ID_Presenza` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT per la tabella `scheduling_groups`
--
ALTER TABLE `scheduling_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT per la tabella `studenti`
--
ALTER TABLE `studenti`
  MODIFY `ID_Studente` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT per la tabella `voti`
--
ALTER TABLE `voti`
  MODIFY `ID_Voto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `argomenti`
--
ALTER TABLE `argomenti`
  ADD CONSTRAINT `argomenti_ibfk_1` FOREIGN KEY (`materia_id`) REFERENCES `materie` (`ID_Materia`) ON DELETE CASCADE;

--
-- Limiti per la tabella `classi_materie`
--
ALTER TABLE `classi_materie`
  ADD CONSTRAINT `classi_materie_ibfk_1` FOREIGN KEY (`ID_C`) REFERENCES `classi` (`ID_Classe`) ON DELETE CASCADE,
  ADD CONSTRAINT `classi_materie_ibfk_2` FOREIGN KEY (`ID_M`) REFERENCES `materie` (`ID_Materia`) ON DELETE CASCADE;

--
-- Limiti per la tabella `lezioni`
--
ALTER TABLE `lezioni`
  ADD CONSTRAINT `lezioni_ibfk_1` FOREIGN KEY (`materia_id`) REFERENCES `materie` (`ID_Materia`) ON DELETE CASCADE,
  ADD CONSTRAINT `lezioni_ibfk_2` FOREIGN KEY (`argomento_id`) REFERENCES `argomenti` (`id`) ON DELETE SET NULL;

--
-- Limiti per la tabella `lezioni_argomenti`
--
ALTER TABLE `lezioni_argomenti`
  ADD CONSTRAINT `lezioni_argomenti_ibfk_1` FOREIGN KEY (`lezione_id`) REFERENCES `lezioni` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lezioni_argomenti_ibfk_2` FOREIGN KEY (`argomento_id`) REFERENCES `argomenti` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
