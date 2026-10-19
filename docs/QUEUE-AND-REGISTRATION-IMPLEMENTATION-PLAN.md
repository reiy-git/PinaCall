# PINACALL Queue and Student Verification Plan

## Goal

Reduce physical congestion at the Cashier and Registrar while ensuring that only
Registrar-approved applicants can use the student portal. The implementation
uses the existing Laravel API, Vue dashboard, bearer-token authentication,
document-request workflow, and Registrar application review.

## Current findings

- Queue enrollment and queue-status lookup were public and relied on a student
  number, which is not a secret.
- Marking a document ready automatically created a Registrar queue token,
  whether or not the student had arrived. This creates "ghost" queue entries.
- Queue-management routes required a login but did not consistently require
  Cashier/Registrar staff authorization for the requested service stage.
- The StudentQueueStatus, LobbyDisplay, and KioskCheckIn components were not
  connected to the main dashboard. The public lobby also exposed a student
  number and allowed status lookups by student number.
- The visible New student form called a removed `/auth/register` endpoint and
  allowed a password to be chosen before Registrar approval. The separate
  application-and-approval workflow already correctly withholds accounts until
  approval.

## Queue flow: reusable physical QR tokens

The queue uses reusable, durable physical tokens placed in a visible holder
between or near the Cashier/Registrar counters. Students perform normal
check-in on their own phones; staff only collect and reset the token while
completing the service. This avoids kiosks, tablets, printed one-time slips,
WebSockets, and geolocation.

Each token has a service-specific QR code and a small printed staff reference
(for example, `K7M4`). The reference is not a queue number. The QR contains an
opaque random secret; it contains no student, request, service position, or
predictable token sequence. The server assigns the actual daily queue ticket
only after a successful authenticated check-in, for example `Q-018` or
`R-006`.

Staff print or reprint the durable QR from **Queue Management → Token Status ·
Print QR**. They select a physical token, then print its QR for the matching
service counter. The QR opens `/queue/check-in` on the configured public app
origin; the physical token's secret is URL-encoded as a query parameter.
Students do not print the reusable token QR; after scanning and checking in,
they can print their assigned queue number.

For local phone testing, both the frontend and API must be reachable from the
phone on the same network. Set `VITE_PUBLIC_APP_URL` to the computer's LAN URL,
use the Vite `/api` proxy (configured by `VITE_API_PROXY_TARGET`), and start
Vite with its network host enabled. Do not print a QR whose URL contains
`localhost` or `127.0.0.1`; those addresses point to the scanning phone itself.
For production on Vercel, set the variables from
`frontend/.env.production.example` in **Project Settings → Environment
Variables → Production**, then redeploy. `VITE_PUBLIC_APP_URL` must be the
public HTTPS Vercel origin (currently `https://pina-call.vercel.app`).
`VITE_API_BASE_URL=/api` is routed to Render by `frontend/vercel.json`. These
`VITE_` values are embedded at build time, not read when a QR is scanned.

Deploy the Laravel API changes to Render as well. In the Render shell, apply
the new schema and create reusable QR token records with:

```sh
php artisan migrate --force
php artisan db:seed --class=PhysicalQueueTokenSeeder --force
```

If the API is called directly from another frontend origin instead of through
the Vercel `/api` rewrite, configure Laravel's `FRONTEND_URL` to that exact
HTTPS origin. Do not use the development-only Vite host or proxy as the
production web server.

1. Student submits a document request while signed in. Every currently listed
   document type follows the payment-first route; the request starts as
   `waiting_for_payment`. No queue ticket is created yet.
2. At the Cashier area, the student takes one available Cashier token, scans
   its printed QR using their own phone, signs in (without needing to scan
   again after sign-in), selects an unpaid `waiting_for_payment` request, and
   confirms check-in.
3. In one transaction, the server verifies the signed-in student, the token's
   availability, the request's eligibility, and the absence of another active
   ticket. It then assigns the physical token, creates the daily Cashier queue
   ticket, and records the activation attempt.
