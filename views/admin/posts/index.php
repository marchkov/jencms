<div class="actions" style="margin-bottom: 16px; justify-content: space-between; align-items: flex-end;">
  <form method="get" action="<?= e(admin_path($config, 'posts')) ?>" class="actions" style="align-items: flex-end;">
    <div class="field" style="margin-bottom: 0; min-width: 220px;">
      <label for="post_search">Search</label>
      <input id="post_search" type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Search by title or slug">
    </div>
    <div class="field" style="margin-bottom: 0; min-width: 220px;">
      <label for="post_section">Section</label>
      <select id="post_section" name="section_id">
        <option value="0">All sections</option>
        <?php foreach ($sections as $section): ?>
          <option value="<?= (int) $section['id'] ?>" <?= (int) $filters['section_id'] === (int) $section['id'] ? 'selected' : '' ?>>
            <?= e(($section['title'] ?: '[untitled]') . ' (' . $section['slug'] . ')') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field" style="margin-bottom: 0; min-width: 180px;">
      <label for="post_status">Status</label>
      <select id="post_status" name="status">
        <option value="">All statuses</option>
        <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="hidden" <?= $filters['status'] === 'hidden' ? 'selected' : '' ?>>Hidden</option>
      </select>
    </div>
    <input type="submit" value="Filter">
    <a class="button button--secondary" href="<?= e(admin_path($config, 'posts')) ?>">Reset</a>
  </form>
  <a class="button" href="<?= e(admin_path($config, 'posts/create')) ?>">New post</a>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Section</th>
        <th>Slug</th>
        <th>Published at</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($posts as $post): ?>
      <tr>
        <td><?= (int) $post['id'] ?></td>
        <td><?= e($post['title'] ?: '[untitled]') ?></td>
        <td><?= e($post['section_title'] ?: '[no section]') ?></td>
        <td><code><?= e($post['slug']) ?></code></td>
        <td><?= e(format_admin_datetime($post['published_at'] ?? '')) ?></td>
        <td><?= (int) $post['is_published'] === 1 ? 'Published' : 'Hidden' ?></td>
        <td>
          <div class="actions">
            <a class="button button--secondary" href="<?= e(admin_path($config, 'posts/' . $post['id'] . '/edit')) ?>">Edit</a>
            <form method="post" action="<?= e(admin_path($config, 'posts/' . $post['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this post?');">
              <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
              <button type="submit" class="button--danger">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($posts === []): ?>
      <tr>
        <td colspan="7" class="muted">No posts matched the current filters.</td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
