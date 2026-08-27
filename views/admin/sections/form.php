<div class="actions" style="margin-bottom: 16px;">
  <a class="button button--secondary" href="<?= e(admin_path($config, 'sections')) ?>">Back to sections</a>
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
        <input id="slug" type="text" name="slug" value="<?= e($section['slug']) ?>" placeholder="news" spellcheck="false" autocapitalize="off" autocomplete="off">
      </div>

      <div class="field">
        <label for="title">Title</label>
        <input id="title" type="text" name="title" value="<?= e($section['title']) ?>" placeholder="News">
      </div>

      <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3" placeholder="Short intro shown above the listing."><?= e($section['description']) ?></textarea>
      </div>

      <div class="field">
        <label for="posts_per_page">Posts per page</label>
        <input id="posts_per_page" type="number" min="6" step="6" name="posts_per_page" value="<?= (int) $section['posts_per_page'] ?>" placeholder="12">
      </div>

      <div class="field">
        <label for="sort_order">Sort order</label>
        <input id="sort_order" type="number" name="sort_order" value="<?= (int) $section['sort_order'] ?>" placeholder="0">
      </div>

      <label class="checkbox">
        <input type="checkbox" name="is_published" value="1" <?= (int) $section['is_published'] === 1 ? 'checked' : '' ?>>
        <span>Published</span>
      </label>
    </section>

    <section class="card">
      <h2>Hint</h2>
      <p>Sections are listing pages like <code>/news</code>. Their slug must be unique across both sections and pages.</p>
      <p>Deleting a section will also remove its related categories and posts, because the database uses cascading deletes.</p>
    </section>
  </div>

  <div class="actions" style="margin-top: 16px;">
    <input type="submit" value="<?= e($submitLabel) ?>">
    <a class="button button--secondary" href="<?= e(admin_path($config, 'sections')) ?>">Cancel</a>
  </div>
</form>