4. The student keeps the physical token while waiting and checks their ticket
   status, people ahead, and called window from the authenticated dashboard.
   The student must bring the same token to the window when called.
5. Cashier staff call/re-call the next queue ticket, verify the student and
   request, receive payment, collect the matching physical token, and choose
   **Complete payment + token returned**. The returned token is reset for reuse.
   If it is not physically returned, staff choose **Complete payment + token
   missing**; it remains blocked. The request status becomes `processing` once
   payment is confirmed.
6. The Registrar tracks preparation online as `processing`, then marks the
   request `ready_for_pickup`. The student can see that status in the portal
   and wait until ready before returning. Payment does not create a Registrar
   ticket.
7. When the student returns to the Registrar, they repeat the self-check-in
   flow with a Registrar token. Only a paid `ready_for_pickup` request is
   eligible. The old Cashier hold is completed when the request is marked
   ready. Registrar verifies identity in the Registrar queue, releases the
   document only after calling the student, collects the token, and records it
   as returned or missing.

## Manual online-queue test case

**Scenario:** A student pays for a document at the Cashier, waits for
preparation, then joins the Registrar queue to collect it.

**Preconditions:** Seeded demo accounts and physical tokens; an active student
account with an eligible document request; Cashier and Registrar staff signed
in to their respective dashboards.

1. Sign in as the student and submit a one-copy document request.
2. In the Cashier dashboard, open **Queue Management → Print Token QR**, select
   an available token, and print/reprint its QR. The student scans it with
   their phone, signs in without rescanning, chooses the new unpaid request,
   and joins the queue.
3. Confirm the student dashboard shows a `Q-nnn` cashier ticket and can print
   that queue number. As Cashier,
   call it, receive payment and the physical token, then complete payment.
4. As Registrar, mark the paid request ready for pickup. The cashier hold is
   now completed; no Registrar ticket is created automatically.
5. As the student, scan an available Registrar token QR, select the ready
   request, and join. Confirm the student dashboard shows an `R-nnn` Registrar
   ticket.
6. As Registrar, call the ticket, verify the returned Registrar token, and
   complete the release. Confirm the request is `released` and the student has
   no active queue ticket.

**Observed local run:** Fiona Reyes, request `#13`, Cashier `Q-001` / `C-01`,
then Registrar `R-001` / `R-01`; final document status `released`, active
queue empty.

### Practical presence and accountability limits

Taking and later handing back a physical token is a practical on-site
check-in gate, not guaranteed location or identity proof. QR images can be
copied, so the system must not disclose token availability, queue order, or
assignment details when a scan fails. A copied QR is useful only if the token
is currently available and the remote user successfully claims it before an
on-site student; daily attempt limits and staff-only reset make this
inconvenient rather than impossible.

Students may hold a token while waiting, so casual token hoarding does not
provide extra queue positions: a student can have only one active ticket and
must present the physical token to complete service. The token holder should
remain visible to staff, but routine supervision is sufficient; do not add
unnecessary hardware or elaborate abuse-detection scenarios.

## Student registration and access flow

1. A guest submits the existing application form with required supporting
   documents. Submission creates only a `PENDING` application—never a user,
   student, password, or access token.
2. Registrar reviews the application and documents. Rejecting it does not
   create access. Approval creates the student account and student record
   atomically and queues temporary credentials for delivery.
3. Student signs in only after approval, changes the temporary password, and
   then receives access to student services.
4. API login denies student-role accounts that are not linked to an active
   student record. Server-side authorization is authoritative; hiding a
   dashboard in Vue is not a security control.

## Rules and API boundaries

- Queue check-in and student queue status require a valid portal bearer token
  and an active student record. Never accept caller-supplied student IDs or
  numbers to select the account.
- A physical token has an opaque QR secret, a separate printed staff reference,
  a service stage (`cashier` or `registrar`), and a status. The printed
  reference is only for staff verification/reset and can never activate a
  queue ticket by itself. Store a hash for lookup and an encrypted copy for
  stage-authorized staff QR reprints; never expose the secret in inventory or
  history responses.
