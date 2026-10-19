# Online / Offline Synchronisation

The same CBT application can run as an **online hub** and as one or more **offline examination servers** (leaves). Exam work continues without internet. When a connection is detected, the offline server pushes local data and pulls hub data in the background.

## How it works

```
Offline exam hall (leaf)                    Online server (hub)
------------------------                    -------------------
Students take exams                         Source of restrictions
Answers / results stored locally            Receives pushes from leaves
Background worker / request shutdown        Relays changes to other leaves
HMAC-signed HTTP API  <------------------>  Super-admin node console
```

Changes are captured by database triggers into `sync_change`, so every existing form and AJAX endpoint is covered without rewriting business logic.

## What is not implemented

Remote **self-destruct / wipe all files** is not available. The hub can **suspend** or **retire** a node. Suspended nodes lock users out until the hub re-enables them. Retire stops sync and locks the node. Neither action deletes files or exam data.

Project file updates from the hub are **signed ZIP packages**. The offline super-admin must download, verify, and approve them locally. Existing files are backed up first. Paths under `config/`, `storage/` and `.git/` are never replaced, and nothing is deleted.

## Hub setup (online)

1. Import `sql/schema.sql` into MySQL/MariaDB.
2. Create `config/node.local.php` from `config/node.local.example.php`:

```php
<?php
return [
    'role' => 'hub',
    'name' => 'Online CBT',
    'app_url' => 'https://cbt.example.edu',
    'db_host' => 'localhost',
    'db_name' => 'cbt_system',
    'db_user' => 'cbt_user',
    'db_pass' => 'change-me',
];
```

3. Install the sync schema and triggers:

```bash
php sync/install.php --role=hub --name="Online CBT"
```

4. Register each offline server. The secret is printed once:

```bash
php sync/register_node.php --name="Offline Campus A"
```

Paste the printed snippet into the leaf's `config/node.local.php`.

5. Open **Admin > Offline Nodes** as super-admin to set restrictions, suspend, retire, or queue a signed ZIP update.

## Leaf setup (offline exam hall)

1. Deploy the same codebase and import `sql/schema.sql`.
2. Put the snippet from `register_node.php` into `config/node.local.php`. Set `hub_url` to the public URL of the hub (no trailing slash).
3. Install the sync schema:

```bash
php sync/install.php --role=leaf --name="Offline Campus A"
```

4. Run one sync (or wait for the background hook):

```bash
php bin/sync-once.php
```

5. Optional worker for busy exam days:

```bash
php bin/sync-worker.php
```

A systemd unit or supervisor should restart that process. Web requests also sync after the response is sent, so the worker is optional.

### Cron (optional)

```
*/1 * * * * php /var/www/cbt-system/bin/sync-once.php >/dev/null 2>&1
```

## Restrictions the hub can set

| Capability | Effect on the offline server |
|---|---|
| Student editing | Add / edit / delete / promote / reset password blocked |
| Question editing | Question bank and composite questions blocked |
| Exam management | Create / assign / force-end / extend blocked |
| Exam access | Students cannot start exams |
| Result viewing | Admin result pages blocked |
| Result editing | Publish / edit / delete results blocked |
| File updates | Signed packages cannot be downloaded |
| Suspend | All local web access locked (HTTP 423) until the hub reactivates |
| Retire | Sync rejected; node stays locked |

Restrictions are stored in `storage/sync/policy.json` on the leaf and applied on every request.

## File updates

1. On the hub, upload a ZIP on **Offline Nodes**.
2. On the leaf, open **Project Updates**, download (checksum + HMAC verified), then **Approve & apply**.
3. Backups land in `storage/sync/backups/`.

## Commands

```bash
php sync/install.php --role=hub|leaf
php sync/register_node.php --name="Offline Campus A"
php bin/sync-once.php
php bin/sync-once.php --full
php bin/sync-worker.php
php bin/sync-prune.php
php bin/policy.php --show
php bin/policy.php --clear-suspension
```

`--clear-suspension` only unlocks the local policy file. The next successful sync will re-apply the hub policy.

## Security

- Every API call is HMAC-SHA256 signed over the raw JSON body.
- Timestamp window is 5 minutes; nonces prevent replay.
- Retired nodes are rejected.
- `config/`, `storage/` and `.git/` are never overwritten by a package.
- Package apply requires a local super-admin click.

## Empty leaf vs cloned database

- A **cloned** database (same students/exams already present) starts from the hub's current cursor and only receives later changes.
- An **empty** database pulls the hub's backfilled history so it can become a working copy.

Re-run `php sync/install.php` on the hub after importing new seed data so that history is backfilled for new leaves.
