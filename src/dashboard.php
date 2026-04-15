<?php
$user    = currentUser();
$db      = getDb();
$appName = getSetting('app_name', 'Ultimate Dashboard');
$appLogo = getSetting('app_logo', '');

// Fetch groups visible to this user
$stmt = $db->prepare(
    'SELECT * FROM tile_groups
     WHERE is_global=1 OR user_id=?
     ORDER BY sort_order, id'
);
$stmt->execute([$user['id']]);
$groups = $stmt->fetchAll();

// Fetch tiles for all those groups
$groupIds = array_column($groups, 'id');
$tiles    = [];
if ($groupIds) {
    $in   = implode(',', array_map('intval', $groupIds));
    $rows = $db->query("SELECT * FROM tiles WHERE group_id IN ($in) ORDER BY sort_order, id")->fetchAll();
    foreach ($rows as $tile) {
        $tiles[$tile['group_id']][] = $tile;
    }
}

$csrf = bin2hex(random_bytes(16));
$_SESSION['csrf'] = $csrf;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="/logo.svg">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<canvas id="bg-canvas" aria-hidden="true"></canvas>

<!-- ===================== NAVBAR ===================== -->
<header class="navbar" id="navbar">
  <div class="navbar-inner">
    <a href="/" class="navbar-brand">
      <?php if ($appLogo): ?>
        <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo" class="brand-logo-img">
      <?php else: ?>
        <div class="brand-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
            <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
          </svg>
        </div>
      <?php endif; ?>
      <span class="brand-name"><?= htmlspecialchars($appName) ?></span>
    </a>

    <nav class="nav-links" id="nav-links">
      <a href="/" class="nav-link active">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Dashboard
      </a>
      <?php if ($user['is_admin']): ?>
      <a href="/settings" class="nav-link">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 0 1 21 12a10 10 0 0 1-1.93 7.07M4.93 4.93A10 10 0 0 0 3 12a10 10 0 0 0 1.93 7.07M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>
        Settings
      </a>
      <?php endif; ?>
    </nav>

    <div class="navbar-right">
      <button class="btn btn-outline btn-sm edit-toggle" id="editToggle" title="Edit dashboard">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        <span>Edit</span>
      </button>

      <div class="user-menu" id="userMenuWrap">
        <button class="user-btn" id="userMenuBtn" aria-expanded="false" aria-haspopup="true">
          <div class="user-avatar"><?= strtoupper(substr($user['display_name'] ?: $user['username'], 0, 1)) ?></div>
          <span class="user-name"><?= htmlspecialchars($user['display_name'] ?: $user['username']) ?></span>
          <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="dropdown" id="userDropdown" role="menu">
          <a href="/settings" class="dropdown-item" role="menuitem">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 0 1 21 12a10 10 0 0 1-1.93 7.07M4.93 4.93A10 10 0 0 0 3 12a10 10 0 0 0 1.93 7.07"/></svg>
            Settings
          </a>
          <div class="dropdown-divider"></div>
          <a href="/logout" class="dropdown-item danger" role="menuitem">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign Out
          </a>
        </div>
      </div>

      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>
<!-- ===================== /NAVBAR ===================== -->

<!-- ===================== MAIN ===================== -->
<main class="main-content" id="main">

  <!-- Edit mode banner -->
  <div class="edit-banner" id="editBanner" hidden>
    <div class="edit-banner-inner">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Edit mode active - drag tiles to reorder, use the icons to edit or delete
      <button class="btn btn-sm btn-primary ml-auto" onclick="toggleEditMode(false)">Done Editing</button>
    </div>
  </div>

  <!-- Groups -->
  <div class="groups-container" id="groupsContainer">

    <?php if (empty($groups)): ?>
      <div class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        </div>
        <h2>No tiles yet</h2>
        <p>Click <strong>Edit</strong> and then <strong>+ Add Group</strong> to get started.</p>
      </div>
    <?php endif; ?>

    <?php foreach ($groups as $group): ?>
    <section class="group-section" data-group-id="<?= $group['id'] ?>">
      <div class="group-header">
        <div class="group-title-wrap">
          <div class="group-icon"><?= groupIcon($group['icon']) ?></div>
          <h2 class="group-title"><?= htmlspecialchars($group['name']) ?></h2>
        </div>
        <div class="group-actions edit-only" hidden>
          <button class="icon-btn" title="Edit group" onclick="openGroupModal(<?= $group['id'] ?>, '<?= htmlspecialchars(addslashes($group['name'])) ?>', '<?= htmlspecialchars($group['icon']) ?>')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="icon-btn danger" title="Delete group" onclick="deleteGroup(<?= $group['id'] ?>, '<?= htmlspecialchars(addslashes($group['name'])) ?>')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
          </button>
          <button class="icon-btn primary" title="Add tile" onclick="openTileModal(null, <?= $group['id'] ?>)">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          </button>
        </div>
      </div>

      <div class="tiles-grid" id="grid-<?= $group['id'] ?>">
        <?php foreach (($tiles[$group['id']] ?? []) as $tile): ?>
          <?php renderTile($tile) ?>
        <?php endforeach; ?>

        <!-- Add tile placeholder (edit mode) -->
        <div class="tile-add edit-only" hidden onclick="openTileModal(null, <?= $group['id'] ?>)" title="Add tile">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          <span>Add tile</span>
        </div>
      </div>
    </section>
    <?php endforeach; ?>

  </div><!-- /groups-container -->

  <!-- Add group button (edit mode) -->
  <div class="add-group-wrap edit-only" id="addGroupWrap" hidden>
    <button class="btn btn-outline add-group-btn" onclick="openGroupModal()">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      Add Group
    </button>
  </div>

