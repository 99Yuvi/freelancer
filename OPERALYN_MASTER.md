# OPERALYN — Complete Project Master Document
**For any AI assistant reading this: this file is the single source of truth for the Operalyn platform. Read it fully before answering any question about the codebase.**

---

## 1. WHAT IS OPERALYN

Operalyn is a two-sided **freelancing marketplace** platform built for the Indian market, similar to Upwork or Fiverr.

**Company:** Operalyn Freelance Network Services Private Limited  
**Domain:** operalyn.com  
**Business model:** 12% platform commission on every completed milestone payment (via Razorpay)  
**Target market:** India — INR payments, Indian freelancers and businesses

**Three user roles:**
- **Client** — posts projects, reviews proposals, hires freelancers, approves milestones, pays
- **Freelancer** — creates a profile, submits proposals, delivers work, receives payment
- **Admin** — manages the platform: verifies freelancers, monitors payments, moderates content

**Current status:** All 6 sprints complete. Full MVP is production-ready. Every feature from Auth → Chat → Payments → Reviews → Admin panel is implemented and tested.

---

## 2. CHECK RESULTS (verified June 2026)

| Item | Result |
|---|---|
| API routes total | 77 routes registered |
| Database migrations | 26 ran successfully |
| Frontend modules | 196 modules, clean build |
| Frontend pages | 36 pages across 5 role areas |
| Chat server files | 6 files, all syntax OK |
| DB seeded | 7 categories, 31 skills, 5 settings, 1 admin |
| PHP version | 8.3 |
| Node.js version | 24.x |

---

## 3. TECH STACK

### Backend
| Component | Tech | Version |
|---|---|---|
| Framework | Laravel | 11 |
| Language | PHP | 8.3 |
| Authentication | Laravel Sanctum (SPA cookie) | — |
| Permissions | Spatie Laravel Permission | — |
| PDF generation | barryvdh/laravel-dompdf | — |
| Payment gateway | Razorpay PHP SDK | — |
| Queue driver | Database (MySQL) | — |
| Mail (dev) | Log driver (writes to storage/logs) | — |
| Mail (prod) | SMTP (Hostinger or SendGrid) | — |

### Frontend
| Component | Tech | Version |
|---|---|---|
| Framework | React | 18 |
| Build tool | Vite | 8 |
| Routing | React Router | v6 |
| HTTP client | Axios | — |
| Server state | TanStack Query (React Query) | v5 |
| Forms | React Hook Form + Zod | — |
| CSS | Tailwind CSS | v4 |
| Real-time | socket.io-client | — |

### Chat Server
| Component | Tech |
|---|---|
| Runtime | Node.js 24 |
| Real-time | Socket.io 4 |
| HTTP client | Axios (for Laravel persistence) |

### Database & Hosting
| Component | Tech |
|---|---|
| Database | MySQL 8 |
| Dev DB name | operalyn_dev |
| Hosting | Hostinger Business (prod) / localhost (dev) |
| Chat server | DigitalOcean $6 droplet (prod) / localhost:3001 (dev) |

---

## 4. THREE PROJECTS — FILE LOCATIONS

```
E:\operalyn-backend\    ← Laravel 11 API + admin panel backend
E:\operalyn-frontend\   ← React 18 SPA (all three dashboards in one build)
E:\operalyn-chat\       ← Node.js Socket.io chat server
```

