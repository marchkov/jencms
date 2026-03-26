<div class="actions" style="margin-bottom: 16px;">
  <a class="button button--secondary" href="<?= e(admin_path($config, 'templates')) ?>">Back to template files</a>
</div>

<?php if ($errors !== []): ?>
  <ul class="errors">
    <?php foreach ($errors as $error): ?>
      <li><?= e($error) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<div class="grid grid--two" style="margin-bottom: 16px;">
  <section class="card">
    <h2><?= e($file['name']) ?></h2>
    <p class="muted"><code><?= e($file['path']) ?></code></p>
    <p class="muted">This editor writes directly to the active theme file. Save carefully.</p>
    <div class="actions">
      <?php foreach ($previewLinks as $link): ?>
        <a class="button button--secondary" href="<?= e($link['url']) ?>" target="_blank" rel="noreferrer"><?= e($link['label']) ?></a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="card">
    <h2>Quick backups</h2>
    <?php if ($backups === []): ?>
      <p class="muted">No backups yet. The first save will create one automatically.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Backup</th>
              <th>Saved at</th>
              <th>Size</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($backups as $backup): ?>
            <tr>
              <td><code><?= e($backup['name']) ?></code></td>
              <td><?= e($backup['timestamp']) ?></td>
              <td><?= (int) $backup['size'] ?> bytes</td>
              <td>
                <form method="post" action="<?= e($restoreAction) ?>" onsubmit="return confirm('Restore this backup? The current file will be backed up first.');">
                  <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="backup" value="<?= e($backup['name']) ?>">
                  <button type="submit" class="button button--secondary">Restore</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<form method="post" action="<?= e($formAction) ?>">
  <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

  <div class="field">
    <label for="template_content">File content</label>
    <textarea id="template_content" name="content" rows="28" style="font-family: Consolas, 'Courier New', monospace; min-height: 480px;"><?= e($file['content']) ?></textarea>
  </div>

  <div class="actions">
    <input type="submit" value="Save file">
    <a class="button button--secondary" href="<?= e(admin_path($config, 'templates')) ?>">Cancel</a>
  </div>
</form>