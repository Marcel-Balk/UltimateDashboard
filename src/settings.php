<?php
requireAuth();
$user    = currentUser();
$db      = getDb();
$appName = getSetting('app_name', 'Ultimate Dashboard');
$appLogo = getSetting('app_logo', '');
$users   = isAdmin() ? $db->query('SELECT id,username,display_name,is_admin,created_at FROM users ORDER BY id')->fetchAll() : [];
$csrf = $_SESSION['csrf'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings – <?= htmlspecialchars($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="/logo.svg">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<canvas id="bg-canvas" aria-hidden="true"></canvas>

<!-- NAVBAR -->
<header class="navbar" id="navbar">
  <div class="navbar-inner">
    <a href="/" class="navbar-brand">
      <?php if ($appLogo): ?>
        <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo" class="brand-logo-img">
      <?php else: ?>
        <div class="brand-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></div>
      <?php endif; ?>
      <span class="brand-name"><?= htmlspecialchars($appName) ?></span>
    </a>
    <nav class="nav-links">
      <a href="/" class="nav-link"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Dashboard</a>
      <a href="/settings" class="nav-link active"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 0 1 21 12a10 10 0 0 1-1.93 7.07M4.93 4.93A10 10 0 0 0 3 12a10 10 0 0 0 1.93 7.07"/></svg>Settings</a>
    </nav>
    <div class="navbar-right">
      <div class="user-menu">
        <a href="/logout" class="btn btn-outline btn-sm">Sign Out</a>
      </div>
    </div>
  </div>
</header>

<main class="main-content settings-page">
  <div class="settings-container">
    <div class="page-header">
      <h1 class="page-title">Settings</h1>
      <p class="page-sub">Manage your dashboard appearance and users</p>
    </div>

    <?php if (isAdmin()): ?>
    <!-- App Settings -->
    <div class="settings-card">
      <div class="settings-card-header">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        <div>
          <h2>Dashboard Settings</h2>
          <p>Customize the name and logo of your dashboard</p>
        </div>
      </div>
      <div class="settings-card-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="sAppName">Dashboard Name</label>
            <input type="text" id="sAppName" class="form-control" value="<?= htmlspecialchars($appName) ?>" placeholder="Ultimate Dashboard">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Dashboard Logo</label>
          <div class="logo-upload-area" id="appLogoArea">
            <div class="logo-preview" id="appLogoPreview">
              <?php if ($appLogo): ?>
                <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo" style="width:64px;height:64px;object-fit:contain">
              <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              <?php endif; ?>
            </div>
            <div class="logo-upload-text">
              <strong>Click to upload logo</strong><br>
              <span>PNG, JPG, SVG (max 2 MB)</span>
              <input type="file" id="appLogoFile" accept="image/*" onchange="uploadAppLogo(this)">
            </div>
          </div>
          <input type="hidden" id="sAppLogo" value="<?= htmlspecialchars($appLogo) ?>">
        </div>

        <div class="form-actions">
          <button class="btn btn-primary" onclick="saveAppSettings()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Settings
          </button>
        </div>
      </div>
    </div>

    <!-- User Management -->
    <div class="settings-card">
      <div class="settings-card-header">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <div>
          <h2>User Management</h2>
          <p>Add, remove, and manage dashboard users</p>
        </div>
      </div>
      <div class="settings-card-body">
        <table class="data-table">
          <thead>
            <tr><th>Username</th><th>Display Name</th><th>Role</th><th>Created</th><th>Actions</th></tr>
          </thead>
          <tbody id="usersTable">
            <?php foreach ($users as $u): ?>
            <tr data-uid="<?= $u['id'] ?>">
              <td><?= htmlspecialchars($u['username']) ?></td>
              <td><?= htmlspecialchars($u['display_name'] ?? '') ?></td>
              <td><span class="badge <?= $u['is_admin'] ? 'badge-primary' : 'badge-ghost' ?>"><?= $u['is_admin'] ? 'Admin' : 'User' ?></span></td>
              <td><?= date('Y-m-d', strtotime($u['created_at'])) ?></td>
              <td class="actions-cell">
                <button class="icon-btn" title="Change password" onclick="openChangePassword(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </button>
                <?php if ($u['id'] !== $user['id']): ?>
                <button class="icon-btn danger" title="Delete user" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="form-divider"></div>
        <h3 class="form-section-title">Add New User</h3>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="newUsername">Username *</label>
            <input type="text" id="newUsername" class="form-control" placeholder="username" autocomplete="off">
          </div>
          <div class="form-group">
            <label class="form-label" for="newDisplayName">Display Name</label>
            <input type="text" id="newDisplayName" class="form-control" placeholder="Full Name">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="newPassword">Password *</label>
            <input type="password" id="newPassword" class="form-control" placeholder="Min. 4 characters" autocomplete="new-password">
          </div>
          <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:4px">
            <label class="form-check" style="margin:0">
              <input type="checkbox" id="newIsAdmin" class="check-input">
              <span class="check-label">Administrator</span>
            </label>
          </div>
        </div>
        <button class="btn btn-outline" onclick="addUser()">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
          Add User
        </button>
      </div>
    </div>
    <?php endif; ?>

    <!-- Change own password -->
    <div class="settings-card">
      <div class="settings-card-header">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <div>
          <h2>Change Password</h2>
          <p>Update your own account password</p>
        </div>
      </div>
      <div class="settings-card-body">
        <div class="form-group" style="max-width:320px">
          <label class="form-label" for="myNewPassword">New Password</label>
          <input type="password" id="myNewPassword" class="form-control" placeholder="Min. 4 characters" autocomplete="new-password">
        </div>
        <button class="btn btn-outline" onclick="changeMyPassword()">Update Password</button>
      </div>
    </div>

  </div>
</main>

<!-- Change password modal -->
<div class="modal-backdrop" id="pwModalBackdrop" hidden>
<div class="modal" id="pwModal" role="dialog" aria-modal="true" aria-labelledby="pwModalTitle">
  <div class="modal-header">
    <h3 class="modal-title" id="pwModalTitle">Change Password</h3>
    <button class="modal-close" onclick="closeModal('pwModal')" aria-label="Close"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
  </div>
  <div class="modal-body">
    <input type="hidden" id="pwUserId" value="">
    <div class="form-group">
      <label class="form-label">New Password for <strong id="pwUserLabel"></strong></label>
      <input type="password" id="pwNewPassword" class="form-control" placeholder="Min. 4 characters" autocomplete="new-password">
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn btn-ghost" onclick="closeModal('pwModal')">Cancel</button>
    <button class="btn btn-primary" onclick="savePassword()">Save Password</button>
  </div>
</div>
</div>

<div class="toast-container" id="toastContainer" aria-live="polite"></div>

<script>window.CSRF = '<?= $csrf ?>';</script>
<script src="/assets/js/main.js"></script>
<script>
initParticles('bg-canvas');

function saveAppSettings() {
  api('save_settings', {app_name: document.getElementById('sAppName').value, app_logo: document.getElementById('sAppLogo').value})
    .then(r => { if(r.ok) { showToast('Settings saved!','success'); setTimeout(()=>location.reload(),800); } else showToast(r.error,'error'); });
}

function uploadAppLogo(input) {
  if (!input.files[0]) return;
  const fd = new FormData();
  fd.append('logo', input.files[0]);
  fd.append('csrf', window.CSRF);
  fetch('/api?action=upload_app_logo', {method:'POST',body:fd})
    .then(r=>r.json()).then(r=>{
      if(r.ok){ document.getElementById('sAppLogo').value=r.path; document.getElementById('appLogoPreview').innerHTML='<img src="'+r.path+'" style="width:64px;height:64px;object-fit:contain" alt="Logo">'; showToast('Logo uploaded','success'); }
      else showToast(r.error,'error');
    });
}

function addUser() {
  api('add_user', {username:document.getElementById('newUsername').value, password:document.getElementById('newPassword').value, display_name:document.getElementById('newDisplayName').value, is_admin:document.getElementById('newIsAdmin').checked})
    .then(r=>{ if(r.ok){showToast('User added','success');setTimeout(()=>location.reload(),800);}else showToast(r.error,'error'); });
}

function deleteUser(id, name) {
  if(!confirm('Delete user "'+name+'"?')) return;
  api('delete_user',{id}).then(r=>{ if(r.ok){document.querySelector('[data-uid="'+id+'"]')?.remove();showToast('User deleted','success');}else showToast(r.error,'error'); });
}

function openChangePassword(id, name) {
  document.getElementById('pwUserId').value = id;
  document.getElementById('pwUserLabel').textContent = name;
  document.getElementById('pwNewPassword').value = '';
  openModal('pwModal');
}

function savePassword() {
  api('change_password', {id:+document.getElementById('pwUserId').value, password:document.getElementById('pwNewPassword').value})
    .then(r=>{ if(r.ok){closeModal('pwModal');showToast('Password updated','success');}else showToast(r.error,'error'); });
}

function changeMyPassword() {
  api('change_password', {password:document.getElementById('myNewPassword').value})
    .then(r=>{ if(r.ok){document.getElementById('myNewPassword').value='';showToast('Password updated','success');}else showToast(r.error,'error'); });
}
</script>
</body>
</html>