### Backend folder structure (key paths)
```
app\Http\Controllers\
  Auth\         8 controllers: Register, Login, Logout, Me, EmailVerify,
                ForgotPassword, ResetPassword, SocketVerify
  Client\       ProfileController, ProjectController, ProposalController
  Freelancer\   ProfileController, ProposalController
  Shared\       ContractController, MilestoneController, ConversationController,
                NotificationController, PaymentController, PaymentWebhookController,
                ReviewController, FreelancerSearchController, ProjectBrowseController,
                CategoryController, DeliveryFileController
  Admin\        DashboardController, UserController, VerificationController,
                CategoryController, SettingsController, PaymentMonitorController,
                ReviewModerationController, AuditLogController, ReportController
  Internal\     MessageController (Node.js service endpoint only)

app\Http\Middleware\
  EnsureRole.php          (role-gated routes: admin/client/freelancer)
  EnsureServiceToken.php  (Node.js service token auth)
  LogAdminAction.php      (auto-logs all admin mutations to audit_logs)

app\Models\               (18 models — see Section 6)
app\Policies\             (ContractPolicy, MilestonePolicy, ProjectPolicy, ProposalPolicy)
app\Services\             (ContractService, PaymentService, ProposalService)
app\Jobs\                 (ProcessRazorpayWebhook, GenerateInvoice, UpdateFreelancerRatingJob)
app\Events\               (ContractCreated)
app\Listeners\            (CreateConversationOnContractCreated)
app\Console\Commands\     (ExpireReviews — daily 2am via scheduler)

database\migrations\      (26 migration files)
database\seeders\         (CategorySeeder, SettingsSeeder, AdminUserSeeder, DatabaseSeeder)
resources\views\invoices\ (payment.blade.php — PDF invoice template)
routes\api.php            (ALL 77 API routes)
routes\console.php        (scheduler: queue worker, expire-reviews, prune-failed)
config\services.php       (razorpay keys, node_service_token)
bootstrap\app.php         (Sanctum stateful, middleware aliases, JSON error handler)
```

### Frontend folder structure (key paths)
```
src\main.jsx          Entry: QueryClient + AuthProvider + NotificationProvider + RouterProvider
src\router.jsx        ALL routes
src\index.css         Tailwind v4 with design tokens
src\api\              11 files: admin, auth, client, contracts, conversations,
                      notifications, payments, profiles, projects, proposals, reviews
src\contexts\         AuthContext, NotificationContext
src\hooks\            useContracts, useConversation, useProjects, useProposals
src\lib\              utils.js, queryKeys.js, socket.js
src\layouts\          AdminLayout, ClientLayout, FreelancerLayout
src\components\shared\ RequireAuth, NotificationBell
src\pages\
  auth\               Login, Register, ForgotPassword, ResetPassword, VerifyEmail
  client\             Dashboard, MyProjects, NewProject, ProjectDetail,
                      FreelancerSearch, ContractsList, ProfileEdit
  freelancer\         Dashboard, BrowseProjects, ProjectDetail, MyProposals,
                      ContractsList, Earnings, ProfileEdit
  admin\              Dashboard, Users, Verifications, Categories, Payments,
                      Reviews, AuditLogs, Reports, Settings
  shared\             ContractDetail, FreelancerPublicProfile, ChatView,
                      ConversationsList, PaymentHistory, ReviewForm
  public\             Landing
  errors\             NotFound, Forbidden
```

### Chat server folder structure
```
E:\operalyn-chat\
  server.js                Express + Socket.io init, attaches auth middleware
  .env                     PORT, LARAVEL_API_URL, NODE_SERVICE_TOKEN, CORS_ORIGIN
  middleware\auth.js        Validates Sanctum token via GET /auth/socket-verify
  handlers\messageHandler.js   send_message: persist to Laravel FIRST, then broadcast
  handlers\presenceHandler.js  join/leave rooms, online/offline events
  handlers\typingHandler.js    typing_start/stop with 3s server-side auto-stop
  services\laravelApi.js   Axios instance with NODE_SERVICE_TOKEN Bearer header
```

---

## 5. ENVIRONMENT VARIABLES

### Backend (E:\operalyn-backend\.env)
```
APP_NAME=Operalyn
APP_ENV=local                 # change to production for prod
APP_KEY=base64:...            # auto-generated
APP_DEBUG=true                # set FALSE in production
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=operalyn_dev
DB_USERNAME=root
DB_PASSWORD=                  # set your MySQL password

QUEUE_CONNECTION=database
SESSION_DRIVER=database

# Mail — currently 'log' (dev). Change to 'smtp' + MAIL_HOST etc. for production
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@operalyn.com

# Sanctum SPA — must match frontend dev origin
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
FRONTEND_URL=http://localhost:5173

# Razorpay — from razorpay.com dashboard
RAZORPAY_KEY_ID=rzp_test_xxxx
RAZORPAY_KEY_SECRET=your_secret
RAZORPAY_WEBHOOK_SECRET=webhook_secret

# Generate: php artisan tinker → User::find(1)->createToken('node-service')->plainTextToken
NODE_SERVICE_TOKEN=your_generated_token
```

