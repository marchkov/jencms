<h1>Login to adminpanel</h1>

<?php if (! empty($errorMessage)): ?>
  <div class="flash flash--error"><?= e($errorMessage) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(admin_path($config, 'login')) ?>">
  <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">

  <div class="field">
    <label for="login">Login</label>
    <input id="login" type="text" name="login" value="<?= e($login) ?>" autocomplete="username">
  </div>

  <div class="field">
    <label for="password">Password</label>
    <input id="password" type="password" name="password" autocomplete="current-password">
  </div>

  <input type="submit" value="Sign in">
</form>