- Staff queue reads, calling, recall, no-show, payment, ready-for-pickup,
  release, token reset, missing-token, and disabled-token actions require the
  correct staff role for the queue stage; `admin` is the documented override.
- Cashier entry requires an unpaid `waiting_for_payment` document request.
  Registrar entry requires a paid `ready_for_pickup` document request.
- All currently available document types require Cashier payment first. After
  payment, the request is shown as `processing` while the Registrar prepares
  it online; the student waits for `ready_for_pickup` before joining the
  Registrar queue. Add any future no-fee/online-only document types as an
  explicit policy change rather than allowing students to bypass payment.
- Registrar staff mark a paid document ready for pickup in the fulfillment
  workflow, which closes the prior Cashier `hold` ticket. The student then
  checks in with a Registrar QR token. Final release is only available through
  Registrar Queue Management after the student is called and returns the
  matching physical token.
- If an older released request still has a Cashier `hold` ticket, student queue
  status treats it as historical rather than active. This keeps legacy records
  from masking a later Registrar ticket.
- A student can have only one active queue ticket. A token can have only one
  active assignment. Both checks and queue-ticket creation occur transactionally.
- Tokens progress through `available`, `assigned`, `called`, `returned`,
  `missing`, and `disabled`. `returned` becomes `available` only after staff
  physically receive the matching token and confirm/reset it. `missing` and
  `disabled` tokens stay unavailable until an authorized physical reset.
- Log every activation attempt, assignment, call, return, missing outcome,
  reset, and disable action with token, student, queue ticket, timestamp, and
  staff member where applicable. The last assigned student is an accountability
  record, not automatic proof of intentional loss.
- On the same service day, block a student from the same token after three
  activation attempts and show an alert that it was already used or attempted.
  After five failed attempts for the same token by different students without
  an official reset, lock that token and direct the current holder to bring it
  to staff for physical verification and reset.
- Failed activation responses are generic: "This queue token is unavailable.
  Please use an available token at the service area or ask staff for
  assistance." Do not reveal whether the token is assigned, missing, returned,
  or likely to become available.
- Sequence numbers are allocated transactionally and unique by date and stage.
- The public lobby may show ticket numbers, windows, and counts, but never
  names, student numbers, document details, or personal queue lookups.
- Applications do not provision accounts until Registrar approval; new
  applicants do not choose a password or receive a dashboard session.

## Realtime options

| Option | Cost/operations | Freshness | Recommendation |
|---|---|---|---|
| Short polling (existing stack) | Lowest; no new service or dependency | A few seconds | Use for the release |

Use authenticated short polling only: every 10–15 seconds while a student has
an active ticket, and every 5–10 seconds for staff and optional public display
views. Stop student polling when the ticket ends. The API is the source of
truth; no WebSocket service, broadcast channel, or geolocation capability is
part of this plan. Public displays may show generated queue ticket numbers,
windows, and counts only.

## Database design

Use the existing queue-ticket records for the actual daily queue position and
add three small coordination tables.

| Table | Required purpose and fields |
|---|---|
| `physical_queue_tokens` | One row per durable physical token: `id`, unique printed `reference`, `service_stage`, `qr_secret_hash`, `status`, current/last `student_id`, current daily `queue_token_id`, `last_reset_by_staff_id`, and timestamps. |
| `physical_queue_token_attempts` | One row per authenticated activation attempt: physical token, `student_id`, `service_date`, timestamp, result (`assigned`, `unavailable`, `blocked`), internal reason, and optional daily `queue_token_id`. Index by token/date and student/token/date. |
| `physical_queue_token_events` | Audit events for `assigned`, `called`, `returned`, `missing`, `reset`, `locked`, and `disabled`, with physical token, relevant student/daily ticket, optional staff actor, timestamp, and an optional staff note. |