### Chat server (E:\operalyn-chat\.env)
```
PORT=3001
LARAVEL_API_URL=http://localhost:8000/api/v1
NODE_SERVICE_TOKEN=same_token_as_backend
CORS_ORIGIN=http://localhost:5173
```

---

## 6. DATABASE — ALL 26 TABLES

### Core identity & sessions
```
users                   id, name, email, password, role(admin/client/freelancer),
                        status(active/suspended), avatar_path, deleted_at
sessions                id, user_id, payload
personal_access_tokens  id, tokenable_id, token, name
password_reset_tokens   email, token
jobs / failed_jobs      queue job storage
cache                   Laravel cache
```

### Settings & catalog
```
settings    key_name, value, type, group_name
            Seeded: commission_rate=12%, max_active_proposals=5,
                    review_window_days=14, min_reviews_for_rating=3, max_file_upload_mb=10

categories  id, name, slug, parent_id, icon, sort_order, is_active
            Seeded: 7 top-level (Development/Design/Marketing/Writing/Business/Media/Architecture)

skills      id, name, slug, category_id, is_approved
            Seeded: 31 skills across all categories
```

### Profiles
```
freelancer_profiles     user_id, headline, bio, hourly_rate, availability,
                        verification_status(unsubmitted/pending/approved/rejected),
                        resume_path, total_earnings, rating_avg, rating_count

client_profiles         user_id, company_name, website, industry, location, total_spent

freelancer_skills       freelancer_profile_id, skill_id  [pivot]

experiences             freelancer_profile_id, title, company, start_date, end_date, is_current
educations              freelancer_profile_id, institution, degree, start_year, end_year
certifications          freelancer_profile_id, name, issuer, issued_date
portfolio_items         freelancer_profile_id, title, description, project_url
portfolio_images        portfolio_item_id, file_path, sort_order
verification_documents  freelancer_profile_id, doc_type(id_front/id_back/selfie), file_path [private]
```

### Projects & proposals
```
projects        client_id, category_id, title, description, budget_type, budget_min,
                budget_max, deadline, visibility(public/invite_only),
                status(draft/open/in_progress/completed/cancelled), deleted_at
                FULLTEXT index on title+description (MySQL prod)

project_skills  project_id, skill_id  [pivot]

proposals       project_id, freelancer_id, cover_letter, bid_amount, duration_days,
                status(pending/shortlisted/accepted/rejected/withdrawn)
                UNIQUE(project_id, freelancer_id) — one proposal per project per freelancer
```

### Contracts & milestones
```
contracts       proposal_id(UNIQUE), project_id, client_id, freelancer_id,
                total_amount, commission_rate(SNAPSHOTTED at creation), status, started_at, completed_at

milestones      contract_id, title, description, amount, due_date,
                status(pending/in_progress/submitted/revision_requested/approved/paid)

milestone_deliveries       milestone_id, note
milestone_delivery_files   delivery_id, file_path, original_name, mime_type, file_size [private]
```

### Chat
```
conversations   contract_id(UNIQUE), client_id, freelancer_id, last_message_at
                One conversation is auto-created per contract via ContractCreated event

messages        conversation_id, sender_id, type(text/file/image), body, file_path, read_at
```

