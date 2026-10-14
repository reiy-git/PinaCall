# Public admission wizard handoff

Implemented on `feature/public-admission-wizard`, based on `feature/enrollee-approval-flow`.
After `git fetch origin`, the approval-flow commit remains absent from `origin/main`. This branch includes the prior approval work; use `feature/enrollee-approval-flow` as the PR base while it remains unmerged.
No commit, push, merge, deployment, live migration, or database reset was performed. The local Compose override is unchanged and excluded from the staging commands below.

## Behavior

- `/enroll` now has applicant, academic, document, and review steps followed by a confirmation with the application reference. State stays in memory between steps, including files. Reloading the page starts a fresh form.
- Inputs have labels, field errors, invalid-field focus, keyboard controls, mobile layouts, and a focused confirmation. Final submission is guarded against repeated clicks. API validation and network errors retain the form.
- Documents show filenames and sizes, support removal and replacement, and retain the existing server MIME/count/size checks. No official institutional requirements were invented. Programs and document guidance come from `/api/enrollees/options`, backed by `api/config/enrollment.php`. Guidance can be set with `ENROLLMENT_DOCUMENT_GUIDANCE`.
- Optional `previous_school` is validated (string, maximum 255 characters), stored on the guest application, shown to reviewers, and copied into the approved student application. Existing records remain nullable.
- Authentication responses include a restricted `approved_admission` relation based on the approved guest application's `user_id`. Private documents are excluded.
- Provisioned students see an admission summary and Subjects & sections instead of a duplicate initial-admission form, progress rail, and credential-submission prompt. The existing section workspace, clearance, grades, and document services remain available.
- An explicit continuing-student action retains old/irregular/shiftee/returnee applications. Students without an approved guest-account relationship retain their existing workflows. Direct initial `new` or `transferee` submissions from provisioned accounts return HTTP 409 before mutating student records.
- Removed the hard-coded subject VERIFIED status; admission does not certify subject enrollment, payment, or clearance.
- Existing transactional/idempotent approval, credential outbox, password gate, token revocation/expiry, private review authorization, and public registration closure remain intact.

## Modified files

Backend:

- `api/app/Http/Controllers/AuthController.php`
- `api/app/Http/Controllers/EnrolleeController.php`
- `api/app/Http/Controllers/EnrollmentApplicationController.php`
- `api/app/Models/User.php`
- `api/config/enrollment.php`
- `api/routes/api.php`
- `api/database/migrations/2026_10_14_000001_add_previous_school_to_enrollee_applications.php` (new)
- `api/tests/Feature/EnrolleeApprovalTest.php`

Frontend:

- `frontend/src/components/enrollment/PublicEnrollment.vue`
- `frontend/src/components/student/StudentDashboard.vue`
- `frontend/src/components/registrar/EnrolleeReview.vue`
- `frontend/src/components/Sidebar.vue`
- `frontend/src/services/api.ts`
- `frontend/src/types/index.ts`

Documentation: `docs/public-admission-wizard.md` (this file).

Inspected but unchanged: App.vue, navigation service, unused EnrollmentForm/ApplicationCard components, auth middleware, EnrolleeApplication/StudentApplication/Student/Clearance models, relevant migrations, routes, and existing workflow tests. The unused components are not mounted by the active dashboard.

## Verification

