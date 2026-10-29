/* BBMS - shared front-end helpers (plain JavaScript, no frameworks) */
(function () {
  const BASE = document.currentScript.src.replace(/assets\/js\/app\.js.*$/, '');
  const BBMS = (window.BBMS = { BASE });

  /* ---------- Icons (feather-style inline SVG) ---------- */
  const ICONS = {
    drop: '<path d="M12 2.7s7 7.3 7 12.3a7 7 0 0 1-14 0c0-5 7-12.3 7-12.3z"/>',
    user: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    users: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
    chart: '<path d="M18 20V10M12 20V4M6 20v-6"/>',
    history: '<path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><path d="M12 7v5l4 2"/>',
    calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    building: '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M8 10h.01M16 10h.01M12 10h.01M8 14h.01M16 14h.01M12 14h.01"/>',
    shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
    list: '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    flask: '<path d="M9 2h6M10 2v6L4 20a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2l-6-12V2"/>',
    check: '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="M22 4 12 14l-3-3"/>',
    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
    menu: '<path d="M3 12h18M3 6h18M3 18h18"/>',
    clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    pin: '<path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
    phone: '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
    mail: '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="m22 6-10 7L2 6"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    heart: '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
    zap: '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',
    alert: '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
    x: '<path d="M18 6 6 18M6 6l12 12"/>',
  };
  const icon = (n, cls = '') => `<svg class="icon ${cls}" viewBox="0 0 24 24">${ICONS[n] || ''}</svg>`;
  BBMS.icon = icon;

  /* ---------- Small helpers ---------- */
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fmtDate = (d) => (d ? new Date(String(d).replace(' ', 'T')).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—');
  const fmtDateTime = (d) => (d ? new Date(String(d).replace(' ', 'T')).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—');
  const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
  Object.assign(BBMS, { esc, fmtDate, fmtDateTime, BLOOD_GROUPS });

  BBMS.toast = (msg, type = 'success') => {
    let box = document.getElementById('toasts');
    if (!box) { box = document.createElement('div'); box.id = 'toasts'; document.body.appendChild(box); }
    const t = document.createElement('div');
    t.className = 'toast ' + type; t.textContent = msg; box.appendChild(t);
    setTimeout(() => t.remove(), 3500);
  };

  BBMS.loading = (el, text = 'Loading...') => { el.innerHTML = `<div class="loading"><div class="spinner"></div>${esc(text)}</div>`; };
  BBMS.badge = (text, type = '') => `<span class="badge ${type}">${esc(text)}</span>`;
  BBMS.chip = (g) => `<span class="bg-chip">${esc(g)}</span>`;
  BBMS.statCard = (label, value, ic, color = '') => `<div class="card stat"><div class="ico ${color}">${icon(ic, 'lg')}</div><div><div class="val">${esc(value)}</div><div class="lbl">${esc(label)}</div></div></div>`;
  BBMS.statusBadge = (s) => BBMS.badge(s, { approved: 'green', accepted: 'green', Completed: 'green', Upcoming: 'blue', Ongoing: 'amber', pending: 'amber', rejected: 'red', Cancelled: 'red' }[s] || '');
  BBMS.empty = (text) => `<div class="empty">${esc(text)}</div>`;
  BBMS.table = (headers, rows, emptyText = 'No records found') =>
    rows.length
      ? `<div class="table-wrap"><table><thead><tr>${headers.map((h) => `<th>${h}</th>`).join('')}</tr></thead><tbody>${rows.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`
      : BBMS.empty(emptyText);
  BBMS.pager = (p, fnName) =>
    p && p.totalPages > 1
      ? `<div class="pager"><button class="btn outline sm" ${p.currentPage <= 1 ? 'disabled' : ''} onclick="${fnName}(${p.currentPage - 1})">Previous</button>Page ${p.currentPage} of ${p.totalPages}<button class="btn outline sm" ${p.currentPage >= p.totalPages ? 'disabled' : ''} onclick="${fnName}(${p.currentPage + 1})">Next</button></div>`
      : '';
  BBMS.groupOptions = (sel = '', placeholder = 'Select') =>
    `<option value="">${placeholder}</option>` + BLOOD_GROUPS.map((g) => `<option ${g === sel ? 'selected' : ''}>${g}</option>`).join('');
  BBMS.stockGrid = (rows, qtyKey = 'quantity') => {
    const map = {}; rows.forEach((r) => (map[r.blood_group] = r));
    return `<div class="stock-grid">${BLOOD_GROUPS.map((g) => { const q = Number(map[g]?.[qtyKey] || 0); return `<div class="stock-box ${q < 5 ? 'low' : ''}"><div class="g">${g}</div><div class="n">${q}</div><div class="small muted">units${map[g] ? ' · exp ' + fmtDate(map[g].expiry_date) : ''}</div></div>`; }).join('')}</div>`;
  };
  BBMS.historyList = (rows) =>
    rows.length ? `<ul class="timeline">${rows.map((h) => `<li><span>${BBMS.badge(h.event_type)} ${esc(h.description)}</span><span class="muted small" style="white-space:nowrap">${fmtDateTime(h.created_at)}</span></li>`).join('')}</ul>` : BBMS.empty('No activity yet');

  BBMS.modal = (title, bodyHtml, { onSubmit, submitText = 'Save' } = {}) => {
    const bg = document.createElement('div');
    bg.className = 'modal-bg';
    bg.innerHTML = `<form class="modal"><h2>${esc(title)}</h2>${bodyHtml}<div class="actions"><button type="button" class="btn outline" data-x>Cancel</button>${onSubmit ? `<button class="btn">${esc(submitText)}</button>` : ''}</div></form>`;
    document.body.appendChild(bg);
    const close = () => bg.remove();
    bg.querySelector('[data-x]').onclick = close;
    bg.addEventListener('mousedown', (e) => { if (e.target === bg) close(); });
    bg.querySelector('form').onsubmit = async (e) => {
      e.preventDefault();
      if (!onSubmit) return close();
      const btn = bg.querySelector('.actions .btn:not(.outline)'); btn.disabled = true;
      try { if ((await onSubmit(Object.fromEntries(new FormData(e.target)), e.target)) !== false) close(); }
      finally { btn.disabled = false; }
    };
    return { close, el: bg };
  };

  /* ---------- API ---------- */
  // BBMS.api('donor', 'profile')  |  BBMS.api('lab', 'stock_add', { body: {...} })
  BBMS.api = async (file, action, { params = {}, body = null } = {}) => {
    const qs = new URLSearchParams({ action, ...params }).toString();
    const opt = { credentials: 'same-origin' };
    if (body) { opt.method = 'POST'; opt.headers = { 'Content-Type': 'application/json' }; opt.body = JSON.stringify(body); }
    let res, data;
    try { res = await fetch(`${BASE}api/${file}.php?${qs}`, opt); data = await res.json(); }
    catch (e) { throw new Error('Server se connection nahi hua. XAMPP mein Apache aur MySQL start hain?'); }
    if (res.status === 401 && document.body.dataset.role) { location.href = BASE + 'login.html'; throw new Error('Session expired'); }
    if (!res.ok || data.success === false) throw new Error(data.message || 'Request failed');
    return data;
  };
  // Button handler: error toast automatically
  BBMS.run = async (fn) => { try { return await fn(); } catch (e) { BBMS.toast(e.message, 'error'); } };

  /* ---------- Public header / footer ---------- */
  function publicShell() {
    const h = document.getElementById('site-header'), f = document.getElementById('site-footer');
    if (h) h.innerHTML = `<nav class="site-nav"><div class="container">
      <a href="${BASE}index.html" class="row"><div class="logo-mark">${icon('drop', 'lg')}</div><b style="font-size:1.1rem">BBMS</b></a>
      <div class="links"><a href="${BASE}index.html">Home</a><a href="${BASE}about.html">About</a><a href="${BASE}contact.html">Contact</a></div>
      <div class="actions"><a class="btn outline sm" href="${BASE}login.html">Login</a><a class="btn sm" href="${BASE}register-donor.html">Become a Donor</a></div></div></nav>`;
    if (f) f.innerHTML = `<footer class="site-footer"><div class="container"><div><b style="color:#fff">BBMS</b> — Blood Bank Management System<br>Saving lives through technology.</div>
      <div class="row" style="gap:1.2rem"><a href="${BASE}about.html">About</a><a href="${BASE}contact.html">Contact</a><a href="${BASE}register-facility.html">Register Facility</a></div></div></footer>`;
  }

  /* ---------- Dashboard shell ---------- */
  const MENUS = {
    donor: { title: 'Blood Donor Portal', sub: 'Be a Hero, Save Lives', items: [['donor/index.html', 'Dashboard', 'chart'], ['donor/profile.html', 'My Profile', 'user'], ['donor/history.html', 'Donation History', 'history'], ['donor/camps.html', 'Blood Camps', 'calendar']] },
    hospital: { title: 'Hospital Management', sub: 'Blood Request & Inventory', items: [['hospital/index.html', 'Dashboard', 'chart'], ['hospital/request.html', 'Request Blood', 'list'], ['hospital/inventory.html', 'Inventory', 'drop'], ['hospital/donors.html', 'Donors', 'users'], ['hospital/history.html', 'History', 'history'], ['hospital/profile.html', 'Profile', 'check']] },
    'blood-lab': { title: 'Blood Lab Center', sub: 'Testing & Quality Control', items: [['lab/index.html', 'Dashboard', 'chart'], ['lab/inventory.html', 'Inventory', 'drop'], ['lab/donors.html', 'Donors', 'users'], ['lab/camps.html', 'Camps', 'calendar'], ['lab/requests.html', 'Requests', 'list'], ['lab/profile.html', 'Profile', 'check']] },
    admin: { title: 'BBMS Admin Panel', sub: 'System Administration', items: [['admin/index.html', 'Overview', 'chart'], ['admin/verification.html', 'Verification', 'shield'], ['admin/facilities.html', 'Facilities', 'building'], ['admin/donors.html', 'Donors', 'users']] },
  };

  // BBMS.init(callback) — body par data-role="donor|hospital|blood-lab|admin" aur data-title="Page title"
  BBMS.init = async (cb) => {
    const role = document.body.dataset.role;
    const root = document.getElementById('app');
    let user;
    try { user = (await BBMS.api('auth', 'me')).user; } catch { location.href = BASE + 'login.html'; return; }
    if (user.role !== role) { location.href = BASE + 'login.html'; return; }

    const menu = MENUS[role];
    const here = location.pathname.split('/').slice(-2).join('/');
    root.innerHTML = `<div class="shell">
      <aside class="sidebar" id="sidebar">
        <div class="brand"><div class="logo-mark">${icon('drop', 'lg')}</div><div><div class="t">${menu.title}</div><div class="s">${menu.sub}</div></div></div>
        <nav class="nav">${menu.items.map(([p, l, i]) => `<a href="${BASE}${p}" class="${p === here ? 'active' : ''}">${icon(i)}${l}</a>`).join('')}</nav>
        <div class="side-foot"><button class="btn outline block" id="logoutBtn">${icon('logout')} Logout</button></div>
      </aside><div class="overlay" id="overlay"></div>
      <div class="main"><header class="topbar"><div class="row"><button class="menu-btn" id="menuBtn">${icon('menu', 'lg')}</button><b>${esc(document.body.dataset.title || '')}</b></div>
        <div class="who"><b>${esc(user.name)}</b><span>${esc(role.replace('-', ' '))}</span></div></header>
        <main class="content" id="content"></main></div></div>`;
    const sb = document.getElementById('sidebar');
    document.getElementById('menuBtn').onclick = () => sb.classList.toggle('open');
    document.getElementById('overlay').onclick = () => sb.classList.remove('open');
    document.getElementById('logoutBtn').onclick = async () => { await BBMS.api('auth', 'logout').catch(() => {}); location.href = BASE + 'login.html'; };

    const content = document.getElementById('content');
    BBMS.loading(content);
    try { await cb(user, content); } catch (e) { content.innerHTML = `<div class="err-box">${esc(e.message)}</div>`; }
  };

  /* ---------- Facility profile (hospital + lab share this) ---------- */
  BBMS.facilityProfile = async (el) => {
    const DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const f = (await BBMS.api('facility', 'profile')).facility;
    el.innerHTML = `<div class="page-head"><div><h1>Profile</h1><p>Manage your facility information</p></div>${BBMS.statusBadge(f.status)}</div>
    <div class="grid g2"><form class="card" id="pf"><h3>Facility Details</h3>
      <div class="form-grid">
        <div class="field"><label>Name</label><input name="name" value="${esc(f.name)}" required></div>
        <div class="field"><label>Email</label><input value="${esc(f.email)}" disabled></div>
        <div class="field"><label>Phone</label><input name="phone" value="${esc(f.phone)}" required></div>
        <div class="field"><label>Emergency Contact</label><input name="emergencyContact" value="${esc(f.emergencyContact)}"></div>
        <div class="field"><label>Street</label><input name="street" value="${esc(f.address.street)}"></div>
        <div class="field"><label>City</label><input name="city" value="${esc(f.address.city)}"></div>
        <div class="field"><label>State</label><input name="state" value="${esc(f.address.state)}"></div>
        <div class="field"><label>Postal Code</label><input name="pincode" value="${esc(f.address.pincode)}"></div>
        <div class="field"><label>Opens</label><input type="time" name="open" value="${esc(f.operatingHours.open)}"></div>
        <div class="field"><label>Closes</label><input type="time" name="close" value="${esc(f.operatingHours.close)}"></div>
      </div>
      <div class="field"><label>Working Days</label><div class="checks">${DAYS.map((d) => `<label><input type="checkbox" name="days" value="${d}" ${f.operatingHours.workingDays.includes(d) ? 'checked' : ''}>${d}</label>`).join('')}</div></div>
      <div class="checks mb"><label><input type="checkbox" name="is24x7" ${f.is24x7 ? 'checked' : ''}>Open 24x7</label><label><input type="checkbox" name="emergencyServices" ${f.emergencyServices ? 'checked' : ''}>Emergency services</label></div>
      <div class="field"><label>New Password (optional)</label><input type="password" name="password" minlength="6" placeholder="Leave blank to keep current"></div>
      <button class="btn">Save Changes</button></form>
    <div class="card"><h3>Registration</h3><dl class="kv">
      <dt>Type</dt><dd>${esc(f.facilityType)}</dd><dt>Category</dt><dd>${esc(f.facilityCategory)}</dd>
      <dt>Reg. Number</dt><dd>${esc(f.registrationNumber)}</dd><dt>Status</dt><dd>${BBMS.statusBadge(f.status)}</dd>
      <dt>Member since</dt><dd>${fmtDate(f.createdAt)}</dd><dt>Last login</dt><dd>${fmtDateTime(f.lastLogin)}</dd></dl></div></div>`;
    document.getElementById('pf').onsubmit = (e) => {
      e.preventDefault();
      BBMS.run(async () => {
        const fd = new FormData(e.target);
        const body = { name: fd.get('name'), phone: fd.get('phone'), emergencyContact: fd.get('emergencyContact'),
          address: { street: fd.get('street'), city: fd.get('city'), state: fd.get('state'), pincode: fd.get('pincode') },
          operatingHours: { open: fd.get('open'), close: fd.get('close'), workingDays: fd.getAll('days') },
          is24x7: fd.has('is24x7'), emergencyServices: fd.has('emergencyServices'), password: fd.get('password') };
        const r = await BBMS.api('facility', 'update_profile', { body });
        BBMS.toast(r.message); e.target.password.value = '';
      });
    };
  };

  document.addEventListener('DOMContentLoaded', () => { if (!document.body.dataset.role) publicShell(); });
})();
