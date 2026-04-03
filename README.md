# 📚 Registro Elettronico Web — Accademia del Levante

Applicazione web PHP/MySQL per la gestione didattica del docente.  
Sviluppata da **Massimo Mezzina** — [massimoesperto.it](https://massimoesperto.it)

---

## Indice

1. [Requisiti](#requisiti)
2. [Installazione](#installazione)
3. [Configurazione Database](#configurazione-database)
4. [Schema Database](#schema-database)
5. [Struttura File](#struttura-file)
6. [Funzionalità](#funzionalita)
7. [Modulo Esami](#modulo-esami)
8. [Flusso Utenti](#flusso-utenti)
9. [Risoluzione Problemi](#risoluzione-problemi)

---

## Requisiti

| Componente | Versione minima |
|---|---|
| PHP | 7.4+ (testato su 8.3) |
| MariaDB / MySQL | 10.x / 5.7+ |
| Web server | Apache 2.4+ con `mod_rewrite` |
| Browser | Qualsiasi moderno (Chrome, Firefox, Edge) |

> ⚠️ Su **MariaDB moderno** (11.x+) la collation predefinita è `utf8mb4_uca1400_ai_ci`.  
> Il file `db_connection.php` la imposta automaticamente a livello di connessione.

---

## Installazione

### 1. Clona / carica i file

Copia l'intera cartella `registro/` nella root del web server:

```bash
# Esempio su Raspberry Pi con Apache
cp -r registro/ /var/www/html/registro/
```

### 2. Crea il database

Accedi a **phpMyAdmin** oppure usa il terminale:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE regsx_class
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_uca1400_ai_ci;
```

### 3. Importa lo schema

Importa i file SQL nell'ordine indicato:

```bash
# Passo 1 — Schema base (tabelle originali)
mysql -u admin -p regsx_class < sql/regsx_class.sql

# Passo 2 — Modulo Esami (tabelle aggiuntive)
mysql -u admin -p regsx_class < sql/esami_setup.sql

# Passo 3 — Fix collation (solo se necessario, vedi sezione problemi)
mysql -u admin -p regsx_class < sql/esami_fix_collation.sql
```

> In alternativa usa **phpMyAdmin** → seleziona `regsx_class` → tab **Importa** → carica i file uno alla volta nello stesso ordine.

### 4. Configura la connessione al database

Apri **`db_connection.php`** e modifica le credenziali:

```php
$host     = 'localhost';
$dbname   = 'regsx_class';
$username = 'admin';       // ← tuo utente MySQL
$password = 'Password';  // ← tua password MySQL
```

> Questo è l'**unico file** da modificare per la configurazione del DB.  
> Tutti gli altri file lo includono con `require_once 'db_connection.php'`.

### 5. Permessi Apache

Assicurati che Apache abbia i permessi di lettura sulla cartella:

```bash
sudo chown -R www-data:www-data /var/www/html/registro/
sudo chmod -R 755 /var/www/html/registro/
```

### 6. Primo accesso

Naviga su `http://tuoserver/registro/login.html` e registra il primo utente.  
Nel campo **Codice** inserisci `Accademia` per ottenere i privilegi di amministratore (root).

---

## Configurazione Database

### Connessione

Il file `db_connection.php` espone due oggetti di connessione:

| Variabile | Tipo | Uso |
|---|---|---|
| `$conn` | PDO | File moderni e modulo esami |
| `$mysqli` | mysqli | Retrocompatibilità file legacy |

Entrambe le connessioni impostano automaticamente:
```sql
SET NAMES utf8mb4 COLLATE utf8mb4_uca1400_ai_ci
```

### Utente MySQL consigliato

Per sicurezza, crea un utente dedicato invece di usare `root`:

```sql
CREATE USER 'registro_user'@'localhost' IDENTIFIED BY 'password_sicura';
GRANT ALL PRIVILEGES ON regsx_class.* TO 'registro_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## Schema Database

Il database `regsx_class` è composto da **18 tabelle** divise in due gruppi.

### Tabelle Base (regsx_class.sql)

#### `docente` — Utenti del sistema
| Colonna | Tipo | Descrizione |
|---|---|---|
| `ID_Docente` | int PK | Identificatore univoco |
| `Username` | varchar(30) | Nome utente per il login |
| `Email` | varchar(30) | Email per il login |
| `Password` | varchar(255) | Password hashata con bcrypt |
| `root` | varchar(255) | Privilegi admin: `SI` oppure `NO` |

#### `classi` — Classi didattiche
| Colonna | Tipo | Descrizione |
|---|---|---|
| `ID_Classe` | int PK | Identificatore univoco |
| `Nome` | varchar(30) | Nome della classe (es. ITBAR1) |

#### `classi_docenti` — Associazione classi ↔ docenti
| Colonna | Tipo | Descrizione |
|---|---|---|
| `ID` | int PK | |
| `ID_D` | int FK | → `docente.ID_Docente` |
| `ID_C` | int FK | → `classi.ID_Classe` |

#### `classi_materie` — Associazione classi ↔ materie
| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | int PK | |
| `ID_C` | int FK | → `classi.ID_Classe` |
| `ID_M` | int FK | → `materie.ID_Materia` |

#### `materie` — Materie didattiche
| Colonna | Tipo | Descrizione |
|---|---|---|
| `ID_Materia` | int PK | |
| `Materia` | varchar(30) | Nome della materia |

#### `studenti` — Alunni
| Colonna | Tipo | Descrizione |
|---|---|---|
| `ID_Studente` | int PK | |
| `ID_Classe` | int FK | → `classi.ID_Classe` |
| `Nome` | varchar(100) | Nome dell'alunno |
| `Cognome` | varchar(100) | Cognome dell'alunno |
| `Commento` | varchar(255) | Note del docente sull'alunno |

> ⚠️ Se hai il campo `Nome`/`Cognome` con lunghezza 30, esegui:
> ```sql
> ALTER TABLE studenti
>   MODIFY Nome VARCHAR(100) NOT NULL,
>   MODIFY Cognome VARCHAR(100) NOT NULL;
> ```

#### `voti` — Voti degli studenti
| Colonna | Tipo | Descrizione |
|---|---|---|
| `ID_Voto` | int PK | |
| `ID_Studente` | int FK | → `studenti.ID_Studente` |
| `ID_Materia` | int FK | → `materie.ID_Materia` |
| `Voto` | int | Voto (0–100) |
| `Data` | date | Data del voto |

#### `presenze` — Registro presenze
| Colonna | Tipo | Descrizione |
|---|---|---|
| `ID_Presenza` | int PK | |
| `ID_Studente` | int FK | → `studenti.ID_Studente` |
| `ID_Classe` | int FK | → `classi.ID_Classe` |
| `Data` | date | Data della presenza |
| `Presenza_Mattina` | tinyint | 1 = presente, 0 = assente |
| `Presenza_Pomeriggio` | tinyint | 1 = presente, 0 = assente |

#### `impostazioni_classe` — Ore per classe
| Colonna | Tipo | Descrizione |
|---|---|---|
| `ID_Setting` | int PK | |
| `ID_Classe` | int FK | → `classi.ID_Classe` |
| `Ore_Mattina` | int | Ore per sessione mattutina |
| `Ore_Pomeriggio` | int | Ore per sessione pomeridiana |
| `Ore_Totali_Corso` | int | Ore totali previste dal corso |

#### `scheduling_groups` — Gruppi di scheduling
| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | int PK | |
| `materia_id` | int FK | → `materie.ID_Materia` |
| `classe_id` | int FK | → `classi.ID_Classe` |
| `docente_id` | int FK | → `docente.ID_Docente` |
| `nome_scheduling` | varchar(255) | Nome descrittivo |
| `data_creazione` | timestamp | Data creazione automatica |

#### `lezioni` — Lezioni di uno scheduling
| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | int PK | |
| `materia_id` | int FK | → `materie.ID_Materia` |
| `classe_id` | int FK | → `classi.ID_Classe` |
| `scheduling_id` | int FK | → `scheduling_groups.id` |
| `numero_lezione` | int | Numero progressivo lezione |
| `completato` | tinyint | 1 = completata |
| `commento` | text | Note sulla lezione |

#### `argomenti` — Argomenti per materia
| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | int PK | |
| `materia_id` | int FK | → `materie.ID_Materia` |
| `nome` | varchar(255) | Nome dell'argomento |

#### `lezioni_argomenti` — Argomenti assegnati alle lezioni
| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | int PK | |
| `lezione_id` | int FK | → `lezioni.id` |
| `scheduling_id` | int FK | → `scheduling_groups.id` |
| `argomento_id` | int FK | → `argomenti.id` |
| `completato` | tinyint | 1 = argomento trattato |

---

### Tabelle Modulo Esami (esami_setup.sql)

#### `esami` — Esami creati dall'admin
| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | int PK | |
| `titolo` | varchar(255) | Titolo dell'esame |
| `ID_Classe` | int FK | → `classi.ID_Classe` |
| `ID_Materia` | int FK | → `materie.ID_Materia` (opzionale) |
| `num_domande` | int | Numero di domande previste |
| `durata_minuti` | int | Durata in minuti (default 30) |
| `creato_da` | int FK | → `docente.ID_Docente` |
| `data_creazione` | datetime | Timestamp creazione |
| `attivo` | tinyint | 1 = esame attivo e accessibile |
| `codice_accesso` | varchar(20) | Codice 6 caratteri per i candidati |
| `scadenza_codice` | datetime | Scadenza del codice (24h dall'attivazione) |

#### `esami_domande` — Domande dell'esame
| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | int PK | |
| `esame_id` | int FK | → `esami.id` (CASCADE DELETE) |
| `testo` | text | Testo della domanda |
| `ordine` | int | Ordine di presentazione |

#### `esami_risposte` — Opzioni di risposta (A/B/C/D)
| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | int PK | |
| `domanda_id` | int FK | → `esami_domande.id` (CASCADE DELETE) |
| `testo` | varchar(500) | Testo della risposta |
| `corretta` | tinyint | 1 = risposta corretta |
| `lettera` | char(1) | A, B, C o D |

#### `esami_sessioni` — Sessioni dei candidati
| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | int PK | |
| `esame_id` | int FK | → `esami.id` (CASCADE DELETE) |
| `nome_candidato` | varchar(100) | Nome inserito all'accesso |
| `cognome_candidato` | varchar(100) | Cognome inserito all'accesso |
| `ID_Studente` | int | → `studenti.ID_Studente` (se abbinato) |
| `iniziato_il` | datetime | Timestamp inizio esame |
| `terminato_il` | datetime | Timestamp fine esame |
| `punteggio` | decimal(5,2) | Punteggio finale in percentuale (0–100) |
| `completato` | tinyint | 1 = esame consegnato |

#### `esami_risposte_candidati` — Risposte date durante l'esame
| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | int PK | |
| `sessione_id` | int FK | → `esami_sessioni.id` (CASCADE DELETE) |
| `domanda_id` | int FK | → `esami_domande.id` |
| `risposta_id` | int FK | → `esami_risposte.id` |

> Le risposte vengono salvate in tempo reale via AJAX durante lo svolgimento,  
> quindi non vengono perse in caso di disconnessione.

---

## Struttura File

```
registro/
│
├── db_connection.php              ← ⚙️ UNICO FILE DI CONFIGURAZIONE DB
│
├── login.html                     ← Pagina di login (form)
├── login.php                      ← Logica autenticazione
├── login_reg.html                 ← Pagina login post-registrazione
├── logout.php                     ← Distrugge sessione
├── registrazione.php              ← Registrazione nuovo docente
│
├── dashboard.php                  ← Home dopo il login
│
├── alunni_classi.php              ← Gestione studenti (import CSV/XLSX)
├── scheda_alunno.php              ← Scheda individuale + grafico voti
│
├── gestione_classi.php            ← CRUD classi + associazioni
├── gestione_voti.php              ← Tabella voti per classe
├── add_voto.php                   ← Inserimento voto
├── edit_voto.php                  ← Modifica voto
│
├── presenze.php                   ← Registro presenze mattina/pomeriggio
│
├── materie.php                    ← Gestione materie
├── elabora_materia.php            ← Salva nuova materia
├── elenco_materie.php             ← Lista materie (incluso da materie.php)
├── elimina_materia.php            ← Elimina materia
│
├── scheduling.php                 ← Scheduling lezioni per materia/classe
├── gestione_scheduling.php        ← Dettaglio scheduling con commenti
│
├── esami.php                      ← Admin: lista esami, attiva/disattiva
├── esami_crea.php                 ← Admin: editor domande e risposte
├── esami_accesso.php              ← Candidato: accesso con codice
├── esami_svolgi.php               ← Candidato: svolgimento con timer
├── esami_risultato_candidato.php  ← Candidato: risultato e riepilogo
├── esami_risultati.php            ← Admin: dashboard risultati candidati
│
├── access_denied.php              ← Pagina accesso negato (non-root)
├── style.css                      ← Foglio di stile principale
│
├── icons/                         ← Icone PNG dashboard
│   ├── alunni.png
│   ├── classi.png
│   ├── voti.png
│   ├── presenze.png
│   ├── materie.png
│   ├── scheduling.png
│   ├── logout.png
│   ├── back-icon.png
│   └── submit-icon.png
│
└── sql/
    ├── regsx_class.sql            ← Schema DB base (da importare per primo)
    ├── esami_setup.sql            ← Tabelle modulo esami (da importare per secondo)
    └── esami_fix_collation.sql    ← Fix collation MariaDB (solo se necessario)
```

---

## Funzionalità

### 👥 Gestione Classi
- Crea, modifica ed elimina classi
- Associa più materie e docenti a ogni classe
- Imposta ore mattina, pomeriggio e totali del corso (usate per il calcolo presenze)
- Solo utenti **root** possono creare/modificare classi

### 🎓 Gestione Studenti
- Aggiungi studenti manualmente (nome + cognome)
- Importa lista studenti da file **CSV** o **XLSX**
- Elimina singolo studente o svuota tutta la classe
- Scheda individuale con: ore frequentate, commento docente, grafico andamento voti

### 📝 Gestione Voti
- Visualizzazione a griglia: studenti × materie
- Inserimento e modifica voto per ogni studente/materia
- Voti da 0 a 100

### ✅ Registro Presenze
- Selezione classe + data
- Checkbox mattina/pomeriggio per ogni studente
- Calcolo automatico ore frequentate cumulative
- Aggiornamento corretto anche quando si deselezionano tutti i checkbox

### 📚 Materie
- Lista materie con eliminazione diretta
- L'eliminazione rimuove anche le associazioni in `classi_materie`

### 📅 Scheduling
- Crea gruppi di scheduling per materia + classe
- Gestisce argomenti per materia (aggiungi/elimina)
- Associa argomenti alle singole lezioni
- Segna argomenti come completati con toggle interattivo

### 📝 Modulo Esami
Vedi sezione dedicata sotto.

---

## Modulo Esami

### Flusso Admin

1. **Accede** a `esami.php` dalla dashboard
2. **Crea** un esame: titolo, classe, materia (opzionale), n. domande, durata
3. **Inserisce** le domande in `esami_crea.php`:
   - Testo della domanda
   - 4 opzioni di risposta (A/B/C/D)
   - Seleziona la risposta corretta
4. **Attiva** l'esame → viene generato un **codice di 6 caratteri** valido **24 ore**
5. **Condivide** il codice con i candidati
6. **Monitora** i risultati in tempo reale su `esami_risultati.php`
7. **Inserisce i voti** nel registro con un click (abbinamento per nome/cognome)

### Flusso Candidato

1. Apre `esami_accesso.php`
2. Inserisce **codice**, **nome** e **cognome**
3. Svolge l'esame:
   - Naviga liberamente tra le domande
   - Le risposte vengono **salvate in tempo reale** via AJAX
   - Il timer mostra il tempo rimasto (calcolato lato DB per evitare problemi di timezone)
4. Consegna manualmente oppure il tempo scade → consegna automatica
5. Vede subito il **punteggio** e il riepilogo dettagliato delle risposte

### Codici di Accesso

- Generati automaticamente all'attivazione: 6 caratteri alfanumerici (es. `X7K2MN`)
- Validità: **24 ore** dall'attivazione
- L'admin può disattivare l'esame in qualsiasi momento
- Un candidato non può ripetere lo stesso esame

---

## Flusso Utenti

### Docente standard
```
Login → Dashboard → [Voti / Presenze / Scheduling / Esami (candidato)]
```
- Vede solo le classi a cui è associato
- Non può creare/modificare classi o gestire esami come admin

### Docente root (admin)
```
Login → Dashboard → [tutto]
```
- Accesso completo a tutte le funzionalità
- Gestione classi, utenti, esami
- Dashboard risultati esami

### Candidato (senza account)
```
esami_accesso.php → (inserisce codice + nome) → esami_svolgi.php → esami_risultato_candidato.php
```
- Non richiede registrazione
- Accede solo all'esame attivo con il codice valido

---

## Risoluzione Problemi

### ❌ Errore 500 su qualsiasi pagina

**Causa più comune:** credenziali DB errate in `db_connection.php`.  
**Soluzione:** verifica `$username` e `$password` in `db_connection.php`.

Controlla il log di Apache per il dettaglio:
```bash
sudo tail -50 /var/log/apache2/error.log
```

---

### ❌ "Illegal mix of collations" (errore 1267)

**Causa:** le tabelle del modulo esami hanno collation diversa dal resto del DB.  
**Soluzione:** esegui in phpMyAdmin:
```sql
-- Oppure importa sql/esami_fix_collation.sql
ALTER TABLE esami CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
ALTER TABLE esami_domande CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
ALTER TABLE esami_risposte CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
ALTER TABLE esami_sessioni CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
ALTER TABLE esami_risposte_candidati CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
```

---

### ❌ Timer esame mostra valore sbagliato (es. 147:00)

**Causa:** sfasamento timezone tra PHP (`time()`) e MariaDB (`current_timestamp()`).  
**Soluzione:** già corretta nel codice — il timer usa `TIMESTAMPDIFF(SECOND, iniziato_il, NOW())` calcolato interamente in SQL.  
Se il problema persiste, allinea il timezone di PHP e MariaDB:
```bash
# Controlla timezone PHP
php -r "echo date_default_timezone_get();"

# Controlla timezone MariaDB
mysql -e "SELECT @@global.time_zone, @@session.time_zone;"
```

---

### ❌ Nome studente troppo lungo (errore 1406)

**Causa:** la colonna `Nome`/`Cognome` della tabella `studenti` è troppo corta (varchar 30).  
**Soluzione:**
```sql
ALTER TABLE studenti
  MODIFY Nome VARCHAR(100) NOT NULL,
  MODIFY Cognome VARCHAR(100) NOT NULL;
```

---

### ❌ Le presenze non si azzerano (checkbox deselezionati ignorati)

**Causa:** i checkbox HTML non inviati = studente non presente nel POST = riga DB non aggiornata.  
**Soluzione:** già corretta — `presenze.php` usa un campo hidden `studenti_ids` con la lista completa degli studenti, iterando su tutti indipendentemente dai checkbox spuntati.

---

### ❌ Le materie non appaiono in materie.php

**Causa:** `materie.php` non includeva `db_connection.php` prima di includere `elenco_materie.php`.  
**Soluzione:** già corretta — `materie.php` ora include `db_connection.php` in cima.

---

### 🔐 Sicurezza

- Le password vengono hashate con **bcrypt** (`password_hash` / `password_verify`)
- Tutte le query usano **prepared statements PDO** — nessuna SQL injection possibile
- Le sessioni usano `session_start()` con `session_regenerate_id()` dove necessario
- Il codice di accesso agli esami ha scadenza automatica di 24 ore

---

## Crediti

**Sviluppato da:** Massimo Mezzina  
**Sito:** [massimoesperto.it](https://massimoesperto.it)  
**Ko-fi:** [ko-fi.com/massiprofessor](https://ko-fi.com/massiprofessor)  
**GitHub:** [github.com/massiprofessor](https://github.com/massiprofessor)
