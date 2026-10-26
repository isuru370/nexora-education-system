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

## 5. Admission payments

After the students. CSV: `old_id, custom_id, admission_name, admission_amount, amount, paid_at`.

```bash
php artisan nexora:import-admissions /path/admission_payments.csv --dry-run
php artisan nexora:import-admissions /path/admission_payments.csv
```

- Admission types matched by name (case does not matter); missing ones are added with `admission_amount`.
- The **paid** amount and date are kept (status paid, cash). Receipts get the next `REC-` numbers in date order.
- The same student paying the same admission twice on one day (double entry) is imported once.
- No SMS. The note is `Imported from old system #<old id>`, so running again adds nothing.
- Problems: `storage/logs/import-admissions-problems.txt`.

## 6. Classes, fees, teacher % and enrollments

After the teachers and students. Two CSVs:

```
classes.csv:      old_class_id, old_fee_id, class_name, teacher_custom_id, teacher_percentage, subject, grade,
                  is_active, is_ongoing, category, fee, created_at, option_label (optional)
                  - one row per class + category
enrollments.csv:  old_id, custom_id, old_fee_id, is_active, is_free_card, enrolled_at, updated_at
```

```bash
php artisan nexora:import-classes /path/classes.csv --enrollments=/path/enrollments.csv --dry-run
php artisan nexora:import-classes /path/classes.csv --enrollments=/path/enrollments.csv
```

- Class: name, teacher (by Custom ID), subject, grade, active / ongoing and date as in the old system;
  offline, Sinhala medium. Missing subjects are added; a missing grade stops the import.
- Teacher % → `class_payment_configs` (institute % = 100 − teacher %, organizer 0).
- Categories are the system ones (Theory / Revision / Paper Class). Each class + category → `class_category_fees`
  with the fee option **"Theory Only"** / **"Revision Only"** / **"Paper Class Only"** (the old fee). Another fee in
  the same category is **"Special"**; `option_label` gives a name (the old category `Theory+Revision` comes in as
  the Theory option **"Theory + Revision"**).
- Enrollments: student by Custom ID, active / free card / date kept. An inactive enrollment gets `left_at` =
  the old row's `updated_at` (as when deactivated from the web), so reports stop counting it. A student twice in
  the same class + category is enrolled once (the active row).
- Safe to run again (imported fee options and enrollments are marked in their note). Problems:
  `storage/logs/import-classes-problems.txt`.

## 7. Class fee payments

After the classes (with enrollments). CSV: `old_id, custom_id, old_enrollment_id, old_fee_id, payment_month, amount, paid_at, status`.

```bash
php artisan nexora:import-payments /path/class_payments.csv --dry-run
php artisan nexora:import-payments /path/class_payments.csv
```

- Each payment goes to the student's imported enrollment (a double-entry enrollment's payments go to the one kept).
  Month (`2025 Feb` / `2025-02`), amount and paid date are kept; cash, completed; receipts get `REC-` numbers in date order.
- The teacher / institute split is made exactly as for a payment from the web (class %).
- One payment per enrollment and month (the system's rule): a second one is a double entry, skipped and listed.
  `status = 0` (cancelled in the old system) is skipped.
- No SMS. Safe to run again: with a newer backup only the new payments are added.
- At the end the **old system and the database are compared month by month** - every month must say `OK`.

## 8. Teacher salaries settled in the old system

After the payments. CSV (old teacher payments): `old_id, teacher_custom_id, reason_code, reason, amount, paid_at, salary_month, status`.

```bash
php artisan nexora:import-teacher-salaries /path/teacher_payments.csv --settled-until=2026-08 --dry-run
php artisan nexora:import-teacher-salaries /path/teacher_payments.csv --settled-until=2026-08
```

- Every month up to `--settled-until` in which a teacher earned (or was paid) becomes a **paid** `teacher_salaries`
  row: gross = earnings in the new system, net = what the old system paid (salary + advances), with a note. So the
  new system never pays those months again. Months with earnings but no old payment are closed too, and listed.
- After `--settled-until`, only the months the old system already paid are closed; the rest stay open and are paid
  from the new system.
- Both systems count a payment in the month it was **paid** (checked: 232 of 244 old salary payments match exactly).
- Problems / open months: `storage/logs/import-teacher-salaries-problems.txt`.

## Switching over (go-live day)

1. Stop taking payments in the old system; take the final backup and build the CSVs from it.
2. Run the imports again in the same order - each one only adds what is new (students, enrollments, payments,
   teacher payments). Check that the payments check says `All months match`.
3. Start using the new system.