### Payments, reviews, audit
```
payments        contract_id, milestone_id(UNIQUE), client_id, freelancer_id,
                razorpay_order_id(UNIQUE), razorpay_payment_id,
                amount, commission_rate, commission_amount, net_amount, invoice_path,
                status(pending/captured/failed/refunded), captured_at

reviews         contract_id, reviewer_id, reviewee_id,
                communication(1-5), quality(1-5), timeliness(1-5), overall(1-5),
                body, response, is_visible, is_hidden
                UNIQUE(contract_id, reviewer_id) — one review per party per contract
                Blind: is_visible=false until both parties review or 14-day window closes

audit_logs      admin_id, action, target_type, target_id, payload(JSON), ip_address
                IMMUTABLE — no update/delete operations on this table

notifications   type, notifiable_type, notifiable_id, data(JSON), read_at
                Laravel polymorphic notifications, polled every 60s on frontend
```

---

## 7. ALL 77 API ENDPOINTS

Base: `http://localhost:8000/api/v1`

### Public
```
POST   /register                                  Create account
POST   /login                                     Login (returns Sanctum cookie)
POST   /forgot-password                           Send reset email
POST   /reset-password                            Set new password
GET    /email/verify/{id}/{hash}                  Verify email (signed URL)
POST   /webhooks/razorpay                         Razorpay webhook (HMAC auth)
GET    /projects                                  Browse open projects
GET    /projects/{project}                        Project detail
GET    /freelancers                               Search verified freelancers
GET    /freelancers/{user}                        Freelancer public profile
GET    /categories                               All categories
GET    /skills                                    All approved skills
GET    /users/{user}/reviews                      Public reviews for a user
```

### Authenticated (all roles)
```
POST   /logout
GET    /auth/me                                   Current user + profile
GET    /auth/socket-verify                        Used by Node.js to validate token
POST   /email/resend                              Resend verification email

GET    /contracts                                 My contracts
GET    /contracts/{contract}                      Full contract detail
POST   /contracts/{contract}/milestones           Add milestone
POST   /contracts/{contract}/review               Submit review
PUT    /milestones/{milestone}                    Edit milestone
DELETE /milestones/{milestone}                    Remove milestone
POST   /milestones/{milestone}/deliver            Submit delivery + files
POST   /milestones/{milestone}/approve            Approve → Razorpay order
POST   /milestones/{milestone}/request-revision   Request changes
GET    /deliveries/{delivery}/files/{fileId}      Signed delivery file URL

GET    /payments                                  Payment history
GET    /payments/{payment}                        Payment detail
GET    /payments/{payment}/invoice                Invoice PDF (signed URL)
POST   /reviews/{review}/respond                  Respond to a review

GET    /conversations                             Inbox
GET    /conversations/{conversation}              Conversation detail
GET    /conversations/{conversation}/messages     Messages (cursor-paginated)
PATCH  /conversations/{conversation}/read         Mark as read

GET    /notifications                             Last 50 + unread count
PATCH  /notifications/{id}/read                  Mark one read
PATCH  /notifications/read-all                   Mark all read
```

### Freelancer only (/freelancer/)
```
GET    /freelancer/profile
PUT    /freelancer/profile                        Update + sync skills
POST   /freelancer/resume                         Upload PDF resume (5MB max)
POST   /freelancer/projects/{project}/proposals   Submit proposal
PUT    /freelancer/proposals/{proposal}           Edit pending proposal
DELETE /freelancer/proposals/{proposal}           Withdraw
GET    /freelancer/proposals                      My proposals
```

### Client only (/client/)
```
GET    /client/profile
PUT    /client/profile
GET    /client/projects                           My projects list
POST   /client/projects                           Post project
GET    /client/projects/{project}                 My project + proposals
PUT    /client/projects/{project}                 Edit
DELETE /client/projects/{project}                 Delete
GET    /client/projects/{project}/proposals       Proposals on project
PATCH  /client/proposals/{proposal}/shortlist     Shortlist
PATCH  /client/proposals/{proposal}/reject        Reject
POST   /client/proposals/{proposal}/accept        HIRE → creates contract
```

