<div class="actions" style="margin-bottom: 16px; justify-content: space-between; align-items: flex-end;">
  <form method="get" action="<?= e(admin_path($config, 'categories')) ?>" class="actions" style="align-items: flex-end;">
    <div class="field" style="margin-bottom: 0; min-width: 220px;">
      <label for="category_search">Search</label>
      <input id="category_search" type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Search by title, section, or slug">
    </div>
    <div class="field" style="margin-bottom: 0; min-width: 220px;">
      <label for="category_section">Section</label>
      <select id="category_section" name="section_id">
        <option value="0">All sections</option>
        <?php foreach ($sections as $section): ?>
          <option value="<?= (int) $section['id'] ?>" <?= (int) $filters['section_id'] === (int) $section['id'] ? 'selected' : '' ?>>
            <?= e(($section['title'] ?: '[untitled]') . ' (' . $section['slug'] . ')') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <input type="submit" value="Filter">
    <a class="button button--secondary" href="<?= e(admin_path($config, 'categories')) ?>">Reset</a>
  </form>
  <a class="button" href="<?= e(admin_path($config, 'categories/create')) ?>">New category</a>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Section</th>
        <th>Slug</th>
        <th>Sort</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($categories as $category): ?>
      <tr>
        <td><?= (int) $category['id'] ?></td>
        <td><?= e($category['title'] ?: '[untitled]') ?></td>
        <td><?= e($category['section_title'] ?: '[no section]') ?></td>
        <td><code><?= e($category['slug']) ?></code></td>
        <td><?= (int) $category['sort_order'] ?></td>
        <td>
          <div class="actions">
            <a class="button button--secondary" href="<?= e(admin_path($config, 'categories/' . $category['id'] . '/edit')) ?>">Edit</a>
            <form method="post" action="<?= e(admin_path($config, 'categories/' . $category['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this category? Posts in it will keep working but lose the category link.');">
              <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
              <button type="submit" class="button--danger">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($categories === []): ?>
      <tr>
        <td colspan="6" class="muted">No categories matched the current filters.</td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
