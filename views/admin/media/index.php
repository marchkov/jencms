<div class="grid grid--two" style="margin-bottom: 16px;">
  <section class="card">
    <h2>Upload file</h2>
    <form method="post" action="<?= e($uploadAction) ?>" enctype="multipart/form-data">
      <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

      <div class="field">
        <label for="media_file">Choose file</label>
        <input id="media_file" type="file" name="media_file" accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt,.mp4,.webm,.mp3,.ogg">
        <div class="muted">Allowed: images, PDFs, office files, archives, audio, and video up to 10 MB.</div>
      </div>

      <div class="actions">
        <input type="submit" value="Upload file">
      </div>
    </form>
  </section>

  <section class="card">
    <h2>How to use</h2>
    <p>Upload files here, then copy their public path into post image fields or insert them into page content.</p>
    <p>Editors and administrators can both manage media.</p>
  </section>
</div>

<div class="card">
  <h2>Uploaded files</h2>

  <?php if ($files === []): ?>
    <p class="muted">No files uploaded yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Preview</th>
            <th>File</th>
            <th>Path</th>
            <th>Modified</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($files as $file): ?>
            <tr>
              <td>
                <?php if ($file['is_image']): ?>
                  <img src="<?= e(site_page_url($config, ltrim($file['path'], '/'))) ?>" alt="" style="max-width: 80px; max-height: 60px; display: block;">
                <?php else: ?>
                  <span class="muted">File</span>
                <?php endif; ?>
              </td>
              <td><?= e($file['name']) ?></td>
              <td>
                <code id="media-path-<?= md5($file['path']) ?>"><?= e($file['path']) ?></code>
              </td>
              <td><?= e($file['modified_at']) ?></td>
              <td>
                <div class="actions">
                  <button type="button" class="button button--secondary js-copy-path" data-copy-text="<?= e($file['path']) ?>" data-copy-target="media-path-<?= md5($file['path']) ?>">Copy path</button>
                  <a class="button button--secondary" href="<?= e(site_page_url($config, ltrim($file['path'], '/'))) ?>" target="_blank" rel="noreferrer">Open</a>
                  <form method="post" action="<?= e($deleteAction) ?>">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="path" value="<?= e($file['path']) ?>">
                    <button type="submit" class="button--danger" onclick="return confirm('Delete this file?');">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