</main>
<!-- ===================== /MAIN ===================== -->

<!-- ===================== MODALS ===================== -->

<!-- Tile modal -->
<div class="modal-backdrop" id="tileModalBackdrop" hidden>
<div class="modal" id="tileModal" role="dialog" aria-modal="true" aria-labelledby="tileModalTitle">
  <div class="modal-header">
    <h3 class="modal-title" id="tileModalTitle">Add Tile</h3>
    <button class="modal-close" onclick="closeModal('tileModal')" aria-label="Close">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="modal-body">
    <input type="hidden" id="tileTileId" value="">
    <input type="hidden" id="tileGroupId" value="">

    <div class="form-row">
      <div class="form-group">
        <label class="form-label" for="tileName">Application Name *</label>
        <input type="text" id="tileName" class="form-control" placeholder="e.g. Grafana" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="tileColor">Accent Color</label>
        <div class="color-input-wrap">
          <input type="color" id="tileColor" class="color-input" value="#00d4ff">
          <input type="text" id="tileColorText" class="form-control" value="#00d4ff" placeholder="#00d4ff">
        </div>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="tileUrl">URL *</label>
      <div class="input-wrap">
        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
        <input type="url" id="tileUrl" class="form-control" placeholder="https://grafana.yourdomain.com" required>
      </div>
      <button type="button" class="btn btn-ghost btn-sm mt-1" onclick="fetchFavicon()">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
        Auto-fetch icon
      </button>
    </div>

    <div class="form-group">
      <label class="form-label" for="tileDescription">Description (optional)</label>
      <input type="text" id="tileDescription" class="form-control" placeholder="Short description shown on tile">
    </div>

    <div class="form-group">
      <label class="form-label">Logo / Icon</label>
      <div class="icon-tabs">
        <button type="button" class="icon-tab active" data-tab="upload" onclick="switchIconTab('upload')">Upload</button>
        <button type="button" class="icon-tab" data-tab="library" onclick="switchIconTab('library')">Icon Library</button>
      </div>

      <!-- Upload tab -->
      <div id="iconTabUpload" class="icon-tab-panel">
        <div class="logo-upload-area" id="logoUploadArea">
          <div class="logo-preview" id="logoPreview">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          </div>
          <div class="logo-upload-text">
            <strong>Click to upload</strong> or drag &amp; drop<br>
            <span>PNG, JPG, SVG, ICO (max 2 MB)</span>
            <input type="file" id="tileLogoFile" accept="image/*" onchange="previewLogo(this)">
          </div>
        </div>
      </div>

      <!-- Icon Library tab -->
      <div id="iconTabLibrary" class="icon-tab-panel" hidden>
        <div class="icon-lib-toolbar">
          <input type="text" id="iconSearch" class="form-control" placeholder="Search 500+ icons..." oninput="filterIcons(this.value)">
          <select id="iconCategoryFilter" class="form-control" onchange="filterIcons(document.getElementById('iconSearch').value)">
            <option value="">All categories</option>
          </select>
        </div>
        <div class="icon-grid" id="iconGrid">
          <div class="icon-grid-loading">Loading icons...</div>
        </div>
        <div class="icon-lib-footer">
          <?php if (isAdmin()): ?>
          <button type="button" class="btn btn-ghost btn-sm" onclick="downloadLatestIcons()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            Download latest from GitHub
          </button>
          <?php endif; ?>
          <span id="iconCount" class="icon-lib-count"></span>
        </div>
      </div>

      <input type="hidden" id="tileLogoPath" value="">
    </div>

    <div class="form-group">
      <label class="form-label" for="tileGroupSelect">Group</label>
      <select id="tileGroupSelect" class="form-control">
        <?php foreach ($groups as $g): ?>
          <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-check">
      <input type="checkbox" id="tileNewTab" class="check-input" checked>
      <label for="tileNewTab" class="check-label">Open in new tab</label>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn btn-ghost" onclick="closeModal('tileModal')">Cancel</button>
    <button class="btn btn-primary" onclick="saveTile()">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      Save Tile
    </button>
  </div>
