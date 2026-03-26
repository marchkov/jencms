<div class="actions" style="margin-bottom: 16px;">
  <a class="button button--secondary" href="<?= e(admin_path($config, 'posts')) ?>">Back to posts</a>
</div>

<?php if ($errors !== []): ?>
  <ul class="errors">
    <?php foreach ($errors as $error): ?>
      <li><?= e($error) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data">
  <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

  <div class="grid grid--two">
    <section class="card">
      <h2>General settings</h2>

      <div class="field">
        <label for="section_id">Section</label>
        <select id="section_id" name="section_id" data-category-filter-target="category_id">
          <option value="0">Select a section</option>
          <?php foreach ($sections as $sectionOption): ?>
            <option value="<?= (int) $sectionOption['id'] ?>" <?= (int) $post['section_id'] === (int) $sectionOption['id'] ? 'selected' : '' ?>>
              <?= e(($sectionOption['title'] ?: '[untitled]') . ' (' . $sectionOption['slug'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" data-empty-label="No category" data-placeholder-label="Select a section first">
          <option value="">No category</option>
          <?php foreach ($categories as $categoryOption): ?>
            <option value="<?= (int) $categoryOption['id'] ?>" data-section-id="<?= (int) $categoryOption['section_id'] ?>" <?= $post['category_id'] !== null && (int) $post['category_id'] === (int) $categoryOption['id'] ? 'selected' : '' ?>>
              <?= e(($categoryOption['section_title'] ?: '[section]') . ' / ' . ($categoryOption['title'] ?: '[untitled]')) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="slug">Slug</label>
        <input id="slug" type="text" name="slug" value="<?= e($post['slug']) ?>" placeholder="spring-campaign-2026" spellcheck="false" autocapitalize="off" autocomplete="off">
      </div>

      <div class="field">
        <label for="title">Title</label>
        <input id="title" type="text" name="title" value="<?= e($post['title']) ?>" placeholder="Headline">
      </div>

      <div class="field">
        <label for="excerpt">Excerpt</label>
        <textarea id="excerpt" name="excerpt" rows="4" placeholder="Short teaser shown in post listings."><?= e($post['excerpt']) ?></textarea>
      </div>

      <div class="field">
        <label for="keywords">Keywords</label>
        <input id="keywords" type="text" name="keywords" value="<?= e($post['keywords']) ?>" placeholder="news, launch">
      </div>

      <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3" placeholder="Short summary used for search and social previews."><?= e($post['description']) ?></textarea>
      </div>

      <div class="field">
        <label for="image">Image path</label>
        <input id="image" type="text" name="image" value="<?= e($post['image']) ?>" placeholder="/uploads/example.jpg" spellcheck="false" autocapitalize="off" autocomplete="off">
      </div>

      <div class="field">
        <label for="image_upload">Upload image</label>
        <input id="image_upload" type="file" name="image_upload" accept=".jpg,.jpeg,.png,.gif,.webp,.svg">
      </div>

      <?php if ($post['image'] !== ''): ?>
        <div class="field">
          <label>Current image</label>
          <img src="<?= e(str_starts_with($post['image'], 'http') ? $post['image'] : site_page_url($config, ltrim($post['image'], '/'))) ?>" alt="" style="max-width: 220px; height: auto; display: block; border: 1px solid #d8d8d8; padding: 4px; background: #fff;">
        </div>
      <?php endif; ?>

      <div class="field">
        <label for="published_at">Published at</label>
        <input id="published_at" type="datetime-local" name="published_at" value="<?= e($post['published_at']) ?>" placeholder="2026-03-19T12:00">
      </div>

      <label class="checkbox">
        <input type="checkbox" name="is_published" value="1" <?= (int) $post['is_published'] === 1 ? 'checked' : '' ?>>
        <span>Published</span>
      </label>
    </section>

    <section class="card">
      <h2>Content</h2>
      <div class="field">
        <label for="content">Content</label>
        <textarea class="js-rich-editor" id="content" name="content" rows="18" placeholder="Write the full article body here."><?= e($post['content']) ?></textarea>
      </div>
      <p><a href="<?= e($mediaLibraryUrl) ?>">Open media library</a> to upload files and copy ready-to-use public paths.</p>
    </section>
  </div>

  <div class="actions" style="margin-top: 16px;">
    <input type="submit" value="<?= e($submitLabel) ?>">
    <a class="button button--secondary" href="<?= e(admin_path($config, 'posts')) ?>">Cancel</a>
  </div>
</form>
