# Isolated feature branch and Pull Request: beginner walkthrough

Use VS Code's terminal with PowerShell on Windows. All Git commands below run in your repository ROOT, the directory containing api and frontend. The deliverable was prepared against main commit 553866aa1bf6307d6a0bfb8dc4c00fa064780bf8; if main moved, apply the patch with checks and resolve changes instead of replacing entire folders.

## 1. Get the repository and check your working state

If you have not cloned it:

```powershell
git clone https://github.com/reiy-git/PinaCall.git
cd PinaCall
```

If you already have it, cd into that existing PinaCall folder. Do not clone inside it.

```powershell
git status
git remote -v
git branch --show-current
```

Confirm origin points to reiy-git/PinaCall. If git status is clean, continue. If it shows your unfinished edits, preserve them FIRST:

```powershell
git stash push -u -m "wip: preserve local work before enrollee approval feature"
```

Do not use git reset --hard or delete files to get a clean state. Later, on the original branch, use git stash list and git stash apply to restore your saved work; drop the stash only after verifying it.

## 2. Update main and create your isolated branch

```powershell
git switch main
git pull --ff-only origin main
git switch -c feature/enrollee-approval-flow
```

`--ff-only` stops if local main diverges so you can inspect it; do not force-reset main. If the feature branch already exists, switch to it with git switch feature/enrollee-approval-flow rather than creating another.

## 3. Apply the implementation

Extract the delivery ZIP somewhere OUTSIDE the repository. It contains enrollee-approval.patch, changed-files/, and these guides. Prefer the patch so Git can detect conflicts. Adjust the example path to your extracted package:

```powershell
git apply --check "C:\Users\Kim\Downloads\PinaCall-enrollee-approval\enrollee-approval.patch"
git apply "C:\Users\Kim\Downloads\PinaCall-enrollee-approval\enrollee-approval.patch"
```

`--check` must finish successfully before applying. If it fails because main changed, inspect the affected files and integrate the changes manually from changed-files/. Do not blindly overwrite newer work. changed-files/ contains only the feature's changed/added files, not a complete runnable repository.

## 4. Inspect and run checks

```powershell
git status --short
git diff --stat
git diff --check
git diff
```

Untracked new files appear as ?? and are not included in ordinary git diff until staged. Inspect those in VS Code as well. Confirm .env, vendor/, node_modules/, storage documents, credentials and built dist/ files are absent from the commit.

Follow ENROLLEE_APPROVAL.md for local env setup/migration/mail capture/scheduler. Then run:

```powershell
cd api
composer install
php artisan migrate
php artisan test
cd ../frontend
npm ci
npm run build
cd ..
```

Use the documented browser checklist too. Do not submit a PR claiming checks passed unless you actually ran them. Production migration is performed by the lead/deployment process after review, not by your local tests.

## 5. Stage only the intended files and write conventional commits

From repository root, these paths split backend, frontend and docs into reviewable commits:

```powershell
git add Dockerfile.api api/.env.example api/app/Http/Controllers/AuthController.php api/app/Http/Controllers/EnrolleeController.php api/app/Http/Middleware/AuthenticatePortalToken.php api/app/Models/User.php api/app/Models/EnrolleeApplication.php api/app/Models/ProvisioningEmail.php api/app/Mail/StudentProvisioned.php api/app/Services/ProvisioningMailer.php api/config/enrollment.php api/config/mail.php api/database/migrations/2026_10_13_000001_create_enrollee_approval_pipeline.php api/resources/views/mail/student-provisioned.blade.php api/routes/api.php api/routes/console.php api/tests/Feature/EnrolleeApprovalTest.php
git diff --cached --check
git diff --cached --stat
git diff --cached
git commit -m "feat(enrollment): provision student accounts after registrar approval"

git add frontend/src/App.vue frontend/src/services/api.ts frontend/src/types/index.ts frontend/src/components/auth/LoginModal.vue frontend/src/components/auth/SetPassword.vue frontend/src/components/enrollment/PublicEnrollment.vue frontend/src/components/registrar/EnrolleeReview.vue frontend/src/components/registrar/RegistrarDashboard.vue frontend/src/components/admin/AdminDashboard.vue frontend/vercel.json
git diff --cached --check
git diff --cached --stat
git commit -m "feat(portal): add enrollee application review and first-password screens"

git add docs/ENROLLEE_APPROVAL.md docs/GIT_GITHUB_WALKTHROUGH.md docs/PR_DESCRIPTION.md docs/VALIDATION.md
git diff --cached --check
git commit -m "docs(enrollment): document setup verification and pull request workflow"
git status
git log --oneline origin/main..HEAD
```

