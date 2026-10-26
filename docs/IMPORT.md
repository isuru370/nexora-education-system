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

## 3. Students

CSV with the old `students` columns and the grade **name** in `grade`:

```
custom_id, fname, lname, mobile, email, whatsapp_mobile, nic, bday, gender, address1, address2, address3,
guardian_fname, guardian_lname, guardian_nic, guardian_mobile, is_active, img_url, grade, admission,
student_school, created_at, updated_at
```

```bash
php artisan nexora:import-students /path/students.csv --images=/path/old-uploads --dry-run
php artisan nexora:import-students /path/students.csv --images=/path/old-uploads
```

- The **Custom ID is kept** and `permanent_qr_active` is set, so the old cards (QR = Custom ID) still scan.
- `fname` → full name, `lname` → initial name. Grade by name (`2027 A/L Grade` = `2027 A/L`, `Grade 10` = `10`);
  if a grade is missing nothing is imported and the `nexora:grades add ...` line to run is shown.
- Phones: the first of `07X / 07Y`, `9477...` → `077...`, `77...` → `077...`. Guardian mobile falls back to the mobile.
- Birthday: `2008-05-17` or `05/17/2008` (old system: month first). A date that does not exist is left empty.
- Photo: `storage/app/public/uploads/<file name from img_url>`, copied from `--images` when needed; without it the
  student gets the default `uploads/male.png` / `uploads/female.png` (same as a new registration).
- A NIC already used by another student is left empty. All of these are listed in
  `storage/logs/import-students-problems.txt`.
- `--branch=<code>` when there is more than one branch (default: the only branch / `MAIN`).

## 4. Teachers

CSV with the old `teachers` columns, the bank branch as **codes** (old ids are different):

```
custom_id, fname, lname, email, mobile, nic, bday, gender, address1, address2, address3, is_active,
graduation_details, experience, account_number, bank_code, branch_code, branch_name, created_at, updated_at
```

```bash
php artisan nexora:import-teachers /path/teachers.csv --dry-run
php artisan nexora:import-teachers /path/teachers.csv
```

- Custom ID kept (`CT0001`, `SAT0001`); new teachers continue from the highest `SAT` number.
- `Mr. Kamal` + `Perera` → full name `Mr. Kamal Perera`, initials `Mr. K. Perera` (max 20).
- Email and NIC must be unique: a teacher entered twice is imported once (the active row).
- Linked to the branch (`teacher_branches`). No login is made - add it from the web (Teachers → login).
- Problems: `storage/logs/import-teachers-problems.txt`.

## Next

Subjects and classes - separate commands.