</div>
</div>

<!-- Group modal -->
<div class="modal-backdrop" id="groupModalBackdrop" hidden>
<div class="modal" id="groupModal" role="dialog" aria-modal="true" aria-labelledby="groupModalTitle">
  <div class="modal-header">
    <h3 class="modal-title" id="groupModalTitle">Add Group</h3>
    <button class="modal-close" onclick="closeModal('groupModal')" aria-label="Close">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="modal-body">
    <input type="hidden" id="groupGroupId" value="">
    <div class="form-group">
      <label class="form-label" for="groupName">Group Name *</label>
      <input type="text" id="groupName" class="form-control" placeholder="e.g. Monitoring" required>
    </div>
    <div class="form-group">
      <label class="form-label">Icon</label>
      <div class="icon-picker" id="iconPicker">
        <?php foreach (availableIcons() as $iconKey => $iconSvg): ?>
        <button type="button" class="icon-pick-btn" data-icon="<?= $iconKey ?>" onclick="selectIcon('<?= $iconKey ?>')" title="<?= $iconKey ?>">
          <?= $iconSvg ?>
        </button>
        <?php endforeach; ?>
      </div>
      <input type="hidden" id="groupIcon" value="grid">
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn btn-ghost" onclick="closeModal('groupModal')">Cancel</button>
    <button class="btn btn-primary" onclick="saveGroup()">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      Save Group
    </button>
  </div>
</div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toastContainer" aria-live="polite"></div>

<!-- Data for JS -->
<script>
window.CSRF = '<?= $csrf ?>';
window.IS_ADMIN = <?= $user['is_admin'] ? 'true' : 'false' ?>;
</script>
<script src="/assets/js/main.js"></script>
<script>initParticles('bg-canvas');</script>
</body>
</html>
<?php

// ── PHP helper functions ──────────────────────────────────────────────────────
function renderTile(array $tile): void {
    $logo    = $tile['logo_path'] ? htmlspecialchars($tile['logo_path']) : '';
    $color   = htmlspecialchars($tile['color'] ?: '#00d4ff');
    $name    = htmlspecialchars($tile['name']);
    $url     = htmlspecialchars($tile['url']);
    $desc    = htmlspecialchars($tile['description'] ?? '');
    $target  = $tile['open_new_tab'] ? '_blank' : '_self';
    $rel     = $tile['open_new_tab'] ? 'rel="noopener noreferrer"' : '';
    $id      = (int)$tile['id'];
    $gid     = (int)$tile['group_id'];
    $tileJson = htmlspecialchars(json_encode([
        'id'          => $id,
        'group_id'    => $gid,
        'name'        => $tile['name'],
        'url'         => $tile['url'],
        'description' => $tile['description'] ?? '',
        'logo_path'   => $tile['logo_path'] ?? '',
        'color'       => $tile['color'],
        'open_new_tab'=> (bool)$tile['open_new_tab'],
    ]), ENT_QUOTES);

    echo <<<HTML
<div class="tile-card" draggable="true" data-tile-id="$id" data-group-id="$gid" style="--tile-color:$color">
  <a href="$url" target="$target" $rel class="tile-link">
    <div class="tile-icon-wrap">
HTML;
    if ($logo) {
        echo '<img src="' . $logo . '" alt="' . $name . '" class="tile-logo" loading="lazy" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'">';
        echo '<div class="tile-initials" style="display:none">' . strtoupper(mb_substr($tile['name'], 0, 2)) . '</div>';
    } else {
        echo '<div class="tile-initials">' . strtoupper(mb_substr($tile['name'], 0, 2)) . '</div>';
    }
    echo <<<HTML
    </div>
    <div class="tile-info">
      <span class="tile-name">$name</span>
HTML;
    if ($desc) echo '<span class="tile-desc">' . $desc . '</span>';
    echo <<<HTML
    </div>
  </a>
  <div class="tile-edit-overlay edit-only" hidden>
    <button class="tile-action-btn" title="Edit tile" onclick="openTileModal($id, $gid, '$tileJson')">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
    </button>
    <button class="tile-action-btn danger" title="Delete tile" onclick="deleteTile($id, '$name')">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
    </button>
    <div class="tile-drag-handle" title="Drag to reorder">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
    </div>
  </div>
</div>
HTML;
}

function groupIcon(string $name): string {
    return availableIcons()[$name] ?? availableIcons()['grid'];
}

function availableIcons(): array {
    return [
        'grid'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
        'server'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6" y2="6"/><line x1="6" y1="18" x2="6" y2="18"/></svg>',
        'activity'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
        'shield'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'database'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
        'cloud'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',
        'globe'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
        'tool'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
        'star'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
        'home'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'lock'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
        'mail'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
    ];
}
