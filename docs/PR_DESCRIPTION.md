## Summary

Visitors previously received active student credentials through open registration. Guest applicants now submit a pending application at /enroll; only Registrar/admin approval provisions the account and queues login instructions.

- Add guest applications with private document uploads and recorded reviewer decisions.
- Provision the user/student/academic application/clearance and encrypted mail outbox in one transaction; repeated approvals do not duplicate accounts.
- Remove public registration, add registrar review and credential reissue, and require a first password change on both API and Vue UI.

## Validation

- [x] Backend tests — 18 passed, 173 assertions on PHP 8.4.12 / SQLite.
- [x] Frontend dependencies installed and production build / TypeScript check passed.
- [ ] Submit as guest; verify no user/token until approval.
- [ ] Approve/reject as Registrar and admin; test forbidden roles and private downloads.
- [ ] SMTP failure/retry, expiry/reissue, first-login API restriction, successful password change and revoked old credentials.
- [ ] PostgreSQL concurrent decision check and browser smoke test of existing student workflows.

## Deployment

Run the additive migration. Configure SMTP, verified sender and HTTPS portal URL; run the Laravel scheduler to drain the outbox. Persist private uploads; set request/upload size limits. Audit existing unenrolled accounts separately. Migration preserves existing accounts. Email delivery is at least once; provider acceptance does not prove inbox delivery.