Generate a long random QR secret once per physical token; store only its hash.
The QR resolves to a check-in route carrying that secret. The API looks up the
token, never exposes the stored secret, and does not provide a token-status
endpoint. Enforce unique active ticket and active token assignment constraints
at the database/transaction level so concurrent scans cannot claim the same
token.

## Delivery steps

1. Keep application submission separate from account creation; replace the
   broken password-based registration UI with the existing reviewed
   application form and deny unverified student logins.
2. Add token, activation-attempt, and audit-event migrations; seed separate
   Cashier and Registrar physical tokens with opaque QR secrets and printed
   staff references. Keep lookup hashes and encrypted copies so authorized
   staff can reproduce the same QR when a token needs reprinting.
3. Move queue check-in/status behind portal authentication and replace the
   public kiosk/student-number flow with QR-token activation. Bind the student
   and selected request from the authenticated session; never accept a student
   ID from the client.
4. Implement transactional activation, the three-attempt same-student rule,
   the five-failed-attempts-different-students token lock, generic failure
   responses, and the daily queue-ticket sequence allocation.
5. Correct the document lifecycle so a paid, ready document creates a
   Registrar queue ticket only when the student activates a Registrar token.
6. Add student ticket/status screens and staff returned/reset, missing, and
   authorized physical-reset actions. The normal called-ticket screen must show
   the expected printed token reference so staff only need to compare it with
   the token handed over. Payment/release remains on hold until that matching
   physical token is returned.
7. Enforce queue-stage staff authorization, protect public queue privacy, and
   use the existing polling pattern for all updates.
8. Run focused backend feature tests and the frontend typecheck/production
   build. Before deployment, apply migrations during a maintenance window and
   verify the flows with test accounts and physical test tokens in staging.

## Verification checklist

- Guest queue check-in/status requests are unauthorized.
- A student cannot check in as another student, check in without an active
  student record, or activate an ineligible document request.
- One successful activation atomically creates one daily ticket and assigns
  one physical token; concurrent scans cannot claim that token twice.
- A student with an active ticket receives that ticket rather than a duplicate.
- The same student is blocked from the same token after three same-day
  attempts; a token locks after five failed same-day attempts by different
  students without an official reset.
- Unavailable/locked/missing token scans return the same generic response and
  do not expose token state, assignment, or queue order.
- Mark-ready creates no Registrar ticket; a paid/ready request receives one
  only after valid Registrar-token activation.
- Completing service with the matching physical token resets it. Completing
  service without it marks the token missing and blocks later activation.
- Authorized staff can reset a missing/locked token only after physical
  verification; all token actions have audit events.
- Staff can see only their service-stage token inventory, load/reprint a
  token's QR on demand, mark it missing, and inspect scan/action history with
  linked student, queue ticket, and document request details.
- Staff cannot reset a token while its linked queue ticket is waiting or
  serving; reset history retains the previous student and ticket association.
- Students cannot call, skip, alter payment, release tickets, reset tokens, or
  view another student's token/ticket information.
- Cashier cannot perform Registrar-only actions and vice versa.
- Public lobby exposes no student identity, token reference, request details,
  or private queue lookup.
- Pending/unlinked student accounts cannot sign in; approved active students
  can sign in and must still complete the initial password change.
- Existing application approval, document payment, and release workflows
  continue to pass.

## Deployment and operational notes

1. Back up the database and deploy the API/frontend changes together.
2. Run `php artisan migrate --force` from `api/`; migrations add reusable-token
   coordination, activation-attempt, audit, and uniqueness data only.
3. Build the Vue app with `npm run build` from `frontend/`.
4. Configure HTTPS, keep staff accounts protected, and use a shared production
   database/cache as appropriate to the existing deployment.
5. Prepare a visible holder and return tray for each service stage. Tokens must
   be durable, carry only a QR and staff reference, and never show a queue
   position. Students take one token, retain it while waiting, and hand it to
   staff when served.
6. Brief staff: call tickets normally; compare the handed-back token reference
   with the expected reference on screen; select returned/reset only when the
   physical token is received; otherwise mark it missing. Reset a locked or
   missing token only after physically seeing it. Do not treat a last-user log
   as automatic evidence of intentional loss.