If Git asks for your identity, configure your own details (replace placeholders; never use someone else's identity):

```powershell
git config user.name "Your Name"
git config user.email "Your verified GitHub email or GitHub noreply address"
```

Then rerun the failed git commit. To unstage a mistaken file without losing edits: git restore --staged path/to/file.

## 6. Push this branch for the first time

```powershell
git push -u origin feature/enrollee-approval-flow
```

Authenticate with GitHub's prompted browser/credential manager. Do not enter a GitHub account password as a Git HTTPS password. Never paste a token into source files or a remote URL. `-u` establishes upstream; later pushes need only git push. If permission is denied, ask the repository owner for collaborator access, or push the branch to your own fork and open a cross-fork PR targeting reiy-git/PinaCall main.

## 7. Open the Pull Request in GitHub

1. Open https://github.com/reiy-git/PinaCall in your browser.
2. Click the **Compare & pull request** banner. If absent: **Pull requests -> New pull request**.
3. Select **base: main** and **compare: feature/enrollee-approval-flow**. Base receives the work; compare is your feature branch.
4. Review the **Files changed** tab. It should contain the feature files only.
5. Title: **feat(enrollment): require registrar approval before student account creation**.
6. Copy docs/PR_DESCRIPTION.md into the description; fill the actual validation results and any environment limitations.
7. Under **Reviewers**, select your actual lead developer's GitHub username. The repository owner is reiy-git; select them only if they are your lead. Use Reviewer, not merely Assignee. If the reviewer option is unavailable, ask the owner to grant access or request review through the team's usual channel.
8. Click **Create pull request** once tests and self-review are complete, or **Create draft pull request** if checks are still outstanding.
9. Share the PR URL with the lead yourself. Leave merging to the lead developer.

## 8. Make requested revisions on the SAME branch

```powershell
git switch feature/enrollee-approval-flow
# Edit the requested files in VS Code, then rerun relevant tests/build.
git status --short
git diff
# Replace the following example path with each file you intentionally changed.
git add api/app/Http/Controllers/EnrolleeController.php
git diff --cached
git commit -m "fix(enrollment): address registrar review feedback"
git push
```

The existing PR updates automatically. Reply to review comments describing what changed and the checks run. Do not create a second PR for ordinary revisions. Avoid rewriting already reviewed commits unless your lead requests it.

## 9. If main receives updates before merging

Make sure your feature work is committed and git status is clean. Merge current remote main into your feature branch; this preserves reviewed history and needs no force push:

```powershell
git switch feature/enrollee-approval-flow
git fetch origin
git merge origin/main
```

If there are conflicts:

```powershell
git status
```

Open each conflicted file in VS Code, reconcile both sides, remove all <<<<<<<, ======= and >>>>>>> markers, save, then stage only resolved files:

```powershell
git add path/to/resolved-file
# Repeat for each resolved file.
git commit -m "merge: resolve main updates in enrollee approval feature"
```

Replace example paths before running. To cancel the unfinished merge without discarding pre-existing committed work: git merge --abort. After a successful merge, rerun backend tests and frontend build, then git push. If your team requires rebase instead, follow the lead's policy; rebasing a published branch requires coordinated history rewriting and is intentionally not the default here.

## 10. After the lead merges the PR

```powershell
git switch main
git pull --ff-only origin main
```

Optional local branch cleanup, after verifying the PR is merged:

```powershell
git branch -d feature/enrollee-approval-flow
```

Squash merges can make -d refuse even after GitHub reports merged. Leaving the local branch is safe; do not force-delete it merely to remove the warning.
