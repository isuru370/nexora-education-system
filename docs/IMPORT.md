# Moving an institute from the old system

Run these on the **institute's server** (inside the app container), after `php artisan nexora:deploy`.
Every command can be run again: what is already there is left as it is.

## 1. Grades

```bash
php artisan nexora:grades                                    # list
php artisan nexora:grades add "2025 A/L" "2026 A/L" "2027 A/L" "2028 A/L" ALL
```

Grades 1-11 come with the first install. Old names like `2027 A/L Grade` are matched to `2027 A/L` when students are imported.

## 2. Quick photos (old `quick_photo_details`)

1. Copy the old image files to the server, e.g. into `storage/app/public/uploads/`
   (or any folder, then give it with `--images`).
2. Upload the `quick_photo_details` dump (`.sql` from HeidiSQL, or a `.csv` with
   `custom_id, quick_img, is_active`).
3. Check first, then import:

```bash
php artisan nexora:import-photos /path/quick_image.sql --images=/path/old-uploads --dry-run
php artisan nexora:import-photos /path/quick_image.sql --images=/path/old-uploads
```

- The picture is `storage/app/public/uploads/<file name from the old URL>` (old names are
  numbers like `1727429708.jpg`; new uploads get random names, so they never clash).
- The old photo id is kept (`CQS00001`). Photos not used in the old system (`is_active = 1`)
  can be picked for new registrations.
- Rows whose picture is not found are skipped and listed in `storage/logs/import-photos-problems.txt`;
  copy the missing files and run again.

## Next

Students (with their photo and QR = Custom ID), teachers and classes - separate commands.
