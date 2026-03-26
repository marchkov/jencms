<div class="actions" style="margin-bottom: 16px;">
  <a class="button button--secondary" href="<?= e(admin_path($config, 'users')) ?>">Back to users</a>
</div>

<?php if ($errors !== []): ?>
  <ul class="errors">
    <?php foreach ($errors as $error): ?>
      <li><?= e($error) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="post" action="<?= e($formAction) ?>">
  <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

  <div class="grid grid--two">
    <section class="card">
      <h2>Account</h2>

      <div class="field">
        <label for="login">Login</label>
        <input id="login" type="text" name="login" value="<?= e($userForm['login']) ?>" placeholder="editor.jana" spellcheck="false" autocapitalize="off" autocomplete="off">
      </div>

      <div class="field">
        <label for="name">Name</label>
        <input id="name" type="text" name="name" value="<?= e($userForm['name']) ?>" placeholder="Jana Novakova">
      </div>

      <div class="field">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="<?= e($userForm['email']) ?>" placeholder="jana@example.org" autocapitalize="off" autocomplete="off">
      </div>

      <div class="field">
        <label for="role">Role</label>
        <select id="role" name="role">
          <option value="administrator" <?= $userForm['role'] === 'administrator' ? 'selected' : '' ?>>Administrator</option>
          <option value="editor" <?= $userForm['role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
        </select>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input id="password" type="password" name="password" value="" placeholder="<?= $isEdit ? 'Leave empty to keep the current password' : 'Minimum 8 characters' ?>">
      </div>

      <label class="checkbox">
        <input type="checkbox" name="is_active" value="1" <?= (int) $userForm['is_active'] === 1 ? 'checked' : '' ?>>
        <span>Active user</span>
      </label>
    </section>

    <section class="card">
      <h2>Hint</h2>
      <p>Administrators can manage every part of the CMS. Editors can work with pages and sections only.</p>
      <p>Use the separate reset-password action in the user list when you need to change a password quickly without touching the rest of the account.</p>
    </section>
  </div>

  <div class="actions" style="margin-top: 16px;">
    <input type="submit" value="<?= e($submitLabel) ?>">
    <a class="button button--secondary" href="<?= e(admin_path($config, 'users')) ?>">Cancel</a>
  </div>
</form>
