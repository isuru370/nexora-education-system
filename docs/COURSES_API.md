# Courses API (staff app)

Only when the institute has the `courses` feature (`GET /api/features` → `data.courses`, also `features.courses` in the login and `/api/me` responses). Otherwise every endpoint returns **404**.

Auth: `Authorization: Bearer <token>`. Roles: ADMIN, BRANCH_ADMIN, USER (cashier). Teachers get 403.
Responses: `{ "success": true, "message": "...", "data": { ... } }`. Rule errors: **422** `{ "message", "errors": { field: [..] } }`. Records of another branch: **404**.

`code` = student ID or (temporary) QR code.

## Course Attendance button

| | |
|---|---|
| `GET /api/courses/sessions?date=YYYY-MM-DD` | The day's class days (default today): `id, course_name, course_code, date, start_time, end_time, hall, status, can_mark, present_count, students_count` |
| `POST /api/courses/sessions/{id}/attendance` `{code, mark_method?: qr_mobile\|manual_mobile}` | **201** marked / **200** `already_marked: true`. Returns `student`, `session` (status becomes `ongoing`), `fees` (balance, overdue, next_installment - show a warning if unpaid), `attendance` (attended / held / %) |
| `GET /api/courses/sessions/{id}/report` | Present / absent: `summary {students, present, absent, percentage}`, `present[]`, `absent[]`, **`share_text`** (WhatsApp message), `teachers[] {name, mobile, whatsapp_url}` - open `whatsapp_url` to send the report to that teacher, or share `share_text` with any app |

Class day `status`: `scheduled` → `ongoing` (first attendance) → `completed` (after end time); `cancelled`. Only `can_mark: true` days take attendance.

## Course Payment button (and enrolling)

| | |
|---|---|
| `POST /api/courses/student` `{code}` | `student`, `enrollments[]` (each with `course`, `payment_plan`, `fees {total_fee, standard_fee, special_fee, fee_reason, paid, balance, overdue, next_installment, installments[]}`), `available_courses[]` (to enroll), `payment_methods[]` |
| `POST /api/courses/{course}/enroll` `{code, payment_plan: installment\|full, special_fee?, special_fee_reason?}` | **201** `enrollment`. Special fee needs a reason; 0 = free |
| `POST /api/courses/enrollments/{id}/pay` `{amount, payment_method?: cash\|card\|bank_transfer\|online\|cheque\|other}` | **201** `receipt` (print it) + updated `enrollment`. Amount 1 … balance; suggest `fees.next_installment.remaining` |
| `GET /api/courses/payments/{id}/receipt` | `receipt` again (reprint) |
| `GET /api/courses` | Active courses: `code, name, full_fee, one_time_fee, installment_count, installment_amount, schedule, days[]` |

### Receipt (58mm)

`institute_name, branch, title ("COURSE PAYMENT"), receipt_number, paid_at, student_id, student_name, course_code, course_name, payment_method, amount, special_fee_reason, course_fee, total_paid, balance, next_due_date, next_due_amount, taken_by, status`

Receipt numbers share the counter with class payments (`REC-000123`).
