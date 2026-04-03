<?php
session_start();

if (!isset($_SESSION['esame_sessione_id'], $_SESSION['esame_id'])) {
    header("Location: esami_accesso.php");
    exit;
}

require_once 'db_connection.php';

$sessione_id = (int)$_SESSION['esame_sessione_id'];
$esame_id    = (int)$_SESSION['esame_id'];

// Carica sessione
$stmtSess = $conn->prepare("SELECT * FROM esami_sessioni WHERE id = :id");
$stmtSess->execute([':id' => $sessione_id]);
$sessione = $stmtSess->fetch(PDO::FETCH_ASSOC);

if (!$sessione || $sessione['completato']) {
    header("Location: esami_risultato_candidato.php");
    exit;
}

// Carica esame
$stmtE = $conn->prepare("SELECT * FROM esami WHERE id = :id");
$stmtE->execute([':id' => $esame_id]);
$esame = $stmtE->fetch(PDO::FETCH_ASSOC);

// Carica domande con risposte
$stmtDom = $conn->prepare("SELECT * FROM esami_domande WHERE esame_id = :e ORDER BY ordine");
$stmtDom->execute([':e' => $esame_id]);
$domande = $stmtDom->fetchAll(PDO::FETCH_ASSOC);

foreach ($domande as &$dom) {
    $stmtRis = $conn->prepare("SELECT id, testo, corretta, lettera FROM esami_risposte WHERE domanda_id = :d ORDER BY lettera");
    $stmtRis->execute([':d' => $dom['id']]);
    $dom['risposte'] = $stmtRis->fetchAll(PDO::FETCH_ASSOC);
}
unset($dom);

// Carica risposte già date
$stmtRC = $conn->prepare("SELECT domanda_id, risposta_id FROM esami_risposte_candidati WHERE sessione_id = :s");
$stmtRC->execute([':s' => $sessione_id]);
$risposte_date = [];
foreach ($stmtRC->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $risposte_date[(int)$r['domanda_id']] = (int)$r['risposta_id'];
}

// ── AJAX: SALVA RISPOSTA ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'salva_risposta') {
        header('Content-Type: application/json');
        $dom_id = (int)$_POST['domanda_id'];
        $ris_id = (int)$_POST['risposta_id'];

        $chk = $conn->prepare("SELECT id FROM esami_risposte_candidati WHERE sessione_id=:s AND domanda_id=:d");
        $chk->execute([':s' => $sessione_id, ':d' => $dom_id]);
        if ($chk->fetch()) {
            $conn->prepare("UPDATE esami_risposte_candidati SET risposta_id=:r WHERE sessione_id=:s AND domanda_id=:d")
                 ->execute([':r' => $ris_id, ':s' => $sessione_id, ':d' => $dom_id]);
        } else {
            $conn->prepare("INSERT INTO esami_risposte_candidati (sessione_id, domanda_id, risposta_id) VALUES (:s,:d,:r)")
                 ->execute([':s' => $sessione_id, ':d' => $dom_id, ':r' => $ris_id]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($_POST['action'] === 'consegna') {
        // Rileggi risposte aggiornate dal DB
        $stmtRC2 = $conn->prepare("SELECT domanda_id, risposta_id FROM esami_risposte_candidati WHERE sessione_id = :s");
        $stmtRC2->execute([':s' => $sessione_id]);
        $risposte_finali = [];
        foreach ($stmtRC2->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $risposte_finali[(int)$r['domanda_id']] = (int)$r['risposta_id'];
        }

        $tot     = count($domande);
        $correct = 0;
        foreach ($domande as $dom) {
            $ris_id = $risposte_finali[(int)$dom['id']] ?? null;
            if (!$ris_id) continue;
            $stmtCorr = $conn->prepare("SELECT corretta FROM esami_risposte WHERE id=:id");
            $stmtCorr->execute([':id' => $ris_id]);
            $ris = $stmtCorr->fetch(PDO::FETCH_ASSOC);
            if ($ris && (int)$ris['corretta'] === 1) $correct++;
        }

        $punteggio = $tot > 0 ? round(($correct / $tot) * 100, 2) : 0;
        $conn->prepare("UPDATE esami_sessioni SET completato=1, terminato_il=NOW(), punteggio=:p WHERE id=:id")
             ->execute([':p' => $punteggio, ':id' => $sessione_id]);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'redirect' => 'esami_risultato_candidato.php']);
        exit;
    }
}

// Calcola secondi rimasti — usa il DB per evitare sfasamenti timezone
// MariaDB calcola la differenza internamente, senza dipendere da time() PHP
$stmtTimer = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, iniziato_il, NOW()) AS trascorsi FROM esami_sessioni WHERE id = :id");
$stmtTimer->execute([':id' => $sessione_id]);
$timerRow = $stmtTimer->fetch(PDO::FETCH_ASSOC);
$durata_minuti   = (int)$esame['durata_minuti'];
$durata_s        = $durata_minuti * 60;
$trascorsi       = max(0, (int)$timerRow['trascorsi']);
$secondi_rimasti = max(0, $durata_s - $trascorsi);
$tot_domande     = count($domande);

