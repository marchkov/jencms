<div class="actions" style="margin-bottom: 16px; justify-content: space-between; align-items: flex-end;">
  <form method="get" action="<?= e(admin_path($config, 'sections')) ?>" class="actions" style="align-items: flex-end;">
    <div class="field" style="margin-bottom: 0; min-width: 220px;">
      <label for="section_search">Search</label>
      <input id="section_search" type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Search by title or slug">
    </div>
    <div class="field" style="margin-bottom: 0; min-width: 180px;">
      <label for="section_status">Status</label>
      <select id="section_status" name="status">
        <option value="">All statuses</option>
        <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="hidden" <?= $filters['status'] === 'hidden' ? 'selected' : '' ?>>Hidden</option>
      </select>
    </div>
    <input type="submit" value="Filter">
    <a class="button button--secondary" href="<?= e(admin_path($config, 'sections')) ?>">Reset</a>
  </form>
  <?php if (admin_has_any_role(['administrator', 'editor'])): ?>
    <a class="button" href="<?= e(admin_path($config, 'sections/create')) ?>">New section</a>
  <?php endif; ?>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Slug</th>
        <th>Posts per page</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($sections as $section): ?>
      <tr>
        <td><?= (int) $section['id'] ?></td>
        <td><?= e($section['title'] ?: '[untitled]') ?></td>
        <td><code><?= e($section['slug']) ?></code></td>
        <td><?= (int) $section['posts_per_page'] ?></td>
        <td><?= (int) $section['is_published'] === 1 ? 'Published' : 'Hidden' ?></td>
        <td>
          <div class="actions">
            <a class="button button--secondary" href="<?= e(admin_path($config, 'sections/' . $section['id'] . '/edit')) ?>">Edit</a>
            <?php if (admin_has_role('administrator')): ?>
              <form method="post" action="<?= e(admin_path($config, 'sections/' . $section['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this section and its related content?');">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="button--danger">Delete</button>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($sections === []): ?>
      <tr>
        <td colspan="6" class="muted">No sections matched the current filters.</td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
