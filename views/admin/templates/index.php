<div class="actions" style="margin-bottom: 16px;">
  <span class="muted">Editing files from the active theme: <strong><?= e($config['site']['theme']) ?></strong></span>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>File</th>
        <th>Type</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($files as $file): ?>
      <tr>
        <td><code><?= e($file['path']) ?></code></td>
        <td><?= e(strtoupper($file['extension'])) ?></td>
        <td><a class="button button--secondary" href="<?= e(admin_path($config, 'templates/edit') . '?file=' . rawurlencode($file['path'])) ?>">Edit</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>