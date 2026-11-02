# Storage: S3-compatible object storage

The private disk (avatars, private files) defaults to the local driver
and is served through signed URLs (`FileController`). Switching to
S3-compatible storage wires into the same abstraction — no application
code changes.

## 1. Choose a provider

Any S3-compatible service works: AWS S3, Cloudflare R2, Backblaze B2,
DigitalOcean Spaces. You'll need: bucket, region (or endpoint), access
key and secret.

## 2. Environment

```
FILESYSTEM_DISK=private        # already the default
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=auto        # R2/Spaces/B2 use their own value
AWS_BUCKET=your-private-bucket
AWS_ENDPOINT=https://<accountid>.r2.cloudflarestorage.com   # R2-style
AWS_USE_PATH_STYLE_ENDPOINT=true
```

`config/filesystems.php` already maps the `private` disk to the `s3`
driver with these env values — change the default envs and the disk
follows.

## 3. Swap the driver

In `config/filesystems.php`:

```php
'private' => [
    'driver' => env('PRIVATE_DISK_DRIVER', 'local'), // 's3' in production
    'root' => storage_path('app/private'),
    // keep the s3 keys below as-is when using object storage
],
```

Or point `private` at the existing `s3` disk directly. Either way the
rest of the app is unaffected: avatars still upload and serve the same
way, because FileController redirects to a provider-signed temporary URL
when the driver is `s3`.

## 4. Verify

```bash
php artisan tinker --execute='Storage::disk("private")->put("health.txt","ok"); echo Storage::disk("private")->url("health.txt");'
```

Then upload an avatar from the profile page — it should serve through a
short-lived signed URL.

## Storage quota integration (usage metering)

Plan quotas already include a `storage.mb` metric. To count uploads
against it, record usage at upload time:

```php
app(App\Modules\Entitlements\Services\EntitlementService::class)
    ->record($organization, 'storage.mb', $megabytes);
```

and block oversized uploads with `overLimit($organization, 'storage.mb')`
before accepting the file. The usage bar in Billing reads the same
metric, so the customer sees the total in real time.

## Backups

The private disk holds user data — back it up alongside the database.
With an object-storage provider, enable provider-side versioning/lifecycle
rules instead of filesystem snapshots.