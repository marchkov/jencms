<div class="actions" style="margin-bottom: 16px;">
  <a class="button button--secondary" href="<?= e(admin_path($config, 'categories')) ?>">Back to categories</a>
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
      <h2>General settings</h2>

      <div class="field">
        <label for="section_id">Section</label>
        <select id="section_id" name="section_id">
          <option value="0">Select a section</option>
          <?php foreach ($sections as $sectionOption): ?>
            <option value="<?= (int) $sectionOption['id'] ?>" <?= (int) $category['section_id'] === (int) $sectionOption['id'] ? 'selected' : '' ?>>
              <?= e(($sectionOption['title'] ?: '[untitled]') . ' (' . $sectionOption['slug'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="slug">Slug</label>
        <input id="slug" type="text" name="slug" value="<?= e($category['slug']) ?>" placeholder="campaigns" spellcheck="false" autocapitalize="off" autocomplete="off">
      </div>

      <div class="field">
        <label for="title">Title</label>
        <input id="title" type="text" name="title" value="<?= e($category['title']) ?>" placeholder="Campaigns">
      </div>

      <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3" placeholder="Optional category summary."><?= e($category['description']) ?></textarea>
      </div>

      <div class="field">
        <label for="sort_order">Sort order</label>
        <input id="sort_order" type="number" name="sort_order" value="<?= (int) $category['sort_order'] ?>" placeholder="0">
      </div>
    </section>

    <section class="card">
      <h2>Hint</h2>
      <p>Categories belong to a section, so the same slug may appear in different sections but must stay unique within one section.</p>
      <p>If a category is deleted, related posts are kept and their category is reset to empty.</p>
    </section>
  </div>

  <div class="actions" style="margin-top: 16px;">
    <input type="submit" value="<?= e($submitLabel) ?>">
    <a class="button button--secondary" href="<?= e(admin_path($config, 'categories')) ?>">Cancel</a>
  </div>
</form>
