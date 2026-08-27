<div class="actions" style="margin-bottom: 16px;">
  <span class="muted">Editing files from the active theme: <strong><?= e($config['site']['theme']) ?></strong></span>
</div>

<section class="card" style="margin-top: 16px;">
  <h2>Available template variables</h2>
  <p class="muted">Use these variables in <code>.tpl</code> files. Header, content, and footer contain rendered HTML; all other values are escaped.</p>

  <div class="grid grid--two">
    <div>
      <h3>Content and metadata</h3>
      <p><code>[HEADER]</code> <code>[CONTENT]</code> <code>[FOOTER]</code></p>
      <p><code>[PAGE_TITLE]</code> <code>[META_KEYWORDS]</code> <code>[META_DESCRIPTION]</code></p>
    </div>
    <div>
      <h3>Site and theme</h3>
      <p><code>[SITE_NAME]</code> <code>[SITE_URL]</code> <code>[HOME_URL]</code> <code>[CURRENT_URL]</code> <code>[ADMIN_URL]</code></p>
      <p><code>[THEME_URL]</code> <code>[BODY_CLASS]</code> <code>[HTML_LANG]</code> <code>[CURRENT_YEAR]</code></p>
    </div>
  </div>
</section>

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
