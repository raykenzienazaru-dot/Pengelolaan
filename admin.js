  const LS_KEY = "air_sanitasi_reports_v2";
  const $ = (q) => document.querySelector(q);

  function loadReports(){
    try { return JSON.parse(localStorage.getItem(LS_KEY) || "[]"); } catch { return []; }
  }
  function saveReports(rows){ localStorage.setItem(LS_KEY, JSON.stringify(rows)); }
  function escapeHtml(s=""){ return s.replace(/[&<>"']/g, c => ({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" }[c])); }
  function fmtDate(iso){ return new Date(iso).toLocaleString("id-ID", { dateStyle:"medium", timeStyle:"short" }); }

  function badgeClass(status){
    return ({
      pending:"badge--pending",
      verified:"badge--verified",
      in_progress:"badge--in_progress",
      resolved:"badge--resolved",
      rejected:"badge--rejected"
    })[status] || "";
  }

  let selectedId = null;

  function renderKpis(rows){
    $("#kPending").textContent = rows.filter(r=>r.status==="pending").length;
    $("#kVerified").textContent = rows.filter(r=>r.status==="verified").length;
    $("#kResolved").textContent = rows.filter(r=>r.status==="resolved").length;
  }

  function getFiltered(){
    const q = ($("#q").value || "").toLowerCase();
    const s = $("#statusFilter").value;
    return loadReports().filter(r=>{
      const okS = (s==="all") ? true : r.status === s;
      const hay = `${r.categoryLabel} ${r.description||""} ${r.adminNote||""}`.toLowerCase();
      const okQ = q ? hay.includes(q) : true;
      return okS && okQ;
    });
  }

  function renderTable(){
    const rows = getFiltered();
    renderKpis(loadReports());

    const tbody = $("#tbody");
    if (!rows.length){
      tbody.innerHTML = `<tr><td colspan="4" class="small">Tidak ada data.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.slice(0, 200).map(r=>`
      <tr data-id="${r.id}">
        <td>${escapeHtml(fmtDate(r.createdAt))}</td>
        <td>${escapeHtml(r.categoryLabel)}</td>
        <td><span class="badge ${badgeClass(r.status)}">${escapeHtml(r.status)}</span></td>
        <td>${escapeHtml((r.description||"").slice(0, 55))}${(r.description||"").length>55?"…":""}</td>
      </tr>
    `).join("");

    tbody.querySelectorAll("tr[data-id]").forEach(tr=>{
      tr.addEventListener("click", ()=> openDetail(tr.dataset.id));
    });
  }

  function showAdminMsg(text){
    const el = $("#adminMsg");
    el.textContent = text;
    el.classList.add("is-show");
    setTimeout(()=> el.classList.remove("is-show"), 3000);
  }

  function openDetail(id){
    const rows = loadReports();
    const r = rows.find(x=>x.id===id);
    if (!r) return;

    selectedId = id;

    $("#detailEmpty").style.display = "none";
    $("#detail").style.display = "block";

    $("#dId").textContent = r.id;
    const ds = $("#dStatus");
    ds.className = `badge ${badgeClass(r.status)}`;
    ds.textContent = r.status;

    $("#dCoord").textContent = `${r.lat.toFixed(6)}, ${r.lng.toFixed(6)}`;
    $("#dDesc").textContent = r.description || "(tidak ada)";

    $("#dPhoto").innerHTML = r.photoDataUrl
      ? `<img src="${r.photoDataUrl}" alt="Foto laporan"><div class="small">Foto tersimpan lokal</div>`
      : `<div class="small">(tidak ada foto)</div>`;

    $("#editStatus").value = r.status;
    $("#editNote").value = r.adminNote || "";
  }

  $("#btnSave").addEventListener("click", ()=>{
    if (!selectedId) return;
    const rows = loadReports();
    const r = rows.find(x=>x.id===selectedId);
    if (!r) return;

    r.status = $("#editStatus").value;
    r.adminNote = $("#editNote").value.trim();

    saveReports(rows);
    renderTable();
    openDetail(selectedId);
    showAdminMsg("Perubahan disimpan. Cek peta di halaman user untuk melihat update.");
  });

  $("#btnDelete").addEventListener("click", ()=>{
    if (!selectedId) return;
    const rows = loadReports().filter(r=>r.id!==selectedId);
    saveReports(rows);
    selectedId = null;
    $("#detail").style.display = "none";
    $("#detailEmpty").style.display = "block";
    renderTable();
    showAdminMsg("Laporan dihapus.");
  });

  $("#q").addEventListener("input", renderTable);
  $("#statusFilter").addEventListener("change", renderTable);

  // Export / Import JSON
  $("#btnExport").addEventListener("click", ()=>{
    const rows = loadReports();
    const blob = new Blob([JSON.stringify(rows, null, 2)], { type:"application/json" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "reports_export.json";
    a.click();
    URL.revokeObjectURL(url);
  });

  $("#importFile").addEventListener("change", async ()=>{
    const file = $("#importFile").files?.[0];
    if (!file) return;
    const text = await file.text();
    const rows = JSON.parse(text);
    if (!Array.isArray(rows)) return alert("Format JSON tidak valid.");
    saveReports(rows);
    renderTable();
    alert("Import selesai.");
  });

  renderTable();
