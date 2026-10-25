# SAMS attendance + ArcFace face login

The online CBT hub pulls every SAMS-registered student, passport photo and ArcFace template. Offline halls receive the same records through existing hub/leaf sync.

## Attendance (SAMS) side

Copy these files from this repository into the TSU-SAMS app:

1. `docs/sams-export/CbtSyncController.php` → `app/Controllers/CbtSyncController.php`
2. Register the GET route in `public/index.php`:

```php
use App\Controllers\CbtSyncController;
$router->get('/api/cbt-sync/students', [CbtSyncController::class, 'students']);
```

3. In SAMS **Admin > Settings**, set **CBT sync token** to a long random secret. The GET endpoint is token-auth only (CSRF is not applied to GET).

The export decrypts `face_profiles.descriptor_enc` with the SAMS app key and returns a JSON array of 512-d floats. Photos are base64 of `public/uploads/passports/...`.

## CBT hub

1. Admin > Settings > **Attendance (SAMS) sync**
2. SAMS base URL (no trailing slash), the same sync token, enable pull
3. Face API URL: remote FastAPI when online, or `http://127.0.0.1:9000` on an offline hall
4. Enable **student face login** when templates have been imported
5. Click **Sync from SAMS now**, or run:

```bash
php bin/sams-sync.php
php bin/sams-sync.php --full
```

Hub-only. Leaves skip with `reason=leaf`.

## Offline halls

Students, `face_profiles` and `student_files` (photo bytes) sync like other CBT tables. After pull, photos are restored under `assets/uploads/student_photos/`.

Do **not** copy the hub Face API URL to a leaf. Each node keeps its own `face_api_url` / `face_timeout`. Face login enablement and match threshold follow the hub.

After upgrading an existing install, re-run sync triggers so the new tables are captured:

```bash
php sync/install.php
```

## Face login

When enabled, students see **Sign in with face** on `student/login.php`. The camera captures a JPEG; CBT calls FastAPI `/extract` then cosine-matches `face_profiles` (`/compare`, with local cosine fallback).

Point `face.url` / **Face API URL** at the InsightFace service (`buffalo_l`, default `http://127.0.0.1:9000`).
