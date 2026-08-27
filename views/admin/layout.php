<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($adminTitle) ?> :: <?= e($config['site']['name']) ?></title>
  <link rel="stylesheet" href="<?= e(site_page_url($config, 'admin-assets/editor.css', ['v' => (string) filemtime(BASE_PATH . '/public/admin-assets/editor.css')])) ?>">
  <style>
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f5f5f5; color: #222; }
    a { color: #0b57d0; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .admin-shell { max-width: 1120px; margin: 0 auto; padding: 16px; }
    .admin-header { background: #fff; border: 1px solid #d8d8d8; padding: 16px; margin-bottom: 16px; }
    .admin-header__top { display: flex; gap: 12px; justify-content: space-between; align-items: center; flex-wrap: wrap; }
    .admin-title { font-size: 24px; margin: 0; }
    .admin-subtitle { margin: 4px 0 0; color: #666; }
    .admin-nav { margin-top: 16px; display: flex; gap: 8px; flex-wrap: wrap; }
    .admin-nav a, .admin-nav button { display: inline-block; padding: 10px 14px; border: 1px solid #c8c8c8; background: #fafafa; color: #222; font: inherit; cursor: pointer; }
    .admin-nav a.is-active { background: #222; border-color: #222; color: #fff; }
    .admin-content { background: #fff; border: 1px solid #d8d8d8; padding: 16px; }
    .flash { padding: 12px 14px; margin-bottom: 16px; border: 1px solid #ccc; }
    .flash--success { background: #edf7ed; border-color: #b7d7b9; }
    .flash--error { background: #fdf0f0; border-color: #e3b0b0; }
    .grid { display: grid; gap: 16px; }
    .grid--two { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
    .form-stack { display: grid; gap: 20px; }
    .form-stack > * { min-width: 0; }
    .card { border: 1px solid #d8d8d8; padding: 16px; background: #fafafa; }
    .card h2, .card h3 { margin-top: 0; }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 10px 8px; border-bottom: 1px solid #ddd; text-align: left; vertical-align: top; }
    th { background: #f6f6f6; }
    .actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .button, button, input[type="submit"] { display: inline-block; padding: 10px 14px; border: 1px solid #555; background: #222; color: #fff; font: inherit; cursor: pointer; }
    .button--secondary { background: #fff; color: #222; }
    .button--danger { background: #a52222; border-color: #8c1d1d; }
    form { margin: 0; }
    .field { display: grid; gap: 6px; margin-bottom: 14px; }
    label { font-weight: bold; }
    input[type="text"], input[type="password"], input[type="number"], input[type="datetime-local"], input[type="email"], textarea, select { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #bdbdbd; background: #fff; color: #222; font: inherit; }
    textarea { min-height: 78px; resize: vertical; }
    .checkbox { display: flex; align-items: center; gap: 8px; margin-top: 12px; }
    .tabs { display: grid; gap: 16px; }
    .tab-card { border: 1px solid #ddd; padding: 14px; background: #fcfcfc; }
    .errors { margin: 0 0 16px; padding: 12px 16px; border: 1px solid #d8aaaa; background: #fff2f2; }
    .errors li + li { margin-top: 6px; }
    .muted { color: #666; }
    .system-checks { display: grid; gap: 10px; }
    .system-check { display: grid; gap: 5px; padding: 10px 0; border-top: 1px solid #ddd; overflow-wrap: anywhere; }
    .system-check__heading { display: flex; gap: 8px; justify-content: space-between; align-items: center; }
    .system-status { display: inline-block; min-width: 64px; padding: 4px 7px; border: 1px solid #bbb; text-align: center; font-size: 12px; font-weight: bold; }
    .system-status--ok { background: #edf7ed; border-color: #8fbe93; color: #235d29; }
    .system-status--warning { background: #fff7df; border-color: #d8bd68; color: #705800; }
    .system-status--error { background: #fdf0f0; border-color: #d99797; color: #8c1d1d; }
    .auth-shell { max-width: 460px; margin: 48px auto; background: #fff; border: 1px solid #d8d8d8; padding: 20px; }
    .auth-shell h1 { margin-top: 0; }
    @media (max-width: 720px) {
      .admin-shell { padding: 10px; }
      .admin-content, .admin-header { padding: 12px; }
      th:nth-child(4), td:nth-child(4), th:nth-child(5), td:nth-child(5) { display: none; }
      .form-stack { gap: 14px; }
      .actions > .button, .actions > button, .actions > input[type="submit"] { flex: 1 1 auto; text-align: center; }
    }
  </style>
</head>
<body>
<?php if (! empty($hideNavigation)): ?>
  <div class="auth-shell">
    <?php if ($flashSuccess): ?><div class="flash flash--success"><?= e($flashSuccess) ?></div><?php endif; ?>
    <?php if ($flashError): ?><div class="flash flash--error"><?= e($flashError) ?></div><?php endif; ?>
    <?php require $viewPath; ?>
  </div>
<?php else: ?>
  <div class="admin-shell">
    <header class="admin-header">
      <div class="admin-header__top">
        <div>
          <h1 class="admin-title"><?= e($adminTitle) ?></h1>
          <p class="admin-subtitle"><?= e($config['site']['name']) ?> admin</p>
        </div>
        <div class="muted">
          <?php if ($adminUser): ?>Signed in as <strong><?= e($adminUser['name'] ?: $adminUser['login']) ?></strong> (<?= e(admin_role()) ?>)<?php endif; ?>
        </div>
      </div>
      <nav class="admin-nav">
        <a href="<?= e(admin_path($config)) ?>" class="<?= current_path() === '/' . trim($config['routes']['admin_prefix'], '/') ? 'is-active' : '' ?>">Dashboard</a>
        <a href="<?= e(admin_path($config, 'pages')) ?>" class="<?= str_contains(current_path(), '/' . trim($config['routes']['admin_prefix'], '/') . '/pages') ? 'is-active' : '' ?>">Pages</a>
        <a href="<?= e(admin_path($config, 'posts')) ?>" class="<?= str_contains(current_path(), '/' . trim($config['routes']['admin_prefix'], '/') . '/posts') ? 'is-active' : '' ?>">Posts</a>
        <a href="<?= e(admin_path($config, 'categories')) ?>" class="<?= str_contains(current_path(), '/' . trim($config['routes']['admin_prefix'], '/') . '/categories') ? 'is-active' : '' ?>">Categories</a>
        <a href="<?= e(admin_path($config, 'media')) ?>" class="<?= str_contains(current_path(), '/' . trim($config['routes']['admin_prefix'], '/') . '/media') ? 'is-active' : '' ?>">Media</a>
        <?php if (admin_has_role('administrator')): ?>
          <a href="<?= e(admin_path($config, 'sections')) ?>" class="<?= str_contains(current_path(), '/' . trim($config['routes']['admin_prefix'], '/') . '/sections') ? 'is-active' : '' ?>">Sections</a>
          <a href="<?= e(admin_path($config, 'users')) ?>" class="<?= str_contains(current_path(), '/' . trim($config['routes']['admin_prefix'], '/') . '/users') ? 'is-active' : '' ?>">Users</a>
          <a href="<?= e(admin_path($config, 'settings')) ?>" class="<?= str_contains(current_path(), '/' . trim($config['routes']['admin_prefix'], '/') . '/settings') ? 'is-active' : '' ?>">Settings</a>
          <a href="<?= e(admin_path($config, 'templates')) ?>" class="<?= str_contains(current_path(), '/' . trim($config['routes']['admin_prefix'], '/') . '/templates') ? 'is-active' : '' ?>">Templates</a>
        <?php endif; ?>
        <a href="<?= e(site_page_url($config)) ?>" target="_blank" rel="noreferrer">Open site</a>
        <form method="post" action="<?= e(admin_path($config, 'logout')) ?>">
          <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
          <button type="submit" class="button--secondary">Sign out</button>
        </form>
      </nav>
    </header>
    <main class="admin-content">
      <?php if ($flashSuccess): ?><div class="flash flash--success"><?= e($flashSuccess) ?></div><?php endif; ?>
      <?php if ($flashError): ?><div class="flash flash--error"><?= e($flashError) ?></div><?php endif; ?>
      <?php require $viewPath; ?>
    </main>
  </div>
<?php endif; ?>
<script>
(function () {
  function initEditors() {
    // Tiptap editors are initialized by the ES module below.
  }

  function initCategoryFilters() {
    var sectionSelects = document.querySelectorAll('select[data-category-filter-target]');

    function syncCategorySelect(sectionSelect, categorySelect) {
      var selectedSection = sectionSelect.value;
      var options = categorySelect.querySelectorAll('option');
      var hasVisibleCategory = false;
      var emptyLabel = categorySelect.getAttribute('data-empty-label') || 'No category';
      var placeholderLabel = categorySelect.getAttribute('data-placeholder-label') || emptyLabel;

      for (var i = 0; i < options.length; i += 1) {
        var option = options[i];
        var optionSectionId = option.getAttribute('data-section-id');

        if (option.value === '') {
          option.textContent = selectedSection && selectedSection !== '0' ? emptyLabel : placeholderLabel;
          option.hidden = false;
          continue;
        }

        var shouldShow = selectedSection !== '' && selectedSection !== '0' && optionSectionId === selectedSection;
        option.hidden = !shouldShow;
        if (!shouldShow && option.selected) {
          categorySelect.value = '';
        }
        if (shouldShow) {
          hasVisibleCategory = true;
        }
      }

      categorySelect.disabled = !hasVisibleCategory && (!selectedSection || selectedSection === '0');

      if (!hasVisibleCategory && selectedSection && selectedSection !== '0') {
        categorySelect.value = '';
      }
    }

    for (var i = 0; i < sectionSelects.length; i += 1) {
      (function () {
        var sectionSelect = sectionSelects[i];
        var categoryId = sectionSelect.getAttribute('data-category-filter-target');
        var categorySelect = document.getElementById(categoryId);

        if (!categorySelect) {
          return;
        }

        syncCategorySelect(sectionSelect, categorySelect);
        sectionSelect.addEventListener('change', function () {
          syncCategorySelect(sectionSelect, categorySelect);
        });
      }());
    }
  }

  function initCopyButtons() {
    var buttons = document.querySelectorAll('.js-copy-path');

    function fallbackCopy(text, targetId) {
      var target = targetId ? document.getElementById(targetId) : null;
      if (!target) {
        return false;
      }

      if (document.body.createTextRange) {
        var range = document.body.createTextRange();
        range.moveToElementText(target);
        range.select();
        return true;
      }

      if (window.getSelection && document.createRange) {
        var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(target);
        selection.removeAllRanges();
        selection.addRange(range);
        return true;
      }

      return false;
    }

    function setCopiedState(button) {
      var original = button.getAttribute('data-original-label') || button.textContent;
      button.setAttribute('data-original-label', original);
      button.textContent = 'Copied';
      window.setTimeout(function () {
        button.textContent = original;
      }, 1200);
    }

    for (var i = 0; i < buttons.length; i += 1) {
      buttons[i].addEventListener('click', function () {
        var button = this;
        var text = button.getAttribute('data-copy-text') || '';
        var targetId = button.getAttribute('data-copy-target') || '';

        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(function () {
            setCopiedState(button);
          }, function () {
            if (fallbackCopy(text, targetId)) {
              setCopiedState(button);
            }
          });
          return;
        }

        if (fallbackCopy(text, targetId)) {
          setCopiedState(button);
        }
      });
    }
  }

  function initAdminEnhancements() {
    initEditors();
    initCategoryFilters();
    initCopyButtons();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminEnhancements);
  } else {
    initAdminEnhancements();
  }
}());
</script>
<script src="<?= e(site_page_url($config, 'admin-assets/tiptap-v3.30.2.min.js', ['v' => (string) filemtime(BASE_PATH . '/public/admin-assets/tiptap-v3.30.2.min.js')])) ?>"></script>
<script src="<?= e(site_page_url($config, 'admin-assets/editor.js', ['v' => (string) filemtime(BASE_PATH . '/public/admin-assets/editor.js')])) ?>"></script>
</body>
</html>

