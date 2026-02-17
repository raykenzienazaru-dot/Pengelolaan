const LS_KEY = "air_sanitasi_reports_v2";

const $ = (q) => document.querySelector(q);
const listEl = $("#list");
const msgEl = $("#msg");
const previewEl = $("#preview");

function uid(){ return Math.random().toString(16).slice(2) + "-" + Date.now().toString(16); }
function escapeHtml(s=""){ return s.replace(/[&<>"']/g, c => ({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" }[c])); }
function fmtDate(iso){ return new Date(iso).toLocaleString("id-ID", { dateStyle:"medium", timeStyle:"short" }); }

function loadReports(){
  try { return JSON.parse(localStorage.getItem(LS_KEY) || "[]"); } catch { return []; }
}
function saveReports(rows){ localStorage.setItem(LS_KEY, JSON.stringify(rows)); }

function showCallout(text){
  msgEl.textContent = text;
  msgEl.classList.add("is-show");
  setTimeout(()=> msgEl.classList.remove("is-show"), 3500);
}

function categoryLabel(v){
  return ({
    air_kotor: "Air kotor/keruh",
    air_tidak_tersedia: "Air tidak tersedia",
    pipa_bocor: "Pipa bocor",
    sanitasi_buruk: "Sanitasi buruk",
    lainnya: "Lainnya",
  })[v] || v;
}
function badgeClass(status){
  return ({
    pending:"badge--pending",
    verified:"badge--verified",
    in_progress:"badge--in_progress",
    resolved:"badge--resolved",
    rejected:"badge--rejected"
  })[status] || "";
}
function statusColor(status){
  return ({
    pending:"#fbbf24",
    verified:"#60a5fa",
    in_progress:"#a78bfa",
    resolved:"#34d399",
    rejected:"#fb7185"
  })[status] || "#94a3b8";
}
function markerIcon(status){
  const color = statusColor(status);
  return L.divIcon({
    className: "custom-pin",
    html: `<div style="width:14px;height:14px;border-radius:999px;background:${color};border:2px solid rgba(255,255,255,.85);box-shadow:0 10px 20px rgba(0,0,0,.35);"></div>`,
    iconSize:[14,14], iconAnchor:[7,7]
  });
}

async function fileToDataUrl(file){
  return new Promise((resolve,reject)=>{
    const r = new FileReader();
    r.onload = ()=> resolve(String(r.result));
    r.onerror = reject;
    r.readAsDataURL(file);
  });
}

// ---------------- MAP ----------------
const map = L.map("map").setView([-2.5, 118], 5);
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 19, attribution:"&copy; OpenStreetMap" }).addTo(map);
const markers = L.layerGroup().addTo(map);

let draftPin = null;
map.on("click", (e) => {
  $("#lat").value = e.latlng.lat.toFixed(6);
  $("#lng").value = e.latlng.lng.toFixed(6);

  if (draftPin) markers.removeLayer(draftPin);
  draftPin = L.marker([e.latlng.lat, e.latlng.lng], { icon: markerIcon("pending") })
    .addTo(markers)
    .bindPopup("Lokasi laporan (draft)")
    .openPopup();
});

function renderMap(){
  const filter = $("#mapFilter").value;
  markers.clearLayers();
  draftPin = null;

  const rows = loadReports();
  rows
    .filter(r => filter === "all" ? true : r.status === filter)
    .forEach(r => {
      const m = L.marker([r.lat, r.lng], { icon: markerIcon(r.status) }).addTo(markers);
      const img = r.photoDataUrl ? `<img src="${r.photoDataUrl}" style="width:210px;border-radius:12px;border:1px solid rgba(255,255,255,.15);margin-top:8px" />` : "";
      const popup = `
        <div style="min-width:220px">
          <div style="font-weight:900;margin-bottom:6px">${escapeHtml(r.categoryLabel)}</div>
          <div style="font-size:12px;opacity:.9">
            <b>Status:</b> ${escapeHtml(r.status)}
            ${r.adminNote ? `<br/><b>Catatan:</b> ${escapeHtml(r.adminNote)}` : ""}
          </div>
          <div style="font-size:12px;opacity:.85;margin-top:6px"><b>Waktu:</b> ${escapeHtml(fmtDate(r.createdAt))}</div>
          ${r.description ? `<div style="margin-top:8px;font-size:13px;line-height:1.45">${escapeHtml(r.description)}</div>` : ""}
          ${img}
        </div>
      `;
      m.bindPopup(popup);
      r.__leafletId = m._leaflet_id;
    });

  saveReports(rows);
}

$("#mapFilter").addEventListener("change", renderMap);

// ---------------- FORM ----------------
$("#photo").addEventListener("change", async () => {
  previewEl.innerHTML = "";
  const file = $("#photo").files?.[0];
  if (!file) return;
  const url = await fileToDataUrl(file);
  previewEl.dataset.url = url;
  previewEl.innerHTML = `<img src="${url}" alt="Preview foto"><div class="small">${escapeHtml(file.name)} • ${(file.size/1024).toFixed(0)} KB</div>`;
});

$("#btnClearPreview").addEventListener("click", () => {
  previewEl.innerHTML = "";
  previewEl.dataset.url = "";
});