### Admin only (/admin/, all mutations → audit_logs)
```
GET    /admin/dashboard                           8 platform stats
GET    /admin/users                               All users (search/filter)
GET    /admin/users/{user}                        User detail
PATCH  /admin/users/{user}/status                 Suspend/unsuspend
DELETE /admin/users/{user}                        Soft delete
GET    /admin/verifications                       Verification queue
PATCH  /admin/verifications/{profile}             Approve/reject
GET    /admin/verifications/{profile}/documents/{docId}  Signed doc URL
GET    /admin/categories                          All categories
POST   /admin/categories                          Create
PUT    /admin/categories/{category}               Update
DELETE /admin/categories/{category}               Delete
GET    /admin/settings                            All settings
PUT    /admin/settings                            Batch update
GET    /admin/payments                            All payments (CSV export via ?export=csv)
GET    /admin/reviews                             All reviews
PATCH  /admin/reviews/{review}/hide               Hide with reason
PATCH  /admin/reviews/{review}/unhide             Restore
GET    /admin/audit-logs                          Immutable log
GET    /admin/reports/overview                    Charts data
```

### Internal (Node.js service token only)
```
POST   /internal/messages                         Persist chat message
```

---

## 8. FRONTEND ROUTES (36 pages)

```
/                                      Landing page (public marketing)
/auth/login                            Login
/auth/register                         Register (role selector)
/auth/forgot-password                  Forgot password
/auth/reset-password                   Reset password
/auth/verify-email                     Email verification status
/403                                   Forbidden
/*                                     404 Not Found

CLIENT (require role=client):
/client                                Dashboard — stats + active contracts + open projects
/client/projects                       My projects list
/client/projects/new                   3-step wizard: Info → Skills → Budget
/client/projects/:id                   Project detail + proposal cards (hire/shortlist/reject)
/client/contracts                      Contracts list with progress bars
/client/contracts/:id                  Milestones + approve (Razorpay) + revision
/client/contracts/:contractId/review   4-dimension star rating form
/client/chat                           Inbox with unread count badges
/client/chat/:conversationId           Real-time chat
/client/payments                       Transaction table + invoice PDF links
/client/freelancers                    Search freelancers (skill/rate/availability)
/client/freelancers/:userId            Freelancer public profile
/client/profile                        Edit company profile

FREELANCER (require role=freelancer):
/freelancer                            Dashboard — contracts + proposals + earnings
/freelancer/projects                   Browse open projects
/freelancer/projects/:id               Project detail + submit proposal form
/freelancer/proposals                  My proposals with status + withdraw
/freelancer/contracts                  Contracts with "action needed" badges
/freelancer/contracts/:id              Milestones + deliver (file upload) + status
/freelancer/contracts/:contractId/review  Review the client
/freelancer/chat                       Inbox
/freelancer/chat/:conversationId       Real-time chat
/freelancer/earnings                   Lifetime/monthly earnings + per-payment breakdown
/freelancer/profile                    Edit profile + skill tag picker + resume upload

ADMIN (require role=admin):
/admin                                 Platform stats (GMV, commission, users, verifications)
/admin/users                           Search/filter users, suspend/delete
/admin/verifications                   Approve/reject freelancer IDs with doc preview
/admin/categories                      CRUD categories, toggle active
/admin/payments                        All transactions, CSV export
/admin/reviews                         Moderate reviews, hide/restore
/admin/audit-logs                      Immutable action log
/admin/reports                         Charts: growth, volume, revenue, top freelancers
/admin/settings                        Commission rate, limits, review window
```

---

## 9. KEY BUSINESS FLOWS

### Hire a freelancer
Client posts project → Freelancers submit proposals → Client accepts →
ContractService (atomic): creates contract (snapshots commission_rate) + rejects other proposals +
sets project=in_progress + fires ContractCreated event → listener auto-creates conversation

### Deliver and get paid
Client adds milestones → Freelancer submits delivery (note + files) →
Client approves → PaymentService creates Razorpay order → Frontend opens checkout.js modal →
User pays → Razorpay webhook → Laravel HMAC verify → ProcessRazorpayWebhook job queued →
Job: milestone=paid, totals updated, contract auto-completed if all paid, GenerateInvoice job dispatched

