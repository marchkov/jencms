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
      <h2>Reset password</h2>
      <p>Updating the password for <strong><?= e($targetUser['name'] ?: $targetUser['login']) ?></strong> (<?= e($targetUser['login']) ?>).</p>

      <div class="field">
        <label for="password">New password</label>
        <input id="password" type="password" name="password" value="" placeholder="Minimum 8 characters">
      </div>

      <div class="field">
        <label for="password_confirm">Confirm password</label>
        <input id="password_confirm" type="password" name="password_confirm" value="" placeholder="Repeat the new password">
      </div>
    </section>

    <section class="card">
      <h2>Hint</h2>
      <p>This action only changes the password. Login, email, role, and status stay untouched.</p>
    </section>
  </div>

  <div class="actions" style="margin-top: 16px;">
    <input type="submit" value="Save password">
    <a class="button button--secondary" href="<?= e(admin_path($config, 'users')) ?>">Cancel</a>
  </div>
</form>
