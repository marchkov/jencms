<div class="grid grid--two">
  <section class="card">
    <h2>Pages</h2>
    <p>Manage static pages and their content.</p>
    <p><a class="button" href="<?= e(admin_path($config, 'pages')) ?>">Open page list</a></p>
  </section>

  <section class="card">
    <h2>Posts</h2>
    <p>Manage news posts, their publication dates, and frontend content.</p>
    <p><a class="button" href="<?= e(admin_path($config, 'posts')) ?>">Open post list</a></p>
  </section>

  <?php if (admin_has_role('administrator')): ?>
    <section class="card">
      <h2>Sections and categories</h2>
      <p>Administrators can manage sections, categories, users, settings, and templates.</p>
      <p><a class="button" href="<?= e(admin_path($config, 'sections')) ?>">Open section list</a></p>
    </section>

    <section class="card">
      <h2>Settings</h2>
      <p>Update site name, homepage, SEO defaults, and the default number of posts per page.</p>
      <p><a class="button" href="<?= e(admin_path($config, 'settings')) ?>">Open settings</a></p>
    </section>
  <?php else: ?>
    <section class="card">
      <h2>Your access</h2>
      <ul>
        <li>View the dashboard</li>
        <li>Create, edit, and delete pages</li>
        <li>Create, edit, and delete posts</li>
        <li>Create, edit, and delete categories</li>
      </ul>
    </section>
  <?php endif; ?>
</div>