- Current-source Docker backend suite: **22 tests, 246 assertions passed**, no PHPUnit warnings.
- Current-source Docker frontend `npm run build`: **passed**, including Vue/TypeScript checks and Vite production compilation.
- `git diff --check`: passed.
- Tests explicitly used `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, empty `DB_URL`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, and `MAIL_MAILER=array`.
- Current source directories were mounted read-only over existing container images. Initial image-only runs were discarded because those images contained old source.
- New coverage checks guest field persistence/validation, document size/count limits, configured public options, duplicate initial submission rejection, continuing-category authorization, private-path exclusion, and live public throttling. Existing provisioning, password, clearance, academic, and document workflow tests also pass.
- Browser automation was unavailable. Interactive behavior and visual accessibility still require the manual checks below.
- The repository-requested Laravel Boost bootstrap was attempted but package access failed. No Composer dependency changes were made.

## Migration

For local PHP with the intended database configured:

```powershell
Set-Location C:\Users\Kim\PinaCall\api
php artisan migrate
```

For Docker, rebuild the images so they contain these changes, then run the additive migration:

```powershell
Set-Location C:\Users\Kim\PinaCall
docker compose build api frontend
docker compose run --rm --no-deps api php artisan migrate
```

Do not use `migrate:fresh`. The migration adds one nullable column and does not change existing admissions or accounts.

## Manual checklist

1. Open `/enroll` at mobile and desktop widths. Try empty/invalid fields, keyboard navigation, and invalid year levels; verify the first invalid field gets focus.
2. Enter details and previous school. Add PDF/JPEG/PNG files; move forward/back and use review Edit controls. Verify all values and files remain selected, sizes are visible, and remove/replacement works.
3. Try zero files, six files, oversized files, and unsupported types. Verify progression is blocked. Try an already-used email and verify its server error appears at the email field without clearing data.
4. Submit once, including rapid repeated clicks. Verify one PENDING guest record, a reference on confirmation, and no new user or token. Simulate a network error and retry with form state retained.
5. Review at `/admin/enrollees`, inspect previous school and private documents, approve twice, and verify one provisioned account/outbox record. Follow the credential email and forced password-change flow.
6. Sign in as the approved student. Verify the admission summary, absence of the duplicate initial form/prompts, and access to subjects/sections, grades, clearance/payment tracking, and document requests. Admission alone must leave clearance and subject enrollment incomplete.
7. Send a direct POST to `/api/applications` with category `new` or `transferee` as that student; expect 409 and unchanged student status/application count. Use the explicit continuing action to verify old/irregular/shiftee/returnee workflows, and check an existing legacy student still has their original application workflow.

## Suggested commit and PR

Commit/title: `feat(enrollment): move initial admission into public application wizard`

PR description:

> Approved guest applicants were offered a second admission form after signing in, allowing their active record to be changed back to pending. Move initial admission details and uploads into a public, validated wizard with review and reference confirmation. Use the approved guest/account relationship for the dashboard summary and reject duplicate initial submissions on the API, while retaining continuing-student applications and academic services.
>
> Add a nullable previous-school migration and configured public application options. Preserve approval provisioning, private review access, credential delivery, and forced password change.
>
> Validation: 22 backend tests / 246 assertions passed with isolated SQLite/array services; Vue/TypeScript production build passed. Browser checks remain manual. Run the additive migration before using the updated application.

Use the prior feature branch as the PR base if it remains unmerged, or `main` once the prior feature is merged. Confirm the remote state first.

Exact PowerShell commands to stage only the delivered files, commit, and push:

```powershell
Set-Location C:\Users\Kim\PinaCall
git switch feature/public-admission-wizard
git status --short
$admissionFiles = @(
  'api/app/Http/Controllers/AuthController.php'
  'api/app/Http/Controllers/EnrolleeController.php'
  'api/app/Http/Controllers/EnrollmentApplicationController.php'
  'api/app/Models/User.php'
  'api/config/enrollment.php'
  'api/routes/api.php'
  'api/database/migrations/2026_10_14_000001_add_previous_school_to_enrollee_applications.php'
  'api/tests/Feature/EnrolleeApprovalTest.php'
  'frontend/src/components/enrollment/PublicEnrollment.vue'
  'frontend/src/components/student/StudentDashboard.vue'
  'frontend/src/components/registrar/EnrolleeReview.vue'
  'frontend/src/components/Sidebar.vue'
  'frontend/src/services/api.ts'
  'frontend/src/types/index.ts'
  'docs/public-admission-wizard.md'
)
git add -- $admissionFiles
git diff --cached --check
git diff --cached --stat
git commit -m "feat(enrollment): move initial admission into public application wizard"
git push -u origin feature/public-admission-wizard
```
