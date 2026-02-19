<?php
require_once __DIR__ . "/auth/functions.php";
require_login("user");
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>User • Pelaporan Air & Sanitasi</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    :root {
      --bg:#0b1120; --surface:#131d33; --card:#192035;
      --border:#253050; --accent:#3b82f6; --text:#e2e8f0;
      --muted:#8898b8; --danger:#ef4444; --success:#22c55e;
    }
    body { background:var(--bg); color:var(--text); font-family:'Segoe UI',system-ui,sans-serif; font-size:14px; }

    /* Topbar */
    .topbar { background:var(--surface); border-bottom:1px solid var(--border); padding:0 20px; height:60px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:100; }
    .brand  { display:flex; align-items:center; gap:10px; }
    .brand-title { font-weight:700; font-size:15px; }
    .brand-sub   { font-size:11px; color:var(--muted); }
    .user-pill   { background:rgba(59,130,246,.15); border:1px solid rgba(59,130,246,.3); border-radius:20px; padding:4px 12px; font-size:12px; }

    /* Grid layout */
    .container { max-width:1280px; margin:0 auto; padding:0 20px; }
    .grid-main  { display:grid; grid-template-columns:1fr 420px; gap:16px; padding:16px 0 40px; }
    @media(max-width:900px){ .grid-main{ grid-template-columns:1fr; } }

    /* Card */
    .card { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:20px; }
    .card-title { font-size:15px; font-weight:700; margin-bottom:4px; }
    .card-sub   { font-size:12px; color:var(--muted); margin-bottom:14px; }

    /* Map */
    #map { height:380px; border-radius:10px; border:1px solid var(--border); margin-bottom:12px; }

    /* Form */
    .field { margin-bottom:14px; }
    label  { display:block; font-size:11px; color:var(--muted); margin-bottom:5px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; }
    input[type=text], input[type=number], select, textarea {
      width:100%; background:#0b1120; border:1px solid var(--border);
      border-radius:8px; color:var(--text); padding:9px 12px;
      font-size:13px; outline:none; transition:border .2s;
    }
    input:focus, select:focus, textarea:focus { border-color:var(--accent); }
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }

    /* Buttons */
    .btn-row { display:flex; gap:8px; flex-wrap:wrap; margin-top:4px; }
    .btn { padding:9px 18px; border-radius:8px; border:none; font-size:13px; font-weight:600; cursor:pointer; transition:opacity .15s; }
    .btn:hover { opacity:.85; }
    .btn-primary { background:var(--accent); color:#fff; }
    .btn-ghost   { background:var(--surface); color:var(--text); border:1px solid var(--border); }
    .btn-danger  { background:var(--danger); color:#fff; }

    /* KPI */
    .kpi-row { display:flex; gap:10px; margin-bottom:14px; }
    .kpi-box { flex:1; background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:12px; text-align:center; }
    .kpi-label { font-size:10px; color:var(--muted); text-transform:uppercase; }
    .kpi-val   { font-size:22px; font-weight:900; margin-top:2px; }

    /* Badge */
    .badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; }
    .s-pending     { background:#78350f22; color:#fbbf24; border:1px solid #f59e0b44; }
    .s-verified    { background:#1e3a5f22; color:#60a5fa; border:1px solid #3b82f644; }
    .s-in_progress { background:#1e1b4b22; color:#a78bfa; border:1px solid #7c3aed44; }
    .s-resolved    { background:#14532d22; color:#4ade80; border:1px solid #22c55e44; }
    .s-rejected    { background:#7f1d1d22; color:#f87171; border:1px solid #ef444444; }

    /* Report list */
    .report-item { background:var(--surface); border:1px solid var(--border); border-radius:10px; padding:12px 14px; margin-bottom:10px; }
    .report-item:last-child { margin-bottom:0; }
    .report-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
    .report-cat  { font-weight:700; font-size:13px; }
    .report-time { font-size:11px; color:var(--muted); }
    .report-ring { font-size:12px; color:var(--muted); }

    /* Filter row */
    .filter-row { display:flex; gap:10px; margin-bottom:14px; align-items:flex-end; }

    /* Legend */
    .legend { display:flex; gap:14px; flex-wrap:wrap; margin-top:10px; }
    .legend-item { display:flex; align-items:center; gap:5px; font-size:12px; color:var(--muted); }
    .dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
    .dot-pending     { background:#fbbf24; }
    .dot-verified    { background:#60a5fa; }
    .dot-in_progress { background:#a78bfa; }
    .dot-resolved    { background:#4ade80; }
    .dot-rejected    { background:#f87171; }

    /* Alert */
    .alert { padding:10px 14px; border-radius:8px; font-size:13px; margin-top:12px; display:none; }
    .alert-ok  { background:#14532d33; color:#86efac; border:1px solid #22c55e55; display:block; }
    .alert-err { background:#450a0a33; color:#fca5a5; border:1px solid #ef444455; display:block; }

    /* Toast */
    #toast { position:fixed; bottom:24px; right:24px; background:#1e3a5f; border:1px solid #3b82f6; color:#93c5fd; border-radius:10px; padding:12px 20px; font-size:13px; font-weight:600; opacity:0; transform:translateY(20px); transition:all .3s; pointer-events:none; z-index:999; }
    #toast.show { opacity:1; transform:translateY(0); }
    #toast.err  { background:#450a0a; border-color:#ef4444; color:#fca5a5; }

    /* Loading */
    .spinner { display:inline-block; width:16px; height:16px; border:2px solid rgba(255,255,255,.3); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; margin-right:6px; vertical-align:middle; }
    @keyframes spin { to{ transform:rotate(360deg); } }

    /* Side container */
    .side { display:flex; flex-direction:column; gap:16px; }
  </style>
</head>
<body>

<nav class="topbar">
  <div class="brand">
    <span style="font-size:24px">💧</span>
    <div>
      <div class="brand-title">Pelaporan Air & Sanitasi</div>
      <div class="brand-sub">Data tersimpan ke database MySQL</div>
    </div>
  </div>
  <div style="display:flex;gap:10px;align-items:center">
    <div class="user-pill">👤 <?= htmlspecialchars($user['nama']) ?></div>
    <a href="auth/logout.php" class="btn btn-ghost" style="text-decoration:none;font-size:12px;padding:6px 12px">Logout</a>
  </div>
</nav>

<div class="container">
  <div class="grid-main">

    <!-- Kolom Kiri: Peta -->
    <div>
      <div class="card">
        <div class="card-title">🗺️ Peta Laporan</div>
        <div class="card-sub">Klik peta untuk mengisi koordinat laporan Anda</div>

        <div style="display:flex;gap:10px;margin-bottom:12px;align-items:flex-end">
          <div style="flex:1">
            <label>Filter Status di Peta</label>
            <select id="mapFilter">
              <option value="all">Semua Status</option>
              <option value="pending">pending</option>
              <option value="verified">verified</option>
              <option value="in_progress">in_progress</option>
              <option value="resolved">resolved</option>
              <option value="rejected">rejected</option>
            </select>
          </div>
          <button class="btn btn-ghost" id="btnRefreshMap" style="white-space:nowrap">🔄 Refresh</button>
        </div>

        <div id="map"></div>

        <div class="legend">
          <div class="legend-item"><span class="dot dot-pending"></span>Pending</div>
          <div class="legend-item"><span class="dot dot-verified"></span>Verified</div>
          <div class="legend-item"><span class="dot dot-in_progress"></span>In Progress</div>
          <div class="legend-item"><span class="dot dot-resolved"></span>Resolved</div>
          <div class="legend-item"><span class="dot dot-rejected"></span>Rejected</div>
        </div>
      </div>
    </div>

    <!-- Kolom Kanan -->
    <div class="side">

      <!-- Form Laporan -->
      <div class="card">
        <div class="card-title">📝 Buat Laporan</div>
        <div class="card-sub">Isi form di bawah untuk mengirim laporan baru</div>

        <form id="reportForm">
          <div class="grid2">
            <div class="field">
              <label>Latitude *</label>
              <input type="number" id="lat" name="lat" step="any" placeholder="-6.200000" required/>
            </div>
            <div class="field">
              <label>Longitude *</label>
              <input type="number" id="lng" name="lng" step="any" placeholder="106.816666" required/>
            </div>
          </div>

          <div class="btn-row" style="margin-bottom:14px">
            <button type="button" class="btn btn-ghost" id="btnGPS">📍 Ambil GPS</button>
            <span style="font-size:11px;color:var(--muted);align-self:center">atau klik peta</span>
          </div>

          <div class="field">
            <label>Kategori Masalah *</label>
            <select id="category" name="category" required>
              <option value="Air Bersih">Air Bersih / Air Keruh</option>
              <option value="Air Tidak Tersedia">Air Tidak Tersedia</option>
              <option value="Pipa Bocor">Pipa Bocor</option>
              <option value="Sanitasi">Sanitasi Buruk</option>
              <option value="Limbah">Pembuangan Limbah</option>
              <option value="Drainase">Drainase / Gorong-gorong</option>
              <option value="Lainnya">Lainnya</option>
            </select>
          </div>

          <div class="field">
            <label>Ringkasan *</label>
            <input type="text" id="ringkasan" name="ringkasan" placeholder="Judul singkat masalah" required/>
          </div>

          <div class="field">
            <label>Deskripsi Lengkap</label>
            <textarea id="desc" name="desc" rows="3" placeholder="Ceritakan kondisi di lapangan…"></textarea>
          </div>

          <div id="formMsg" class="alert"></div>

          <div class="btn-row" style="margin-top:14px">
            <button type="submit" class="btn btn-primary" id="btnSubmit">📤 Kirim Laporan</button>
            <button type="reset"  class="btn btn-ghost">Bersihkan</button>
          </div>
        </form>
      </div>

      <!-- Riwayat -->
      <div class="card">
        <div class="card-title">📋 Riwayat Laporan Saya</div>

        <!-- KPI -->
        <div class="kpi-row">
          <div class="kpi-box"><div class="kpi-label">Total</div><div class="kpi-val" id="kTotal" style="color:var(--accent)">0</div></div>
          <div class="kpi-box"><div class="kpi-label">Pending</div><div class="kpi-val" id="kPending" style="color:#fbbf24">0</div></div>
          <div class="kpi-box"><div class="kpi-label">Resolved</div><div class="kpi-val" id="kResolved" style="color:#4ade80">0</div></div>
        </div>

        <div class="filter-row">
          <div style="flex:1">
            <label>Filter</label>
            <select id="histFilter">
              <option value="all">Semua</option>
              <option value="pending">pending</option>
              <option value="verified">verified</option>
              <option value="in_progress">in_progress</option>
              <option value="resolved">resolved</option>
              <option value="rejected">rejected</option>
            </select>
          </div>
        </div>

        <div id="histList" style="max-height:320px;overflow-y:auto">
          <div style="text-align:center;padding:20px;color:var(--muted)">Memuat data…</div>
        </div>
      </div>

    </div>
  </div>
</div>

<div id="toast"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const API   = 'api.php';
const USER_ID = <?= (int)$user['id'] ?>;

const $ = q => document.querySelector(q);

// ── Toast ──────────────────────────────────────────────────────────────────
function toast(msg, err=false) {
  const el = $('#toast');
  el.textContent = msg;
  el.className   = 'show' + (err?' err':'');
  clearTimeout(el._t);
  el._t = setTimeout(()=>el.className='', 3500);
}

function fmtDate(iso) {
  if (!iso) return '-';
  return new Date(iso).toLocaleString('id-ID',{dateStyle:'medium',timeStyle:'short'});
}

function esc(s='') {
  return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ── MAP ────────────────────────────────────────────────────────────────────
const map = L.map('map').setView([-6.2, 106.816], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors'
}).addTo(map);

const dotColors = {
  pending:'#fbbf24', verified:'#60a5fa',
  in_progress:'#a78bfa', resolved:'#4ade80', rejected:'#f87171'
};

function makeIcon(status) {
  const c = dotColors[status] || '#8898b8';
  return L.divIcon({
    className: '',
    html: `<div style="width:16px;height:16px;background:${c};border:2px solid white;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,.4)"></div>`,
    iconSize:[16,16], iconAnchor:[8,8]
  });
}

let markers = [];
function clearMarkers(){ markers.forEach(m=>map.removeLayer(m)); markers=[]; }

async function loadMapMarkers() {
  const s = $('#mapFilter').value;
  try {
    const res  = await fetch(`${API}?status=${s}`);
    const data = await res.json();
    if (data.error) return;
    clearMarkers();
    data.rows.forEach(r=>{
      if (!r.latitude || !r.longitude) return;
      const m = L.marker([r.latitude, r.longitude], {icon: makeIcon(r.status)})
        .addTo(map)
        .bindPopup(`<b>#${r.id} ${esc(r.kategori)}</b><br>${esc(r.ringkasan||'')}<br><span style="font-size:11px">${r.status}</span>`);
      markers.push(m);
    });
  } catch(e) { console.warn('Map load error', e); }
}

// Klik peta → isi form
map.on('click', e=>{
  $('#lat').value = e.latlng.lat.toFixed(6);
  $('#lng').value = e.latlng.lng.toFixed(6);
  toast('📍 Koordinat diisi dari klik peta');
});

$('#mapFilter').addEventListener('change', loadMapMarkers);
$('#btnRefreshMap').addEventListener('click', loadMapMarkers);

// ── GPS ────────────────────────────────────────────────────────────────────
$('#btnGPS').addEventListener('click', ()=>{
  if (!navigator.geolocation) { toast('GPS tidak didukung browser ini', true); return; }
  toast('⏳ Mengambil lokasi GPS…');
  navigator.geolocation.getCurrentPosition(pos=>{
    $('#lat').value = pos.coords.latitude.toFixed(6);
    $('#lng').value = pos.coords.longitude.toFixed(6);
    map.setView([pos.coords.latitude, pos.coords.longitude], 15);
    toast('📍 Lokasi GPS berhasil diambil!');
  }, ()=>toast('❌ Gagal mengambil GPS', true));
});

// ── Submit Laporan ─────────────────────────────────────────────────────────
$('#reportForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const lat  = parseFloat($('#lat').value);
  const lng  = parseFloat($('#lng').value);
  const ring = $('#ringkasan').value.trim();

  if (!ring)        { showFormMsg('Ringkasan wajib diisi.', true);  return; }
  if (isNaN(lat) || isNaN(lng)) { showFormMsg('Koordinat tidak valid.', true); return; }

  const btn = $('#btnSubmit');
  btn.innerHTML = '<span class="spinner"></span> Mengirim…';
  btn.disabled  = true;

  try {
    const res = await fetch(`${API}?action=tambah`, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        kategori:  $('#category').value,
        ringkasan: ring,
        deskripsi: $('#desc').value.trim(),
        latitude:  lat,
        longitude: lng,
        status:    'pending',
        user_id:   USER_ID
      })
    });
    const d = await res.json();
    if (d.success) {
      showFormMsg(`✅ Laporan #${d.id} berhasil dikirim! Status: pending`, false);
      $('#reportForm').reset();
      loadHistory();
      loadMapMarkers();
    } else {
      showFormMsg('❌ Gagal menyimpan: ' + (d.message||''), true);
    }
  } catch(err) {
    showFormMsg('❌ Tidak dapat terhubung ke server', true);
  } finally {
    btn.innerHTML = '📤 Kirim Laporan';
    btn.disabled  = false;
  }
});

function showFormMsg(msg, err) {
  const el = $('#formMsg');
  el.textContent = msg;
  el.className   = 'alert ' + (err ? 'alert-err' : 'alert-ok');
  setTimeout(()=>el.className='alert', 5000);
}

// ── Riwayat ────────────────────────────────────────────────────────────────
async function loadHistory() {
  const s = $('#histFilter').value;
  try {
    const res  = await fetch(`${API}?status=${s}&user_id=${USER_ID}`);
    const data = await res.json();
    if (data.error) return;

    // KPI
    const rows = data.rows;
    $('#kTotal').textContent   = rows.length;
    $('#kPending').textContent = rows.filter(r=>r.status==='pending').length;
    $('#kResolved').textContent= rows.filter(r=>r.status==='resolved').length;

    const list = $('#histList');
    if (!rows.length) {
      list.innerHTML = `<div style="text-align:center;padding:20px;color:var(--muted)">Belum ada laporan.</div>`;
      return;
    }
    list.innerHTML = rows.slice(0,50).map(r=>`
      <div class="report-item">
        <div class="report-head">
          <div class="report-cat">#${r.id} ${esc(r.kategori)}</div>
          <span class="badge s-${r.status}">${r.status}</span>
        </div>
        <div class="report-ring">${esc(r.ringkasan||'—')}</div>
        <div class="report-time">${fmtDate(r.waktu)}</div>
        ${r.catatan_admin ? `<div style="margin-top:5px;font-size:11px;color:#a78bfa">💬 ${esc(r.catatan_admin)}</div>` : ''}
      </div>`).join('');
  } catch(e) { console.warn(e); }
}

$('#histFilter').addEventListener('change', loadHistory);

// ── Init ───────────────────────────────────────────────────────────────────
loadMapMarkers();
loadHistory();
</script>
</body>
</html>
