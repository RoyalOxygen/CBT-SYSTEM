# SAMS files to copy into TSU-SAMS

1. Copy `CbtSyncController.php` to `app/Controllers/CbtSyncController.php`.
2. In `public/index.php` add:

```php
use App\Controllers\CbtSyncController;
$router->get('/api/cbt-sync/students', [CbtSyncController::class, 'students']);
```

3. In `app/Controllers/AdminController.php` `settingsSave()`, add to `$known`:

```php
'cbt_sync_token' => trim((string)($_POST['cbt_sync_token'] ?? '')),
```

4. In `views/admin/settings.php`, add `cbt_sync_token` to `$known` and this card before Other settings:

```html
<div class="card">
  <h2 class="h6">CBT export</h2>
  <p class="muted small">Token for GET /api/cbt-sync/students (CBT hub pull).</p>
  <input class="form-control" name="cbt_sync_token" value="<?= e($v['cbt_sync_token'] ?? '') ?>">
</div>
```

Set the same token on the CBT hub under Admin > Settings.
