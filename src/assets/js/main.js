/* ============================================================
   Ultimate Dashboard - main.js
   Particles, modals, AJAX API, drag-drop, toasts
   ============================================================ */

'use strict';

/* ── Particle canvas ─────────────────────────────────────── */
function initParticles(canvasId) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  const COLOR    = '0, 212, 255';
  const DIST     = 160;
  const SPEED    = 0.35;
  let particles  = [];
  let raf;

  function resize() {
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
    buildParticles();
  }

  function buildParticles() {
    const count = Math.round((canvas.width * canvas.height) / 9000);
    particles = Array.from({ length: count }, () => ({
      x:  Math.random() * canvas.width,
      y:  Math.random() * canvas.height,
      vx: (Math.random() - 0.5) * SPEED,
      vy: (Math.random() - 0.5) * SPEED,
      r:  Math.random() * 2 + 1,
      a:  Math.random() * 0.4 + 0.2,
    }));
  }

  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    for (let i = 0; i < particles.length; i++) {
      const p = particles[i];

      // Move
      p.x += p.vx;
      p.y += p.vy;
      if (p.x < 0 || p.x > canvas.width)  p.vx *= -1;
      if (p.y < 0 || p.y > canvas.height) p.vy *= -1;

      // Draw dot
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(${COLOR}, ${p.a})`;
      ctx.fill();

      // Draw connections
      for (let j = i + 1; j < particles.length; j++) {
        const q = particles[j];
        const dx = p.x - q.x;
        const dy = p.y - q.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < DIST) {
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(q.x, q.y);
          ctx.strokeStyle = `rgba(${COLOR}, ${(1 - dist / DIST) * 0.35})`;
          ctx.lineWidth = 0.8;
          ctx.stroke();
        }
      }
    }
    raf = requestAnimationFrame(draw);
  }

  resize();
  draw();

  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(resize, 200);
  });
}

/* ── Toast notifications ─────────────────────────────────── */
function showToast(message, type = 'info', duration = 3500) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const icons = {
    success: '<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
    error:   '<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
    info:    '<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
  };

  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = (icons[type] || icons.info) + `<span class="toast-msg">${message}</span>`;
  container.appendChild(el);

  setTimeout(() => {
    el.classList.add('removing');
    el.addEventListener('animationend', () => el.remove(), { once: true });
  }, duration);
}

/* ── Modal helpers ───────────────────────────────────────── */
function openModal(id) {
  const backdrop = document.getElementById(id + 'Backdrop');
  const modal    = document.getElementById(id);
  if (!backdrop || !modal) return;
  backdrop.hidden = false;
  backdrop.addEventListener('click', e => { if (e.target === backdrop) closeModal(id); }, { once: true });
  // Focus first input
  setTimeout(() => { const inp = modal.querySelector('input:not([type=hidden]),select'); if (inp) inp.focus(); }, 50);
}

function closeModal(id) {
  const backdrop = document.getElementById(id + 'Backdrop');
  if (backdrop) backdrop.hidden = true;
}

// Close modals on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-backdrop:not([hidden])').forEach(b => {
      b.hidden = true;
    });
  }
});

/* ── API helper ──────────────────────────────────────────── */
function api(action, data) {
  return fetch('/api?action=' + action, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF || '' },
    body:    JSON.stringify({ ...data, csrf: window.CSRF }),
  }).then(r => r.json());
}

/* ── Navbar: user dropdown ───────────────────────────────── */
(function () {
  const btn      = document.getElementById('userMenuBtn');
  const dropdown = document.getElementById('userDropdown');
  if (!btn || !dropdown) return;

  btn.addEventListener('click', e => {
    e.stopPropagation();
    const open = dropdown.classList.toggle('open');
    btn.setAttribute('aria-expanded', String(open));
  });

  document.addEventListener('click', () => {
    dropdown.classList.remove('open');
    btn.setAttribute('aria-expanded', 'false');
  });
})();

/* ── Hamburger menu ──────────────────────────────────────── */
(function () {
  const btn   = document.getElementById('hamburger');
  const links = document.getElementById('nav-links');
  if (!btn || !links) return;
  btn.addEventListener('click', () => links.classList.toggle('mobile-open'));
})();

/* ── Edit mode ───────────────────────────────────────────── */
let _editMode = false;

function toggleEditMode(force) {
  _editMode = typeof force === 'boolean' ? force : !_editMode;

  const btn    = document.getElementById('editToggle');
  const banner = document.getElementById('editBanner');

  if (btn)    btn.classList.toggle('active', _editMode);
  if (btn)    btn.querySelector('span').textContent = _editMode ? 'Done' : 'Edit';
  if (banner) banner.hidden = !_editMode;

  // Show/hide all edit-only elements
  document.querySelectorAll('.edit-only').forEach(el => el.hidden = !_editMode);

  // Enable/disable tile links during edit mode
  document.querySelectorAll('.tile-link').forEach(a => {
    a.style.pointerEvents = _editMode ? 'none' : '';
  });

  // Show/hide edit overlays
  document.querySelectorAll('.tile-edit-overlay').forEach(el => el.hidden = !_editMode);

  // Enable/disable dragging
  document.querySelectorAll('.tile-card').forEach(el => {
    el.draggable = _editMode;
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('editToggle');
  if (btn) btn.addEventListener('click', () => toggleEditMode());
  // Hide all edit-only on load
  document.querySelectorAll('.edit-only').forEach(el => el.hidden = true);
});

/* ── Tile modal ──────────────────────────────────────────── */
function openTileModal(tileId, groupId, tileJson) {
  // Reset form
  document.getElementById('tileTileId').value     = tileId  || '';
  document.getElementById('tileGroupId').value    = groupId || '';
  document.getElementById('tileName').value       = '';
  document.getElementById('tileUrl').value        = '';
  document.getElementById('tileDescription').value= '';
  document.getElementById('tileLogoPath').value   = '';
  document.getElementById('tileColor').value      = '#00d4ff';
  document.getElementById('tileColorText').value  = '#00d4ff';
  document.getElementById('tileNewTab').checked   = true;

  // Reset logo preview
  const preview = document.getElementById('logoPreview');
  preview.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';

  document.getElementById('tileModalTitle').textContent = tileId ? 'Edit Tile' : 'Add Tile';

  // Populate group select
  const gsel = document.getElementById('tileGroupSelect');
  if (groupId && gsel) {
    gsel.value = groupId;
  }

  // Populate from existing tile data
  if (tileJson) {
    try {
      const t = typeof tileJson === 'string' ? JSON.parse(tileJson) : tileJson;
      document.getElementById('tileName').value        = t.name        || '';
      document.getElementById('tileUrl').value         = t.url         || '';
      document.getElementById('tileDescription').value = t.description || '';
      document.getElementById('tileColor').value       = t.color       || '#00d4ff';
      document.getElementById('tileColorText').value   = t.color       || '#00d4ff';
      document.getElementById('tileNewTab').checked    = t.open_new_tab !== false;
      if (gsel) gsel.value = t.group_id || groupId || '';

      if (t.logo_path) {
        document.getElementById('tileLogoPath').value = t.logo_path;
        preview.innerHTML = `<img src="${t.logo_path}" alt="Logo" style="width:100%;height:100%;object-fit:contain;padding:8px">`;
      }
    } catch (e) { /* ignore */ }
  }

  // Reset icon tab to Upload
  switchIconTab('upload');

  openModal('tileModal');
}

function saveTile() {
  const id    = +document.getElementById('tileTileId').value || null;
  const name  = document.getElementById('tileName').value.trim();
  const url   = document.getElementById('tileUrl').value.trim();
  if (!name || !url) { showToast('Name and URL are required','error'); return; }

  const data = {
    name,
    url,
    description: document.getElementById('tileDescription').value.trim(),
    logo_path:   document.getElementById('tileLogoPath').value,
    color:       document.getElementById('tileColorText').value || '#00d4ff',
    open_new_tab:document.getElementById('tileNewTab').checked,
    group_id:    +document.getElementById('tileGroupSelect').value,
  };

  const action = id ? 'edit_tile' : 'add_tile';
  if (id) data.id = id;

  api(action, data).then(r => {
    if (r.ok) {
      closeModal('tileModal');
      showToast(id ? 'Tile updated' : 'Tile added', 'success');
      setTimeout(() => location.reload(), 600);
    } else {
      showToast(r.error || 'Error saving tile', 'error');
    }
  }).catch(err => showToast('Network error: ' + err.message, 'error'));
}

function deleteTile(id, name) {
  if (!confirm(`Delete tile "${name}"?`)) return;
  api('delete_tile', { id }).then(r => {
    if (r.ok) {
      const el = document.querySelector(`.tile-card[data-tile-id="${id}"]`);
      if (el) el.remove();
      showToast('Tile deleted', 'success');
    } else {
      showToast(r.error || 'Error', 'error');
    }
  });
}

/* ── Group modal ─────────────────────────────────────────── */
function openGroupModal(groupId, name, icon) {
  document.getElementById('groupGroupId').value = groupId || '';
  document.getElementById('groupName').value    = name    || '';
  document.getElementById('groupIcon').value    = icon    || 'grid';
  document.getElementById('groupModalTitle').textContent = groupId ? 'Edit Group' : 'Add Group';

  // Update icon picker selection
  document.querySelectorAll('.icon-pick-btn').forEach(btn => {
    btn.classList.toggle('selected', btn.dataset.icon === (icon || 'grid'));
  });

  openModal('groupModal');
}

function selectIcon(key) {
  document.getElementById('groupIcon').value = key;
  document.querySelectorAll('.icon-pick-btn').forEach(btn => {
    btn.classList.toggle('selected', btn.dataset.icon === key);
  });
}

function saveGroup() {
  const id   = +document.getElementById('groupGroupId').value || null;
  const name = document.getElementById('groupName').value.trim();
  const icon = document.getElementById('groupIcon').value || 'grid';
  if (!name) { showToast('Group name is required', 'error'); return; }

  const action = id ? 'edit_group' : 'add_group';
  const data   = id ? { id, name, icon } : { name, icon };

  api(action, data).then(r => {
    if (r.ok) {
      closeModal('groupModal');
      showToast(id ? 'Group updated' : 'Group added', 'success');
      setTimeout(() => location.reload(), 600);
    } else {
      showToast(r.error || 'Error saving group', 'error');
    }
  }).catch(err => showToast('Network error: ' + err.message, 'error'));
}

function deleteGroup(id, name) {
  if (!confirm(`Delete group "${name}" and all its tiles?`)) return;
  api('delete_group', { id }).then(r => {
    if (r.ok) {
      const el = document.querySelector(`.group-section[data-group-id="${id}"]`);
      if (el) el.remove();
      showToast('Group deleted', 'success');
    } else {
      showToast(r.error || 'Error', 'error');
    }
  });
}

/* ── Color picker sync ───────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  const colorPicker = document.getElementById('tileColor');
  const colorText   = document.getElementById('tileColorText');
  if (colorPicker && colorText) {
    colorPicker.addEventListener('input', () => colorText.value = colorPicker.value);
    colorText.addEventListener('input', () => {
      if (/^#[0-9a-fA-F]{6}$/.test(colorText.value)) colorPicker.value = colorText.value;
    });
  }
});

/* ── Logo upload ─────────────────────────────────────────── */
function previewLogo(input) {
  if (!input.files[0]) return;
  const file = input.files[0];

  // Preview immediately with FileReader
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('logoPreview').innerHTML =
      `<img src="${e.target.result}" alt="Logo" style="width:100%;height:100%;object-fit:contain;padding:8px">`;
  };
  reader.readAsDataURL(file);

  // Upload to server
  const fd = new FormData();
  fd.append('logo', file);
  fd.append('csrf', window.CSRF || '');

  fetch('/api?action=upload_logo', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(r => {
      if (r.ok) {
        document.getElementById('tileLogoPath').value = r.path;
      } else {
        showToast(r.error || 'Upload failed', 'error');
      }
    })
    .catch(() => showToast('Upload error', 'error'));
}

/* ── Icon Library ────────────────────────────────────────── */
let _allIcons = null;
let _iconsLoaded = false;

function switchIconTab(tab) {
  document.querySelectorAll('.icon-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
  document.getElementById('iconTabUpload').hidden  = (tab !== 'upload');
  document.getElementById('iconTabLibrary').hidden = (tab !== 'library');
  if (tab === 'library' && !_iconsLoaded) loadIconLibrary();
}

function loadIconLibrary() {
  _iconsLoaded = true;
  fetch('/api?action=list_icons', {
    headers: { 'X-CSRF-Token': window.CSRF || '' }
  })
  .then(r => r.json())
  .then(r => {
    if (!r.ok) { showToast('Could not load icon library', 'error'); return; }
    _allIcons = r.icons;
    buildCategoryFilter(r.icons);
    renderIconGrid(r.icons);
  })
  .catch(() => showToast('Icon library unavailable', 'error'));
}

function buildCategoryFilter(icons) {
  const cats = [...new Set(icons.map(ic => ic.category))].sort();
  const sel = document.getElementById('iconCategoryFilter');
  if (!sel) return;
  cats.forEach(cat => {
    const opt = document.createElement('option');
    opt.value = cat;
    opt.textContent = cat.charAt(0).toUpperCase() + cat.slice(1);
    sel.appendChild(opt);
  });
}

function renderIconGrid(icons) {
  const grid = document.getElementById('iconGrid');
  const count = document.getElementById('iconCount');
  if (!grid) return;
  if (icons.length === 0) {
    grid.innerHTML = '<div class="icon-grid-empty">No icons found</div>';
    if (count) count.textContent = '';
    return;
  }
  if (count) count.textContent = icons.length + ' icons';

  const currentPath = document.getElementById('tileLogoPath').value;
  grid.innerHTML = icons.map(ic => {
    const src = '/app-icons/icons/' + ic.file;
    const selected = currentPath === src ? ' selected' : '';
    const bg = '#' + ic.color + '22';
    const border = selected ? '; outline: 2px solid #00d4ff' : '';
    return `<button type="button" class="icon-item${selected}" title="${ic.name}"
              style="background:${bg}${border}"
              onclick="selectRepoIcon('${src}','${ic.name}','${ic.color}')">
              <img src="${src}" alt="${ic.name}" loading="lazy">
              <span>${ic.name}</span>
            </button>`;
  }).join('');
}

function filterIcons(q) {
  if (!_allIcons) return;
  const cat = document.getElementById('iconCategoryFilter').value;
  const term = q.toLowerCase().trim();
  const filtered = _allIcons.filter(ic =>
    (!cat || ic.category === cat) &&
    (!term || ic.name.toLowerCase().includes(term) || ic.slug.includes(term))
  );
  renderIconGrid(filtered);
}

function selectRepoIcon(path, name, color) {
  document.getElementById('tileLogoPath').value = path;
  // Update upload-tab preview too
  const preview = document.getElementById('logoPreview');
  if (preview) {
    preview.innerHTML = `<img src="${path}" alt="${name}" style="width:100%;height:100%;object-fit:contain;padding:8px;filter:brightness(0) invert(1)">`;
  }
  // Highlight selected item
  document.querySelectorAll('.icon-item').forEach(el => {
    const isThis = el.getAttribute('onclick').includes(path);
    el.classList.toggle('selected', isThis);
    el.style.outline = isThis ? '2px solid #00d4ff' : '';
  });
  showToast(name + ' selected', 'success');
}

function downloadLatestIcons() {
  showToast('Downloading latest icons from GitHub...', 'info');
  fetch('/api?action=download_icons', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF || '' },
    body: JSON.stringify({ csrf: window.CSRF })
  })
  .then(r => r.json())
  .then(r => {
    if (r.ok) {
      const msg = r.downloaded > 0
        ? 'Downloaded ' + r.downloaded + ' new icons (' + r.total + ' total)'
        : 'Already up to date (' + r.total + ' icons)';
      showToast(msg, 'success');
      _iconsLoaded = false;
      _allIcons = null;
      loadIconLibrary();
    } else {
      showToast(r.error || 'Download failed - check that Icons-Repo is published to GitHub', 'error');
    }
  })
  .catch(() => showToast('Download failed', 'error'));
}

/* ── Favicon auto-fetch ──────────────────────────────────── */
function fetchFavicon() {
  const url = document.getElementById('tileUrl').value.trim();
  if (!url) { showToast('Enter a URL first', 'info'); return; }

  fetch('/api?action=get_favicon&url=' + encodeURIComponent(url))
    .then(r => r.json())
    .then(r => {
      if (r.ok) {
        // Use Google favicon service (more reliable)
        document.getElementById('tileLogoPath').value = r.google_favicon;
        document.getElementById('logoPreview').innerHTML =
          `<img src="${r.google_favicon}" alt="Favicon" style="width:100%;height:100%;object-fit:contain;padding:8px"
                onerror="this.parentElement.innerHTML='<span style=color:var(--text-3)>Not found</span>'">`;
        showToast('Icon fetched!', 'success');
      }
    })
    .catch(() => showToast('Could not fetch favicon', 'error'));
}

/* ── Logo upload area: click-to-open ─────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  const area = document.getElementById('logoUploadArea');
  if (area) {
    area.addEventListener('click', e => {
      if (e.target === area || e.target.closest('.logo-preview, .logo-upload-text:not(input)')) {
        area.querySelector('input[type=file]')?.click();
      }
    });
  }
});

/* ── Drag & drop tile reorder ─────────────────────────────── */
let dragSrc = null;

document.addEventListener('dragstart', e => {
  const tile = e.target.closest('.tile-card');
  if (!tile || !_editMode) { e.preventDefault(); return; }
  dragSrc = tile;
  tile.classList.add('dragging');
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', tile.dataset.tileId);
});

document.addEventListener('dragend', e => {
  const tile = e.target.closest('.tile-card');
  if (tile) tile.classList.remove('dragging');
  document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
});

document.addEventListener('dragover', e => {
  e.preventDefault();
  const target = e.target.closest('.tile-card');
  if (target && target !== dragSrc) {
    target.classList.add('drag-over');
  }
});

document.addEventListener('dragleave', e => {
  const target = e.target.closest('.tile-card');
  if (target) target.classList.remove('drag-over');
});

document.addEventListener('drop', e => {
  e.preventDefault();
  const target = e.target.closest('.tile-card');
  if (!target || target === dragSrc || !dragSrc) return;

  target.classList.remove('drag-over');

  const grid = target.closest('.tiles-grid');
  const srcGrid = dragSrc.closest('.tiles-grid');

  if (grid && srcGrid) {
    // Reorder within/between grids
    const targetRect = target.getBoundingClientRect();
    const midX = targetRect.left + targetRect.width / 2;
    if (e.clientX < midX) {
      grid.insertBefore(dragSrc, target);
    } else {
      grid.insertBefore(dragSrc, target.nextSibling);
    }

    // Persist order
    const groupId  = grid.id.replace('grid-', '');
    const tileIds  = Array.from(grid.querySelectorAll('.tile-card:not(.tile-add)')).map(el => +el.dataset.tileId);

    // Update group_id if moved between groups
    if (srcGrid !== grid) {
      const tileId = +dragSrc.dataset.tileId;
      api('edit_tile', { id: tileId, group_id: +groupId,
        name: dragSrc.querySelector('.tile-name')?.textContent || '',
        url: dragSrc.querySelector('.tile-link')?.href || '' });
    }

    api('reorder_tiles', { items: tileIds }).catch(() => {});
  }
  dragSrc = null;
});

/* ── Keyboard shortcut: E = toggle edit mode ─────────────── */
document.addEventListener('keydown', e => {
  if (e.key === 'e' && !e.ctrlKey && !e.metaKey && !e.altKey) {
    // Only if not focused on an input
    if (!['INPUT','TEXTAREA','SELECT'].includes(document.activeElement?.tagName)) {
      toggleEditMode();
    }
  }
});
