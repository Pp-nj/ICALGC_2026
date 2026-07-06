/**
 * ICALGC 2026 — Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

  // ── Back to Top ────────────────────────────────────────
  const backBtn = document.getElementById('backToTop');
  if (backBtn) {
    window.addEventListener('scroll', () => {
      backBtn.classList.toggle('show', window.scrollY > 400);
    });
    backBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // ── Navbar scroll effect ───────────────────────────────
  const navbar = document.querySelector('.navbar-main');
  if (navbar) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 80) {
        navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,.25)';
      } else {
        navbar.style.boxShadow = '';
      }
    });
  }

  // ── Auto-dismiss alerts ────────────────────────────────
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(el => {
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transition = 'opacity .5s';
      setTimeout(() => el.remove(), 500);
    }, parseInt(el.dataset.autoDismiss) || 5000);
  });

  // ── Score slider live display ──────────────────────────
  document.querySelectorAll('.score-slider').forEach(slider => {
    const display = document.getElementById('display_' + slider.id);
    if (display) {
      const update = () => { display.textContent = slider.value; };
      slider.addEventListener('input', update);
      update();
    }
  });

  // ── Confirmation dialogs ───────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm)) e.preventDefault();
    });
  });

  // ── Active sidebar link ────────────────────────────────
  const currentPath = window.location.pathname;
  document.querySelectorAll('.sidebar-link').forEach(link => {
    if (link.getAttribute('href') === currentPath) {
      link.classList.add('active');
    }
  });

  // ── File upload drag-and-drop styling ─────────────────
  const fileInputs = document.querySelectorAll('input[type="file"].file-drop-zone');
  fileInputs.forEach(input => {
    const wrapper = input.closest('.file-drop-wrapper');
    if (!wrapper) return;
    ['dragenter', 'dragover'].forEach(event => {
      wrapper.addEventListener(event, e => {
        e.preventDefault();
        wrapper.classList.add('drag-over');
      });
    });
    ['dragleave', 'drop'].forEach(event => {
      wrapper.addEventListener(event, e => {
        e.preventDefault();
        wrapper.classList.remove('drag-over');
        if (event === 'drop') {
          input.files = e.dataTransfer.files;
          updateFileLabel(input);
        }
      });
    });
    input.addEventListener('change', () => updateFileLabel(input));
  });

  function updateFileLabel(input) {
    const label = input.closest('.file-drop-wrapper')?.querySelector('.file-label');
    if (label && input.files.length > 0) {
      const size = (input.files[0].size / 1048576).toFixed(2);
      label.textContent = input.files[0].name + ' (' + size + ' MB)';
      label.style.color = 'var(--blue-dark)';
      label.style.fontWeight = '600';
    }
  }

  // ── Co-author dynamic rows ─────────────────────────────
  const addCoAuthorBtn = document.getElementById('addCoAuthor');
  const coAuthorList   = document.getElementById('coAuthorList');
  if (addCoAuthorBtn && coAuthorList) {
    let idx = coAuthorList.querySelectorAll('.co-author-row').length;
    addCoAuthorBtn.addEventListener('click', () => {
      const row = document.createElement('div');
      row.className = 'co-author-row border rounded p-3 mb-3 position-relative';
      row.innerHTML = coAuthorRowHtml(idx++);
      coAuthorList.appendChild(row);
      row.querySelector('.remove-co-author')?.addEventListener('click', () => row.remove());
    });
    coAuthorList.querySelectorAll('.remove-co-author').forEach(btn => {
      btn.addEventListener('click', () => btn.closest('.co-author-row').remove());
    });
  }

  function coAuthorRowHtml(i) {
    const isTh = (window.APP_LANG === 'th');
    const t = {
      firstName:     isTh ? 'ชื่อ'                : 'First Name',
      middleName:    isTh ? 'ชื่อกลาง(ถ้ามี)'             : 'Middle Name (optional)',
      lastName:      isTh ? 'นามสกุล'              : 'Last Name',
      email:         isTh ? 'อีเมล'               : 'Email',
      phone:         isTh ? 'เบอร์ติดต่อ'          : 'Phone',
      institution:   isTh ? 'สถาบัน'              : 'Institution',
      country:       isTh ? 'ประเทศ'              : 'Country',
      corresponding: isTh ? 'ผู้ประสานงานหลัก'      : 'Corresponding Author',
    };
    return `
      <button type="button" class="btn btn-sm btn-outline-danger remove-co-author position-absolute top-0 end-0 m-2">
        <i class="fas fa-times"></i>
      </button>
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label">${t.firstName}</label>
          <input type="text" name="co_first_name[]" class="form-control" placeholder="${t.firstName}" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">${t.middleName}</label>
          <input type="text" name="co_middle_name[]" class="form-control" placeholder="${t.middleName}">
        </div>
        <div class="col-md-4">
          <label class="form-label">${t.lastName}</label>
          <input type="text" name="co_last_name[]" class="form-control" placeholder="${t.lastName}" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">${t.email}</label>
          <input type="email" name="co_email[]" class="form-control" placeholder="email@domain.com">
        </div>
        <div class="col-md-4">
          <label class="form-label">${t.phone}</label>
          <input type="tel" name="co_phone[]" class="form-control" placeholder="${t.phone}">
        </div>
        <div class="col-md-4">
          <label class="form-label">${t.institution}</label>
          <input type="text" name="co_institution[]" class="form-control" placeholder="${t.institution}">
        </div>
        <div class="col-md-4">
          <label class="form-label">${t.country}</label>
          <input type="text" name="co_country[]" class="form-control" placeholder="${t.country}">
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="co_corresponding[]" value="${i}">
            <label class="form-check-label">${t.corresponding}</label>
          </div>
        </div>
      </div>`;
  }

  // ── Tooltips ───────────────────────────────────────────
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  // ── CFA Table of Contents spy ──────────────────────────
  const tocLinks = document.querySelectorAll('.cfa-toc a[href^="#"]');
  if (tocLinks.length) {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const id = entry.target.id;
          tocLinks.forEach(a => {
            a.classList.toggle('active', a.getAttribute('href') === '#' + id);
          });
        }
      });
    }, { rootMargin: '-20% 0px -70% 0px' });

    document.querySelectorAll('.cfa-section[id]').forEach(el => observer.observe(el));
  }

});

// ── Countdown Timer ────────────────────────────────────────
function initCountdown(targetDateStr) {
  const target = new Date(targetDateStr + 'T08:30:00+07:00').getTime();

  function update() {
    const now  = Date.now();
    const diff = target - now;

    if (diff <= 0) {
      ['cd-days','cd-hours','cd-mins','cd-secs'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = '00';
      });
      return;
    }

    const d = Math.floor(diff / 86400000);
    const h = Math.floor((diff % 86400000) / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);

    const pad = n => String(n).padStart(2, '0');

    const days  = document.getElementById('cd-days');
    const hours = document.getElementById('cd-hours');
    const mins  = document.getElementById('cd-mins');
    const secs  = document.getElementById('cd-secs');

    if (days)  days.textContent  = String(d);
    if (hours) hours.textContent = pad(h);
    if (mins)  mins.textContent  = pad(m);
    if (secs)  secs.textContent  = pad(s);
  }

  update();
  setInterval(update, 1000);
}

// ── Notification read ──────────────────────────────────────
function markNotifRead(notifId) {
  fetch('/api/notifications.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'mark_read', id: notifId })
  }).catch(() => {});
}

function markAllNotifRead() {
  fetch('/api/notifications.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'mark_all_read' })
  }).then(() => {
    document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
    const badge = document.querySelector('.notification-badge');
    if (badge) badge.remove();
  }).catch(() => {});
}