// JSON sicuro per JS
$domande_json       = json_encode($domande, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
$risposte_date_json = json_encode($risposte_date, JSON_FORCE_OBJECT);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esame — <?= htmlspecialchars($esame['titolo']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .timer-box { font-size: 2rem; font-weight: 700; letter-spacing: 2px; }
        .timer-warning { color: #dc3545; animation: pulse 1s infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
        .nav-btn { width:38px; height:38px; padding:0; font-size:.85rem; font-weight:600; border-radius:6px; }
        .nav-btn.risposta-data { background:#0d6efd !important; color:#fff !important; border-color:#0d6efd !important; }
        .nav-btn.corrente { outline:3px solid #ffc107; outline-offset:2px; }
        .risposta-opt {
            cursor:pointer; border:2px solid #dee2e6; border-radius:10px;
            padding:14px 18px; margin-bottom:10px; transition:all .15s;
            display:flex; align-items:center; gap:14px; user-select:none;
        }
        .risposta-opt:hover { border-color:#0d6efd; background:#e8f0fe; }
        .risposta-opt.sel   { border-color:#0d6efd; background:#dbeafe; font-weight:600; }
        .lettera {
            width:34px; height:34px; border-radius:50%; background:#6c757d; color:#fff;
            display:flex; align-items:center; justify-content:center;
            font-weight:700; flex-shrink:0; font-size:1rem;
        }
        .risposta-opt.sel .lettera { background:#0d6efd; }
        .sticky-top-bar { position:sticky; top:0; z-index:100; }
        #slide-domanda { min-height:320px; }
    </style>
</head>
<body>

<!-- NAVBAR TIMER -->
<div class="sticky-top-bar bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center shadow">
    <div class="text-truncate" style="max-width:50%">
        <span class="fw-bold"><?= htmlspecialchars($esame['titolo']) ?></span>
        <span class="ms-2 text-white-50 d-none d-md-inline">— <?= htmlspecialchars($_SESSION['candidato_nome']) ?></span>
    </div>
    <div class="timer-box" id="timer">--:--</div>
    <button class="btn btn-danger btn-sm fw-bold" id="btn-consegna">📤 Consegna</button>
</div>

<div class="container py-4">
    <div class="row g-3">

        <!-- SIDEBAR -->
        <div class="col-md-3">
            <div class="card shadow-sm p-3">
                <p class="fw-bold mb-2">Domande</p>
                <div class="d-flex flex-wrap gap-1" id="nav-container">
                    <?php foreach ($domande as $idx => $dom): ?>
                    <button class="btn btn-outline-secondary nav-btn<?= isset($risposte_date[$dom['id']]) ? ' risposta-data' : '' ?>"
                            id="nav-<?= $idx ?>">
                        <?= $idx + 1 ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <hr class="my-2">
                <small class="text-muted">🔵 = Risposta data</small>
                <div class="mt-1 fw-semibold" id="counter">0 / <?= $tot_domande ?> risposte</div>
                <div class="progress mt-1" style="height:5px;">
                    <div class="progress-bar bg-primary" id="prog-bar" style="width:0%"></div>
                </div>
            </div>
        </div>

        <!-- SLIDE -->
        <div class="col-md-9">
            <div class="card shadow-sm p-4" id="slide-domanda">
                <div id="domanda-body"></div>
                <div class="d-flex justify-content-between mt-4">
                    <button class="btn btn-outline-secondary" id="btn-prec">← Precedente</button>
                    <button class="btn btn-primary" id="btn-succ">Successiva →</button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL CONSEGNA -->
<div class="modal fade" id="modal-consegna" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">📤 Conferma Consegna</h5>
            </div>
            <div class="modal-body" id="modal-msg"></div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-outline-secondary" id="btn-annulla" data-bs-dismiss="modal">Annulla</button>
                <button class="btn btn-danger fw-bold" id="btn-ok-consegna">Sì, consegna</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    // ── DATI ────────────────────────────────────────────────────────────────
    var DOMANDE   = <?= $domande_json ?>;
    var RIS_INIT  = <?= $risposte_date_json ?>;
    var risposte  = {};
    Object.keys(RIS_INIT).forEach(function(k) { risposte[parseInt(k)] = RIS_INIT[k]; });

    var corrente = 0;
    var secondi  = <?= (int)$secondi_rimasti ?>;
    var scaduto  = false;

    var modalEl = document.getElementById('modal-consegna');
    var modalBS = new bootstrap.Modal(modalEl);

    // ── UTILITY ─────────────────────────────────────────────────────────────
    function esc(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str)));
        return d.innerHTML;
    }

    // ── RENDER DOMANDA ───────────────────────────────────────────────────────
    function renderDomanda(idx) {
        var dom   = DOMANDE[idx];
        var selId = risposte[dom.id] || null;

        var html = '<p class="text-muted small mb-1">Domanda ' + (idx + 1) + ' di ' + DOMANDE.length + '</p>' +
                   '<h5 class="fw-bold mb-4">' + esc(dom.testo) + '</h5>';

        dom.risposte.forEach(function(r) {
            var sel = (selId !== null && parseInt(r.id) === selId);
            html += '<div class="risposta-opt' + (sel ? ' sel' : '') + '" data-did="' + dom.id + '" data-rid="' + r.id + '">' +
                        '<span class="lettera">' + esc(r.lettera) + '</span>' +
                        '<span>' + esc(r.testo) + '</span>' +
                    '</div>';
        });

        document.getElementById('domanda-body').innerHTML = html;

        // Click handlers
        document.querySelectorAll('.risposta-opt').forEach(function(el) {
            el.addEventListener('click', function() {
                seleziona(parseInt(this.dataset.did), parseInt(this.dataset.rid));
            });
        });

        // Aggiorna nav buttons
        document.querySelectorAll('.nav-btn').forEach(function(b, i) {
            b.classList.toggle('corrente', i === idx);
        });

        document.getElementById('btn-prec').disabled = (idx === 0);
        document.getElementById('btn-succ').style.visibility = (idx === DOMANDE.length - 1) ? 'hidden' : 'visible';
    }

    // ── SELEZIONE RISPOSTA ───────────────────────────────────────────────────
    function seleziona(domId, risId) {
        risposte[domId] = risId;

        document.querySelectorAll('.risposta-opt').forEach(function(el) {
            el.classList.toggle('sel', parseInt(el.dataset.rid) === risId);
        });

        var nb = document.getElementById('nav-' + corrente);
        if (nb) nb.classList.add('risposta-data');

        aggiornaCounter();

        fetch('esami_svolgi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=salva_risposta&domanda_id=' + domId + '&risposta_id=' + risId
        }).catch(function() {});
    }

    function aggiornaCounter() {
        var n   = Object.keys(risposte).length;
        var tot = DOMANDE.length;
        document.getElementById('counter').textContent  = n + ' / ' + tot + ' risposte';
        document.getElementById('prog-bar').style.width = (tot ? Math.round(n / tot * 100) : 0) + '%';
    }

    // ── NAVIGAZIONE ──────────────────────────────────────────────────────────
    function vaiA(idx) {
        if (idx < 0 || idx >= DOMANDE.length) return;
        corrente = idx;
        renderDomanda(idx);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.getElementById('btn-prec').addEventListener('click', function() { vaiA(corrente - 1); });
    document.getElementById('btn-succ').addEventListener('click', function() { vaiA(corrente + 1); });

    document.querySelectorAll('.nav-btn').forEach(function(btn, i) {
        btn.addEventListener('click', function() { vaiA(i); });
    });

    // ── CONSEGNA ─────────────────────────────────────────────────────────────
    function apriConsegna(auto) {
        var n       = Object.keys(risposte).length;
        var mancano = DOMANDE.length - n;
        var msg;

        if (auto) {
            document.getElementById('btn-annulla').style.display = 'none';
            msg = '<div class="alert alert-warning mb-0">⏰ <strong>Tempo scaduto!</strong> L\'esame verrà consegnato ora.</div>';
        } else {
            document.getElementById('btn-annulla').style.display = '';
            msg = mancano > 0
                ? '<div class="alert alert-warning">⚠️ Hai <strong>' + mancano + '</strong> domanda/e senza risposta.</div>'
                : '<div class="alert alert-success">✅ Hai risposto a tutte le domande.</div>';
            msg += '<p class="mb-0">Sei sicuro di voler consegnare?</p>';
        }
        document.getElementById('modal-msg').innerHTML = msg;
        modalBS.show();
    }

    document.getElementById('btn-consegna').addEventListener('click', function() { apriConsegna(false); });

    document.getElementById('btn-ok-consegna').addEventListener('click', function() {
        var btn = this;
        btn.disabled    = true;
        btn.textContent = 'Consegna in corso...';

        fetch('esami_svolgi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=consegna'
        })
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.redirect) window.location.href = d.redirect; })
        .catch(function() { btn.disabled = false; btn.textContent = 'Riprova'; });
    });

    // ── TIMER ─────────────────────────────────────────────────────────────────
    function tick() {
        if (scaduto) return;
        var m  = String(Math.floor(secondi / 60)).padStart(2, '0');
        var s  = String(secondi % 60).padStart(2, '0');
        var el = document.getElementById('timer');
        el.textContent = m + ':' + s;
        el.classList.toggle('timer-warning', secondi <= 300);

        if (secondi <= 0) {
            scaduto = true;
            apriConsegna(true);
            return;
        }
        secondi--;
    }

    // ── INIT ──────────────────────────────────────────────────────────────────
    renderDomanda(0);
    aggiornaCounter();
    tick();
    setInterval(tick, 1000);

})();
</script>
</body>
</html>