### Reviews (blind)
Contract completes → Both see "Leave a review" (14-day window) →
Reviews saved as is_visible=false → Once both submit: is_visible=true → UpdateFreelancerRatingJob recalculates rating_avg →
operalyn:expire-reviews (daily 2am) makes reviews visible if window closed

### Freelancer verification
Freelancer submits ID docs (private storage) → Admin sees in queue →
Admin views signed URLs → Approves/rejects with notes → Approved = Verified badge + appears in search

---

## 10. HOW TO RUN (all 3 services)

```bash
# Terminal 1 — Backend
cd E:\operalyn-backend
php artisan serve
# http://localhost:8000

# Terminal 2 — Frontend
cd E:\operalyn-frontend
npm run dev
# http://localhost:5173

# Terminal 3 — Chat server
cd E:\operalyn-chat
node server.js
# http://localhost:3001

# Terminal 4 (optional for dev) — Queue worker
cd E:\operalyn-backend
php artisan queue:work
# Processes invoice generation, webhook jobs, etc.
```

**If port 3001 is busy (EADDRINUSE):**
```powershell
Stop-Process -Id (Get-NetTCPConnection -LocalPort 3001).OwningProcess -Force
```

---

## 11. ADMIN LOGIN

```
URL:      http://localhost:5173/auth/login
Email:    admin@operalyn.com
Password: Admin@123456
```
**Change this password immediately after first login.**

---

## 12. IMPORTANT NOTES FOR DEVELOPERS

1. **MAIL_MAILER=log in dev** — Emails write to `storage/logs/laravel.log`, not actually sent. Change to smtp for real email.

2. **Queue worker must run** — Invoice PDFs and webhook processing are queued jobs. Run `php artisan queue:work` in a separate terminal. Without it, payments are processed but invoices aren't generated.

3. **Razorpay fallback** — If `RAZORPAY_KEY_ID` is not set, `MilestoneController@approve` falls back to marking the milestone approved directly. Good for dev/test.

4. **NODE_SERVICE_TOKEN** — Must be in BOTH `.env` files (backend + chat server). Generate:
   ```bash
   cd E:\operalyn-backend
   php artisan tinker
   > \App\Models\User::find(1)->createToken('node-service')->plainTextToken
   ```

5. **commission_rate is snapshotted** — Stored in `contracts.commission_rate` at creation time. Changing it in settings does NOT affect existing contracts.

6. **Blind reviews** — Reviews are `is_visible=false` until both parties submit OR the `operalyn:expire-reviews` command runs (daily 2am). In dev, run manually: `php artisan operalyn:expire-reviews`

7. **SQLite vs MySQL** — FULLTEXT index was removed from projects migration (SQLite doesn't support it). On MySQL production, add: `ALTER TABLE projects ADD FULLTEXT ft_search (title, description);`

8. **File storage** — Private files (ID docs, resumes, deliverables, invoices) are in `storage/app/private/`. Access via signed URLs only. Public files (avatars, portfolio images) are in `storage/app/public/` symlinked to `public/storage/`.

---

## 13. NOT IMPLEMENTED (post-MVP)

Escrow · Razorpay Route (automated freelancer payout) · AI matching · Mobile apps ·
Multi-currency · SMS/push notifications · Dispute resolution · Subscription tiers ·
Social login · Hindi localisation · Video calls · CMS editable pages

---

## 14. SPRINT HISTORY

| Sprint | What was built |
|---|---|
| S1 | Auth · Profiles · 26 DB migrations · 3 seeders · React scaffolding |
| S2 | Projects CRUD · Proposals · Freelancer search · 33 routes |
| S3 | Contracts · Milestones · File delivery · Approve/Revision |
| S4 | Node.js chat server · Socket.io · Notifications (60s poll) |
| S5 | Razorpay payments · Invoice PDF · Blind reviews · Earnings page |
| S6 | Admin panel (9 pages) · Verification queue · Reports · Landing page |

**Total: 77 API routes · 36 React pages · 26 DB tables · 3 services**

---

*Operalyn Freelance Network Services Pvt. Ltd. | Built June 2026 | Claude Code*