7. Monitor queue counts, token-missing events, token locks, no-shows, and
   student wait-time feedback. Adjust physical-token count or the practical
   attempt thresholds only if real use demonstrates a need.

Rollback: deploy the previous application code if needed. Do not delete queue,
token, or application records. New coordination tables can remain unused until
a separately reviewed rollback migration is scheduled.

## Implementation checklist

| Work item | Status | Evidence |
|---|---|---|
| Physical-token schema and models | Implemented | Added dedicated physical-token, attempt, and audit migrations/models; the original `queue_tokens` table remains daily ticket history. |
| Initial physical-token inventory | Implemented | `PhysicalQueueTokenSeeder` provisions 60 Cashier and 60 Registrar tokens. |
| Authenticated QR activation | Implemented | Public student-number queue routes were removed; `/queue/activate` derives the active student from the bearer token. |
| Practical attempt controls | Implemented | Three same-student attempts block that token for the day; five cross-student failed attempts lock it until staff reset. |
| Staff return requirement | Implemented | Completion requires the matching printed physical-token reference; staff UI displays the expected reference. |
| No automatic Registrar ticket | Implemented | Marking a document ready closes the old Cashier hold; the student must activate a Registrar token on return. |
| Polling and privacy | Implemented | Authenticated student status polling and anonymous public lobby data remain; no WebSockets or geolocation added. |
| Student QR deep-link page | Implemented | The Vue app accepts `/queue/check-in?queue_token=…`, loads eligible authenticated requests, and activates the QR token. |
| Student ticket visibility and printing | Implemented | Successful check-in shows the assigned queue number and offers a print-only ticket view. |
| Cashier queue visibility | Verified | Browser flow issued Fiona's `Q-001`; Cashier Queue Management displayed it as waiting with a waiting count of 1. |
| Staff physical-token inventory and QR printing | Implemented | Queue Management now includes a stage-scoped token inventory, on-demand QR preview, and print/reprint card controls. |
| Token lifecycle and linked audit history | Implemented | Missing/reset actions refresh inventory; live tickets block reset, and history links each scan/action to its student, queue ticket, document request, and staff actor where applicable. |
| Cashier token-pane browser walkthrough | Verified | Cashier Queue Management displayed the 60-token inventory; selected `C-01` showed its QR and enabled Print / Reprint. The print action invoked the browser print flow. Its current assignment and history linked Fiona Reyes to `Q-001` and request `#11`; reset was disabled while that ticket was active. |
| Registrar token QR | Verified | Registrar inventory is seeded with 60 tokens. Stage-authorized API verification returned the Registrar token QR secret only from its explicit per-token endpoint; the shared staff component displays the same QR/log pane for the Registrar stage. |
| Online sample queue | Verified | Fiona Reyes submitted request `#13`, checked in with Cashier token `C-01` for `Q-001`, paid and processed, then joined Registrar with token `R-01` for `R-001`; Registrar released the document. Final request status was `released`, and the student's active queue was empty. |
| Stale hold / Registrar release consistency | Fixed and tested | Ready-for-pickup closes the prior Cashier hold; old holds attached to ready/released requests are not returned as active student tickets. Direct release from the document list is disabled; release requires Registrar queue service and the returned physical token. |
| Recoverable QR secrets | Implemented | Token QR secrets remain hashed for lookup and are stored encrypted for authorized one-token reprints; inventory and history omit the secret. |
| Session restore before request loading | Fixed and verified | The QR page restores the saved student session before loading eligible requests, avoiding an empty selector after a scan. |
| Registration approval UI | Implemented | The visible guest registration form now opens the existing enrollee application workflow; the removed `/auth/register` client call is gone. |
| Token feature validation | Verified | Full backend suite: 30 tests, 297 assertions passed. `npm run build` passed (Vue type-check and Vite production build); token migrations are applied. The sample cashier-to-Registrar release flow passed against the running local API. |
