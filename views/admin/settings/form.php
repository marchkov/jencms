<div class="actions" style="margin-bottom: 16px;">
  <span class="muted">Runtime-editable site settings stored in SQLite.</span>
</div>

<?php if ($errors !== []): ?>
  <ul class="errors">
    <?php foreach ($errors as $error): ?>
      <li><?= e($error) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="post" action="<?= e($formAction) ?>">
  <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

  <div class="grid grid--two">
    <section class="card">
      <h2>General</h2>

      <div class="field">
        <label for="site_name">Site name</label>
        <input id="site_name" type="text" name="site_name" value="<?= e($settingsForm['site_name']) ?>" placeholder="JenCMS">
      </div>

      <div class="field">
        <label for="homepage_slug">Homepage</label>
        <select id="homepage_slug" name="homepage_slug">
          <option value="">Select a page</option>
          <?php foreach ($pages as $page): ?>
            <option value="<?= e($page['slug']) ?>" <?= $settingsForm['homepage_slug'] === $page['slug'] ? 'selected' : '' ?>>
              <?= e(($page['title'] ?: '[untitled]') . ' (' . $page['slug'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="posts_per_page">Default posts per page</label>
        <input id="posts_per_page" type="number" min="6" step="6" name="posts_per_page" value="<?= (int) $settingsForm['posts_per_page'] ?>" placeholder="12">
      </div>

      <div class="field">
        <label for="default_keywords">Default keywords</label>
        <input id="default_keywords" type="text" name="default_keywords" value="<?= e($settingsForm['default_keywords']) ?>" placeholder="cms, php, sqlite">
      </div>

      <div class="field">
        <label for="default_description">Default description</label>
        <textarea id="default_description" name="default_description" rows="3" placeholder="Fallback meta description for pages without their own SEO text."><?= e($settingsForm['default_description']) ?></textarea>
      </div>
    </section>

    <section class="card">
      <h2>System note</h2>
      <p>This screen edits runtime content settings kept in SQLite.</p>
      <p>Environment-specific values such as <code>site.url</code>, database path, debug mode, and theme filesystem settings still stay in <code>settings.php</code>.</p>
    </section>
  </div>

  <div class="actions" style="margin-top: 16px;">
    <input type="submit" value="Save settings">
  </div>
</form>
