# Registro Elettronico Web — Accademia del Levante

Applicazione PHP/MySQL per la gestione del registro didattico di un docente.

---

## Struttura dei file

```
registro/
├── db_connection.php          ← Configurazione DB (unico file da modificare)
├── login.html                 ← Pagina di login
├── login_reg.html             ← Login post-registrazione
├── login.php                  ← Logica autenticazione
├── logout.php                 ← Distrugge la sessione
├── registrazione.php          ← Registrazione nuovo docente
├── dashboard.php              ← Home dopo il login
│
├── alunni_classi.php          ← Gestione studenti per classe (import CSV/XLSX)
├── gestione_classi.php        ← CRUD classi + associazione materie/docenti
├── scheda_alunno.php          ← Scheda singolo alunno con grafico voti
│
├── gestione_voti.php          ← Tabella voti per classe
├── add_voto.php               ← Aggiunta voto
├── edit_voto.php              ← Modifica voto
│
├── presenze.php               ← Registro presenze mattina/pomeriggio
│
├── materie.php                ← Inserimento materie
├── elabora_materia.php        ← Salva nuova materia
├── elenco_materie.php         ← Lista materie (include in materie.php)
├── elimina_materia.php        ← Elimina materia
│
├── scheduling.php             ← Scheduling lezioni per materia/classe
├── gestione_scheduling.php    ← Dettaglio scheduling con commenti
│
├── esami.php                  ← Admin: lista esami, attiva/disattiva, voti
├── esami_crea.php             ← Admin: editor domande/risposte
├── esami_accesso.php          ← Candidato: accesso con codice
├── esami_svolgi.php           ← Candidato: svolgimento esame con timer
├── esami_risultato_candidato.php ← Candidato: risultato finale
├── esami_risultati.php        ← Admin: dashboard risultati candidati
│
├── access_denied.php          ← Pagina accesso negato (non-root)
│
├── icons/                     ← Icone PNG della dashboard
├── style.css                  ← Foglio di stile principale
│
└── sql/
    ├── regsx_class.sql        ← Schema DB completo (tabelle originali)
    ├── esami_setup.sql        ← Tabelle modulo esami (da importare dopo)
    └── esami_fix_collation.sql ← Fix collation MariaDB (eseguire se 500)
```

---

## Installazione

### 1. Database

1. Crea il database `regsx_class` in phpMyAdmin/MySQL
2. Importa `sql/regsx_class.sql`
3. Importa `sql/esami_setup.sql`
4. Se ottieni errori di collation, esegui `sql/esami_fix_collation.sql`

### 2. Configurazione DB

Modifica **solo** `db_connection.php`:

```php
$host     = 'localhost';
$dbname   = 'regsx_class';
$username = 'TUO_UTENTE';
$password = 'TUA_PASSWORD';
```

### 3. Deploy

Copia tutti i file nella cartella del web server (es. `/var/www/html/registro/`).

---

## Accesso

- **Login docente:** `login.html`
- **Accesso esame candidati:** `esami_accesso.php`
- **Codice root (per account admin):** inserire "Accademia" nel campo codice durante la registrazione

---

## Funzionalità

| Modulo | Descrizione |
|---|---|
| **Classi** | Crea classi, associa materie e docenti, imposta ore mattina/pomeriggio |
| **Alunni** | Aggiungi manualmente o importa da CSV/XLSX |
| **Voti** | Inserisci e modifica voti per materia |
| **Presenze** | Registro giornaliero mattina/pomeriggio con calcolo ore cumulative |
| **Materie** | Gestione elenco materie |
| **Scheduling** | Pianifica lezioni con argomenti per materia/classe |
| **Esami** | Crea quiz a risposta multipla, attiva con codice temporaneo 24h, visualizza risultati |

---

## Note tecniche

- PHP 7.4+ (testato su 8.3)
- MariaDB 10.x / MySQL 5.7+
- Tutti i file usano `db_connection.php` come unico punto di connessione
- La collation del DB deve essere uniforme (`utf8mb4_uca1400_ai_ci` su MariaDB moderno)
- Il timer degli esami usa `TIMESTAMPDIFF` SQL per evitare problemi di timezone
