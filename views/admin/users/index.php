<div class="actions" style="margin-bottom: 16px; justify-content: space-between; align-items: flex-end;">
  <form method="get" action="<?= e(admin_path($config, 'users')) ?>" class="actions" style="align-items: flex-end;">
    <div class="field" style="margin-bottom: 0; min-width: 220px;">
      <label for="user_search">Search</label>
      <input id="user_search" type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Search by login, name, or email">
    </div>
    <div class="field" style="margin-bottom: 0; min-width: 180px;">
      <label for="user_status">Status</label>
      <select id="user_status" name="status">
        <option value="">All statuses</option>
        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
    <input type="submit" value="Filter">
    <a class="button button--secondary" href="<?= e(admin_path($config, 'users')) ?>">Reset</a>
  </form>
  <a class="button" href="<?= e(admin_path($config, 'users/create')) ?>">New user</a>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Login</th>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
      <tr>
        <td><?= (int) $user['id'] ?></td>
        <td><code><?= e($user['login']) ?></code></td>
        <td><?= e($user['name'] ?: '-') ?></td>
        <td><?= e($user['email'] ?: '-') ?></td>
        <td><?= e($user['role'] === 'administrator' ? 'Administrator' : 'Editor') ?></td>
        <td><?= (int) $user['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
        <td>
          <div class="actions">
            <a class="button button--secondary" href="<?= e(admin_path($config, 'users/' . $user['id'] . '/edit')) ?>">Edit</a>
            <a class="button button--secondary" href="<?= e(admin_path($config, 'users/' . $user['id'] . '/reset-password')) ?>">Reset password</a>
            <form method="post" action="<?= e(admin_path($config, 'users/' . $user['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this user?');">
              <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
              <button type="submit" class="button--danger">Delete</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if ($users === []): ?>
      <tr>
        <td colspan="7" class="muted">No users matched the current filters.</td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
