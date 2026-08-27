<div class="actions" style="margin-bottom: 16px;">
  <a class="button button--secondary" href="<?= e(admin_path($config, 'pages')) ?>">Back to pages</a>
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

  <div class="form-stack">
    <section class="card">
      <h2>General settings</h2>

      <div class="field">
        <label for="slug">Slug</label>
        <input id="slug" type="text" name="slug" value="<?= e($page['slug']) ?>" placeholder="about-us" spellcheck="false" autocapitalize="off" autocomplete="off">
      </div>

      <div class="field">
        <label for="title">Title</label>
        <input id="title" type="text" name="title" value="<?= e($page['title']) ?>" placeholder="About us">
      </div>

      <div class="field">
        <label for="keywords">Keywords</label>
        <input id="keywords" type="text" name="keywords" value="<?= e($page['keywords']) ?>" placeholder="company, services">
      </div>

      <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3" placeholder="Short summary used in search results."><?= e($page['description']) ?></textarea>
      </div>

      <div class="field">
        <label for="sort_order">Sort order</label>
        <input id="sort_order" type="number" name="sort_order" value="<?= (int) $page['sort_order'] ?>" placeholder="0">
      </div>

      <label class="checkbox">
        <input type="checkbox" name="is_published" value="1" <?= (int) $page['is_published'] === 1 ? 'checked' : '' ?>>
        <span>Published</span>
      </label>
    </section>

    <div>
      <textarea class="js-rich-editor" id="content" name="content" rows="24" aria-label="Page content" placeholder="Write the page content here."><?= e($page['content']) ?></textarea>
      <script type="application/json" class="js-editor-media-data"><?= json_encode([
        'uploadUrl' => $mediaUploadUrl,
        'images' => array_map(static fn (array $file): array => [
          'name' => $file['name'],
          'path' => $file['path'],
          'url' => site_page_url($config, ltrim($file['path'], '/')),
        ], $mediaImages),
      ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    </div>
  </div>

  <div class="actions" style="margin-top: 16px;">
    <input type="submit" value="<?= e($submitLabel) ?>">
    <a class="button button--secondary" href="<?= e(admin_path($config, 'pages')) ?>">Cancel</a>
  </div>
</form>
