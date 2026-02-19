<?php
require_once __DIR__ . "/auth/functions.php";
require_login("admin");
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Admin • Dashboard Verifikasi</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:      #0b1120; --surface: #131d33; --card: #192035;
      --border:  #253050; --accent:  #3b82f6; --text: #e2e8f0;
      --muted:   #8898b8; --danger:  #ef4444; --success: #22c55e;
    }
    body { background:var(--bg); color:var(--text); font-family:'Segoe UI',system-ui,sans-serif; font-size:14px; }

    /* Topbar */
    .topbar { background:var(--surface); border-bottom:1px solid var(--border); padding:0 24px; height:60px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; }
    .brand  { display:flex; align-items:center; gap:12px; }
    .brand-title { font-weight:700; font-size:15px; }
    .brand-sub   { font-size:11px; color:var(--muted); }

    /* Layout */
    .container { max-width:1300px; margin:0 auto; padding:0 24px; }
    .main       { padding:20px 0 40px; }
    .layout     { display:flex; gap:16px; align-items:flex-start; margin-top:16px; }
    .panel-left  { flex:1; min-width:0; }
    .panel-right { width:380px; flex-shrink:0; }
    @media(max-width:900px){ .layout{flex-direction:column} .panel-right{width:100%} }

    /* Card */
    .card { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:20px; }

    /* KPI */
    .kpi-row { display:flex; gap:12px; flex-wrap:wrap; margin:16px 0; }
    .kpi-box { flex:1; min-width:110px; background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:14px 18px; text-align:center; }
    .kpi-label { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; }
    .kpi-val   { font-size:28px; font-weight:900; margin-top:4px; }

    /* Badge */
    .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; }
    .s-pending     { background:#78350f22; color:#fbbf24; border:1px solid #f59e0b44; }
    .s-verified    { background:#1e3a5f22; color:#60a5fa; border:1px solid #3b82f644; }
    .s-in_progress { background:#1e1b4b22; color:#a78bfa; border:1px solid #7c3aed44; }
    .s-resolved    { background:#14532d22; color:#4ade80; border:1px solid #22c55e44; }
    .s-rejected    { background:#7f1d1d22; color:#f87171; border:1px solid #ef444444; }

    /* Toolbar */
    .toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; margin-bottom:14px; }
    .toolbar-group { display:flex; gap:10px; flex-wrap:wrap; }
    label { display:block; font-size:11px; color:var(--muted); margin-bottom:4px; }
    input, select, textarea { background:var(--surface); border:1px solid var(--border); border-radius:8px; color:var(--text); padding:8px 12px; font-size:13px; width:100%; outline:none; transition:border .2s; }
    input:focus, select:focus, textarea:focus { border-color:var(--accent); }

    /* Table */
    .table-wrap { overflow-x:auto; border-radius:10px; border:1px solid var(--border); }
    table       { width:100%; border-collapse:collapse; }
    thead th    { background:var(--surface); padding:10px 14px; text-align:left; font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border); white-space:nowrap; }
    tbody tr    { border-bottom:1px solid var(--border); cursor:pointer; transition:background .15s; }
    tbody tr:hover  { background:rgba(59,130,246,.06); }
    tbody tr.active { background:rgba(59,130,246,.12) !important; }
    tbody tr:last-child { border-bottom:none; }
    td { padding:10px 14px; font-size:13px; vertical-align:middle; }
    .td-id  { font-weight:700; color:var(--accent); width:50px; }
    .td-time{ color:var(--muted); white-space:nowrap; width:140px; }
    .td-ring{ color:var(--muted); max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .no-data{ text-align:center; padding:30px; color:var(--muted); }

    /* Buttons */
    .btn-row { display:flex; gap:8px; margin-top:14px; }
    .btn { padding:8px 18px; border-radius:8px; border:none; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .15s; }
    .btn:hover { opacity:.87; }
    .btn-primary { background:var(--accent); color:#fff; }
    .btn-danger  { background:var(--danger); color:#fff; }
    .btn-ghost   { background:var(--surface); color:var(--text); border:1px solid var(--border); }
    .btn-success { background:var(--success); color:#fff; }

    /* Detail panel */
    .detail-empty { color:var(--muted); text-align:center; padding:30px 0; font-size:13px; }
    .field-block  { margin-top:14px; }
    .field-val    { font-size:13px; margin-top:4px; line-height:1.6; }
    .coord        { font-family:monospace; font-size:12px; color:#38bdf8; }
    .divider      { border:none; border-top:1px solid var(--border); margin:16px 0; }

    /* Modal */
    .overlay { position:fixed; inset:0; background:rgba(0,0,0,.7); display:flex; align-items:center; justify-content:center; z-index:200; opacity:0; pointer-events:none; transition:opacity .25s; }
    .overlay.show { opacity:1; pointer-events:all; }
    .modal { background:var(--card); border:1px solid var(--border); border-radius:14px; padding:24px; width:min(480px,95vw); max-height:90vh; overflow-y:auto; }
    .modal-title { font-size:16px; font-weight:700; margin-bottom:16px; }
    .close-btn { float:right; cursor:pointer; color:var(--muted); font-size:20px; line-height:1; }

    /* Toast */
    #toast { position:fixed; bottom:24px; right:24px; background:#1e3a5f; border:1px solid #3b82f6; color:#93c5fd; border-radius:10px; padding:12px 20px; font-size:13px; font-weight:600; opacity:0; transform:translateY(20px); transition:all .3s; pointer-events:none; z-index:999; }
    #toast.show { opacity:1; transform:translateY(0); }
    #toast.err  { background:#450a0a; border-color:#ef4444; color:#fca5a5; }

    /* User badge in topbar */
    .user-pill { background:rgba(59,130,246,.15); border:1px solid rgba(59,130,246,.3); border-radius:20px; padding:4px 12px; font-size:12px; display:flex; align-items:center; gap:6px; }
  </style>
</head>
<body>

<nav class="topbar">
  <div class="brand">
    <span style="font-size:24px">🛡️</span>
    <div>
      <div class="brand-title">Admin Dashboard — Verifikasi Laporan</div>
      <div class="brand-sub">PHP + MySQL • Terhubung ke database</div>
    </div>
  </div>
  <div style="display:flex;gap:10px;align-items:center">
    <div class="user-pill">
      🛡️ <?= htmlspecialchars($user['nama']) ?> (admin)
    </div>
    <a href="auth/logout.php" class="btn btn-ghost" style="text-decoration:none;font-size:13px;padding:6px 14px">Logout</a>
    <button class="btn btn-success" id="btnTambah">+ Tambah</button>
  </div>
</nav>

<div class="container main">

  <!-- KPI -->
  <div class="kpi-row">
    <div class="kpi-box"><div class="kpi-label">Pending</div>     <div class="kpi-val" id="kPending"    style="color:#fbbf24">0</div></div>
    <div class="kpi-box"><div class="kpi-label">Verified</div>    <div class="kpi-val" id="kVerified"   style="color:#60a5fa">0</div></div>
    <div class="kpi-box"><div class="kpi-label">In Progress</div> <div class="kpi-val" id="kInProgress" style="color:#a78bfa">0</div></div>
    <div class="kpi-box"><div class="kpi-label">Resolved</div>    <div class="kpi-val" id="kResolved"   style="color:#4ade80">0</div></div>
    <div class="kpi-box"><div class="kpi-label">Rejected</div>    <div class="kpi-val" id="kRejected"   style="color:#f87171">0</div></div>
  </div>

  <!-- Toolbar -->
  <div class="toolbar">
    <div class="toolbar-group">
      <div>
        <label>🔍 Cari</label>
        <input id="q" style="width:240px" placeholder="Kategori / ringkasan…"/>
      </div>
      <div>
        <label>Filter Status</label>
        <select id="statusFilter" style="width:160px">
          <option value="all">Semua Status</option>
          <option value="pending">pending</option>
          <option value="verified">verified</option>
          <option value="in_progress">in_progress</option>
          <option value="resolved">resolved</option>
          <option value="rejected">rejected</option>
        </select>
      </div>
    </div>
    <span style="font-size:12px;color:var(--muted)" id="rowCount"></span>
  </div>

  <!-- Layout -->
  <div class="layout">
    <!-- Table -->
    <div class="panel-left">
      <div class="table-wrap">
        <table>
          <thead><tr>
            <th>ID</th><th>Waktu</th><th>Kategori</th><th>Status</th><th>Ringkasan</th>
          </tr></thead>
          <tbody id="tbody"></tbody>
        </table>
      </div>
    </div>

    <!-- Detail -->
    <div class="panel-right">
      <div class="card">
        <div style="font-size:16px;font-weight:700">📋 Detail Laporan</div>
        <div id="detailEmpty" class="detail-empty">← Klik baris untuk lihat detail</div>

        <div id="detail" style="display:none">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px">
            <div>
              <div style="font-size:11px;color:var(--muted)">ID Laporan</div>
              <div style="font-size:22px;font-weight:900;color:var(--accent)">#<span id="dId"></span></div>
            </div>
            <span class="badge" id="dStatus"></span>
          </div>

          <div class="field-block"><label>Waktu</label><div class="field-val" id="dWaktu"></div></div>
          <div class="field-block"><label>Kategori</label><div class="field-val" id="dKategori"></div></div>
          <div class="field-block"><label>Koordinat</label><div class="field-val coord" id="dCoord"></div></div>
          <div class="field-block"><label>Ringkasan</label><div class="field-val" id="dRingkasan"></div></div>
          <div class="field-block"><label>Deskripsi</label><div class="field-val" id="dDeskripsi" style="color:var(--muted)"></div></div>
          <div class="field-block"><label>Catatan Admin</label><div class="field-val" id="dCatatan" style="color:var(--muted);font-style:italic"></div></div>

          <hr class="divider"/>

          <div class="field-block">
            <label>Ubah Status</label>
            <select id="editStatus">
              <option value="pending">pending</option>
              <option value="verified">verified</option>
              <option value="in_progress">in_progress</option>
              <option value="resolved">resolved</option>
              <option value="rejected">rejected</option>
            </select>
          </div>
          <div class="field-block">
            <label>Catatan Admin</label>
            <textarea id="editNote" rows="3" placeholder="Tulis catatan verifikasi…"></textarea>
          </div>
          <div class="btn-row">
            <button class="btn btn-primary" id="btnSave">💾 Simpan</button>
            <button class="btn btn-danger"  id="btnDelete">🗑 Hapus</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah -->
<div class="overlay" id="overlay">
  <div class="modal">
    <span class="close-btn" id="closeModal">✕</span>
    <div class="modal-title">➕ Tambah Laporan Baru</div>
    <div class="field-block">
      <label>Kategori *</label>
      <select id="fKategori">
        <option value="Air Bersih">Air Bersih</option>
        <option value="Sanitasi">Sanitasi</option>
        <option value="Limbah">Limbah</option>
        <option value="Drainase">Drainase</option>
        <option value="Lainnya">Lainnya</option>
      </select>
    </div>
    <div class="field-block">
      <label>Ringkasan *</label>
      <input id="fRingkasan" placeholder="Ringkasan singkat masalah"/>
    </div>
    <div class="field-block">
      <label>Deskripsi</label>
      <textarea id="fDeskripsi" rows="3" placeholder="Detail masalah…"></textarea>
    </div>
    <div style="display:flex;gap:10px">
      <div class="field-block" style="flex:1"><label>Latitude</label><input id="fLat" type="number" step="any" placeholder="-6.200000"/></div>
      <div class="field-block" style="flex:1"><label>Longitude</label><input id="fLng" type="number" step="any" placeholder="106.816666"/></div>
    </div>
    <div class="field-block">
      <label>Status Awal</label>
      <select id="fStatus">
        <option value="pending">pending</option>
        <option value="verified">verified</option>
      </select>
    </div>
    <div class="btn-row">
      <button class="btn btn-primary" id="btnSubmit">Simpan</button>
      <button class="btn btn-ghost"   id="btnCancel">Batal</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
const API = 'api.php';
let selectedId = null;

const $ = q => document.querySelector(q);

function toast(msg, err=false) {
  const el = $('#toast');
  el.textContent = msg;
  el.className = 'show' + (err?' err':'');
  clearTimeout(el._t);
  el._t = setTimeout(()=>el.className='', 3000);
}

function fmtDate(iso) {
  if (!iso) return '-';
  return new Date(iso).toLocaleString('id-ID',{dateStyle:'medium',timeStyle:'short'});
}

function badge(status) {
  return `<span class="badge s-${status}">${status}</span>`;
}

function esc(s='') {
  return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// Load data
async function load() {
  const q = $('#q').value;
  const s = $('#statusFilter').value;
  try {
    const res  = await fetch(`${API}?q=${encodeURIComponent(q)}&status=${s}`);
    const data = await res.json();
    if (data.error) { toast('❌ '+data.message, true); return; }
    renderKPI(data.kpi);
    renderTable(data.rows);
  } catch(e) { toast('❌ Gagal terhubung ke server', true); }
}

function renderKPI(k) {
  $('#kPending').textContent    = k.pending;
  $('#kVerified').textContent   = k.verified;
  $('#kInProgress').textContent = k.in_progress;
  $('#kResolved').textContent   = k.resolved;
  $('#kRejected').textContent   = k.rejected;
}

function renderTable(rows) {
  $('#rowCount').textContent = rows.length + ' laporan';
  const tb = $('#tbody');
  if (!rows.length) {
    tb.innerHTML = `<tr><td colspan="5" class="no-data">Tidak ada data.</td></tr>`;
    return;
  }
  tb.innerHTML = rows.map(r=>`
    <tr data-id="${r.id}" class="${r.id==selectedId?'active':''}">
      <td class="td-id">${r.id}</td>
      <td class="td-time">${fmtDate(r.waktu)}</td>
      <td>${esc(r.kategori)}</td>
      <td>${badge(r.status)}</td>
      <td class="td-ring">${esc(r.ringkasan||'')}</td>
    </tr>`).join('');
  tb.querySelectorAll('tr[data-id]').forEach(tr=>{
    tr.addEventListener('click',()=>openDetail(tr.dataset.id));
  });
}

async function openDetail(id) {
  selectedId = id;
  document.querySelectorAll('tbody tr').forEach(tr=>tr.classList.toggle('active',tr.dataset.id==id));
  const res  = await fetch(`${API}?action=detail&id=${id}`);
  const r    = await res.json();
  if (r.error) { toast('Gagal memuat', true); return; }

  $('#detailEmpty').style.display='none';
  $('#detail').style.display='block';
  $('#dId').textContent        = r.id;
  const ds = $('#dStatus');
  ds.className = `badge s-${r.status}`;
  ds.textContent = r.status;
  $('#dWaktu').textContent     = fmtDate(r.waktu);
  $('#dKategori').textContent  = r.kategori;
  $('#dCoord').textContent     = r.latitude ? `${parseFloat(r.latitude).toFixed(6)}, ${parseFloat(r.longitude).toFixed(6)}` : '—';
  $('#dRingkasan').textContent = r.ringkasan||'—';
  $('#dDeskripsi').textContent = r.deskripsi||'—';
  $('#dCatatan').textContent   = r.catatan_admin||'(belum ada catatan)';
  $('#editStatus').value       = r.status;
  $('#editNote').value         = r.catatan_admin||'';
}

$('#btnSave').addEventListener('click', async ()=>{
  if (!selectedId) return;
  const res = await fetch(`${API}?action=update`,{
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({id:selectedId, status:$('#editStatus').value, catatan:$('#editNote').value.trim()})
  });
  const d = await res.json();
  if (d.success) { toast('✅ Disimpan!'); load(); openDetail(selectedId); }
  else toast('❌ Gagal', true);
});

$('#btnDelete').addEventListener('click', async ()=>{
  if (!selectedId || !confirm(`Hapus laporan #${selectedId}?`)) return;
  const res = await fetch(`${API}?action=hapus`,{
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({id:selectedId})
  });
  const d = await res.json();
  if (d.success) {
    selectedId=null;
    $('#detail').style.display='none';
    $('#detailEmpty').style.display='block';
    toast('🗑 Dihapus.');
    load();
  }
});

// Modal
$('#btnTambah').addEventListener('click', ()=>$('#overlay').classList.add('show'));
$('#closeModal').addEventListener('click',()=>$('#overlay').classList.remove('show'));
$('#btnCancel').addEventListener('click', ()=>$('#overlay').classList.remove('show'));
$('#overlay').addEventListener('click',e=>{if(e.target===$('#overlay'))$('#overlay').classList.remove('show');});

$('#btnSubmit').addEventListener('click', async ()=>{
  const kat  = $('#fKategori').value;
  const ring = $('#fRingkasan').value.trim();
  if (!ring) { toast('⚠️ Ringkasan wajib diisi', true); return; }
  const res = await fetch(`${API}?action=tambah`,{
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({kategori:kat, ringkasan:ring, deskripsi:$('#fDeskripsi').value.trim(), latitude:$('#fLat').value||null, longitude:$('#fLng').value||null, status:$('#fStatus').value})
  });
  const d = await res.json();
  if (d.success) {
    toast(`✅ Laporan #${d.id} ditambahkan!`);
    $('#overlay').classList.remove('show');
    ['#fRingkasan','#fDeskripsi','#fLat','#fLng'].forEach(s=>$(s).value='');
    load();
  }
});

let debT;
$('#q').addEventListener('input',()=>{clearTimeout(debT);debT=setTimeout(load,350);});
$('#statusFilter').addEventListener('change', load);

load();
</script>
</body>
</html>