$("#btnGPS").addEventListener("click", () => {
  if (!navigator.geolocation) return showCallout("Browser tidak mendukung GPS.");
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      $("#lat").value = pos.coords.latitude.toFixed(6);
      $("#lng").value = pos.coords.longitude.toFixed(6);
      showCallout("GPS berhasil diambil.");
    },
    (err) => showCallout("Gagal ambil GPS: " + err.message),
    { enableHighAccuracy:true, timeout:12000 }
  );
});

$("#btnCenter").addEventListener("click", () => {
  const lat = Number($("#lat").value), lng = Number($("#lng").value);
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return showCallout("Isi lat/lng dulu.");
  map.setView([lat, lng], 14);
});

$("#reportForm").addEventListener("submit", (e) => {
  e.preventDefault();
  const lat = Number($("#lat").value);
  const lng = Number($("#lng").value);
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return showCallout("Latitude/Longitude tidak valid.");

  const cat = $("#category").value;
  const report = {
    id: uid(),
    createdAt: new Date().toISOString(),
    lat, lng,
    category: cat,
    categoryLabel: categoryLabel(cat),
    description: $("#desc").value.trim(),
    photoDataUrl: previewEl.dataset.url || null,
    status: "pending",
    adminNote: ""
  };

  const rows = loadReports();
  rows.unshift(report);
  saveReports(rows);

  renderMap();
  renderList();
  renderKpis();

  showCallout("Laporan tersimpan (offline). Status: pending.");
  e.target.reset();
  previewEl.innerHTML = "";
  previewEl.dataset.url = "";
});

// ---------------- LIST + KPI ----------------
function renderKpis(){
  const rows = loadReports();
  $("#kpiTotal").textContent = rows.length;
  $("#kpiPending").textContent = rows.filter(r => r.status === "pending").length;
  $("#kpiVerified").textContent = rows.filter(r => r.status === "verified").length;
}

function renderList(){
  const rows = loadReports();
  if (!rows.length){
    listEl.innerHTML = `<div class="small">Belum ada laporan. Buat laporan dari form di atas.</div>`;
    return;
  }

  listEl.innerHTML = rows.slice(0, 20).map(r => `
    <div class="item">
      <div class="item__top">
        <div>
          <div class="item__title">${escapeHtml(r.categoryLabel)}</div>
          <div class="item__meta">${escapeHtml(fmtDate(r.createdAt))} • ${r.lat.toFixed(6)}, ${r.lng.toFixed(6)}</div>
        </div>
        <div class="badge ${badgeClass(r.status)}">${escapeHtml(r.status)}</div>
      </div>
      ${r.description ? `<div class="item__desc">${escapeHtml(r.description)}</div>` : ""}
      ${r.adminNote ? `<div class="hint"><b>Catatan admin:</b> ${escapeHtml(r.adminNote)}</div>` : ""}
      <div class="row" style="margin-top:10px">
        <button class="btn btn--ghost" data-focus="${r.id}" type="button">Lihat di Peta</button>
        <button class="btn btn--danger" data-del="${r.id}" type="button">Hapus</button>
      </div>
    </div>
  `).join("");

  listEl.querySelectorAll("[data-focus]").forEach(btn=>{
    btn.addEventListener("click", ()=>{
      const id = btn.dataset.focus;
      const row = loadReports().find(x=>x.id===id);
      if (!row) return;
      map.setView([row.lat, row.lng], 15);
      // open popup
      const layer = markers.getLayers().find(l => l._leaflet_id === row.__leafletId);
      if (layer) layer.openPopup();
    });
  });

  listEl.querySelectorAll("[data-del]").forEach(btn=>{
    btn.addEventListener("click", ()=>{
      const id = btn.dataset.del;
      const rows2 = loadReports().filter(r=>r.id!==id);
      saveReports(rows2);
      renderMap(); renderList(); renderKpis();
    });
  });
}

// ---------------- DEMO + RESET ----------------
$("#btnReset").addEventListener("click", ()=>{
  localStorage.removeItem(LS_KEY);
  renderMap(); renderList(); renderKpis();
  showCallout("Data lokal direset.");
});

$("#btnSeed").addEventListener("click", ()=>{
  const demo = [
    { id:uid(), createdAt:new Date(Date.now()-8*3600e3).toISOString(), lat:-6.2, lng:106.8167, category:"air_kotor", categoryLabel:categoryLabel("air_kotor"), description:"Air sumur keruh dan berbau, beberapa anak diare.", photoDataUrl:null, status:"verified", adminNote:"Perlu cek sumber air." },
    { id:uid(), createdAt:new Date(Date.now()-30*3600e3).toISOString(), lat:-7.7971, lng:110.3705, category:"sanitasi_buruk", categoryLabel:categoryLabel("sanitasi_buruk"), description:"Drainase tersumbat dekat toilet umum.", photoDataUrl:null, status:"in_progress", adminNote:"Koordinasi dinas." },
    { id:uid(), createdAt:new Date(Date.now()-3*3600e3).toISOString(), lat:3.5952, lng:98.6722, category:"air_tidak_tersedia", categoryLabel:categoryLabel("air_tidak_tersedia"), description:"Air tidak mengalir sejak kemarin.", photoDataUrl:null, status:"pending", adminNote:"" }
  ];
  saveReports([...demo, ...loadReports()]);
  renderMap(); renderList(); renderKpis();
  showCallout("Data demo dimuat.");
});

// init
renderMap();
renderList();
renderKpis();
