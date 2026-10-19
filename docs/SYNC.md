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

1. Copy the project onto the online server and make `config/`, `storage/`, `logs/` and `assets/uploads/` writable.
2. Open the site in a browser. The installer starts automatically.
3. Choose **Online server**, enter the database login and first super-admin account, then click **Install now**.
4. Sign in as super-admin and open **Admin > Offline Nodes**.
5. Click **Add exam hall**, name it, and copy the pairing code. That code is shown once.

## Leaf setup (offline exam hall)

1. Copy the same project onto the exam-hall computer.
2. Open the site in a browser and choose **Offline exam hall**.
3. Enter that computer's database login and a local admin account.
4. Paste the pairing code from the online server, then click **Install now**.
5. If the hall is offline during install, setup still finishes. Sync starts automatically when the internet is available.

Optional worker for busy exam days:

```bash
php bin/sync-worker.php
```

A systemd unit or supervisor should restart that process. Web requests also sync after the response is sent, so the worker is optional. The CLI commands below remain available for recovery.

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

## Commands (optional / recovery)

New deployments should use `/install/` in a browser. These remain for recovery:

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
