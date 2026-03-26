<div class="actions" style="margin-bottom: 16px; justify-content: space-between; align-items: flex-end;">
  <form method="get" action="<?= e(admin_path($config, 'pages')) ?>" class="actions" style="align-items: flex-end;">
    <div class="field" style="margin-bottom: 0; min-width: 220px;">
      <label for="page_search">Search</label>
      <input id="page_search" type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Search by title or slug">
    </div>
    <div class="field" style="margin-bottom: 0; min-width: 180px;">
      <label for="page_status">Status</label>
      <select id="page_status" name="status">
        <option value="">All statuses</option>
        <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="hidden" <?= $filters['status'] === 'hidden' ? 'selected' : '' ?>>Hidden</option>
      </select>
    </div>
    <input type="submit" value="Filter">
    <a class="button button--secondary" href="<?= e(admin_path($config, 'pages')) ?>">Reset</a>
  </form>
  <a class="button" href="<?= e(admin_path($config, 'pages/create')) ?>">New page</a>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Slug</th>
        <th>Status</th>
        <th>Sort</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($pages as $page): ?>
      <tr>
        <td><?= (int) $page['id'] ?></td>
        <td><?= e($page['title'] ?: '[untitled]') ?></td>
        <td><code><?= e($page['slug']) ?></code></td>
        <td><?= (int) $page['is_published'] === 1 ? 'Published' : 'Hidden' ?></td>
        <td><?= (int) $page['sort_order'] ?></td>
        <td>
          <div class="actions">
            <a class="button button--secondary" href="<?= e(admin_path($config, 'pages/' . $page['id'] . '/edit')) ?>">Edit</a>
            <form method="post" action="<?= e(admin_path($config, 'pages/' . $page['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this page?');">
              <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
              <button type="submit" class="button--danger">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($pages === []): ?>
      <tr>
        <td colspan="6" class="muted">No pages matched the current filters.</td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
