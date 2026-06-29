# CLAUDE_CONTEXT.md
## Read this entire file before doing anything. This is the complete handoff brief for the Operalyn project.

---

## WHO YOU ARE AND WHAT YOU ARE DOING

You are a senior full-stack developer continuing work on **Operalyn** — a production-ready Indian freelancing marketplace (like Upwork/Fiverr) that has been fully built across 6 sprints. The platform is **complete and working**. Your job is to extend, fix, or improve it based on whatever the user asks next.

**The user (Yuvi) is the founder.** He speaks in Hinglish (mix of Hindi and English). He is non-technical enough that you should write all code and never ask him to figure out complex steps himself. When he says something like "ye fix karo" or "add kar do", just do it.

---

## THREE PROJECT FOLDERS

```
E:\operalyn-backend\    ← Laravel 11 API (PHP 8.3)
E:\operalyn-frontend\   ← React 18 + Vite SPA
E:\operalyn-chat\       ← Node.js Socket.io chat server
```

**All three must run simultaneously for the full platform to work.**

---

## HOW TO START EVERYTHING (run in separate terminals)

```bash
# Terminal 1 — Laravel API
cd E:\operalyn-backend
php artisan serve
# → http://localhost:8000

# Terminal 2 — React frontend
cd E:\operalyn-frontend
npm run dev
# → http://localhost:5173

# Terminal 3 — Chat server
cd E:\operalyn-chat
node server.js
# → http://localhost:3001

# Terminal 4 — Queue worker (for invoice/email jobs)
cd E:\operalyn-backend
php artisan queue:work
```

**If port 3001 is already in use (EADDRINUSE error):**
```powershell
Stop-Process -Id (Get-NetTCPConnection -LocalPort 3001).OwningProcess -Force
```

---

## ADMIN LOGIN

```
URL:      http://localhost:5173/auth/login
Email:    admin@operalyn.com
Password: Admin@123456
```

**Test accounts created during development:**
- Client: `client@test.com` / `Client@12345`
- Freelancer: `freelancer@test.com` / `Freelancer@12345`

---

## TECH STACK (exact versions matter)

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.3 |
| Auth | Laravel Sanctum (SPA cookie-based) |
| Queue | Database driver (MySQL) |
| PDF | barryvdh/laravel-dompdf |
| Payment | Razorpay PHP SDK |
| Frontend | React 18, Vite 8 |
| CSS | Tailwind CSS v4 with `@tailwindcss/vite` plugin |
| Routing | React Router v6 |
| Server state | TanStack Query (React Query) v5 |
| Forms | React Hook Form + Zod |
| Real-time | socket.io-client |
| Chat server | Node.js 24, Socket.io 4 |
| Database | MySQL 8 (local: `operalyn_dev`) |

**Critical Tailwind note:** This project uses **Tailwind v4** which uses `@import "tailwindcss"` in CSS, NOT `tailwind.config.js`. Do not create a tailwind.config.js.

---

## BACKEND STRUCTURE (key folders)

```
app/Http/Controllers/
  Auth/           RegisterController, LoginController, LogoutController,
                  MeController, EmailVerificationController,
                  ForgotPasswordController, ResetPasswordController,
                  SocketVerifyController
  Client/         ProfileController, ProjectController, ProposalController
  Freelancer/     ProfileController, ProposalController
  Shared/         ContractController, MilestoneController, ConversationController,
                  NotificationController, PaymentController, PaymentWebhookController,
                  ReviewController, FreelancerSearchController, ProjectBrowseController,
                  CategoryController, DeliveryFileController
  Admin/          DashboardController, UserController, VerificationController,
                  CategoryController, SettingsController, PaymentMonitorController,
                  ReviewModerationController, AuditLogController, ReportController
  Internal/       MessageController (called by Node.js only)

app/Http/Middleware/
  EnsureRole.php           — usage: EnsureRole::using('client')
  EnsureServiceToken.php   — for Node.js service token
  LogAdminAction.php       — auto-logs admin mutations to audit_logs

app/Models/               18 models (User, FreelancerProfile, ClientProfile,
                          Category, Skill, Project, Proposal, Contract, Milestone,
                          Conversation, Message, Payment, Review, Setting, AuditLog,
                          PortfolioItem, PortfolioImage, MilestoneDelivery)

app/Policies/             ContractPolicy, MilestonePolicy, ProjectPolicy, ProposalPolicy
app/Services/             ContractService, PaymentService, ProposalService
app/Jobs/                 ProcessRazorpayWebhook, GenerateInvoice, UpdateFreelancerRatingJob
app/Events/               ContractCreated
app/Listeners/            CreateConversationOnContractCreated
app/Console/Commands/     ExpireReviews (runs daily at 2am via scheduler)

routes/api.php            ALL 77 API routes
routes/console.php        Scheduler definitions
config/services.php       Razorpay keys + node_service_token
bootstrap/app.php         Sanctum stateful, middleware registration, JSON error handler
```

---

## FRONTEND STRUCTURE (key folders)

```
src/
  main.jsx          Entry: QueryClient → AuthProvider → NotificationProvider → RouterProvider
  router.jsx        ALL routes (36 pages)
  index.css         Tailwind v4 + CSS custom properties
  api/              11 files: admin.js, auth.js, client.js, contracts.js,
                    conversations.js, notifications.js, payments.js,
                    profiles.js, projects.js, proposals.js, reviews.js
  contexts/         AuthContext.jsx, NotificationContext.jsx
  hooks/            useContracts.js, useConversation.js, useProjects.js, useProposals.js
  lib/              utils.js, queryKeys.js, socket.js
  layouts/          AdminLayout.jsx, ClientLayout.jsx, FreelancerLayout.jsx
  components/shared/ RequireAuth.jsx, NotificationBell.jsx
  pages/
    auth/           Login, Register, ForgotPassword, ResetPassword, VerifyEmail
    client/         Dashboard, MyProjects, NewProject, ProjectDetail,
                    FreelancerSearch, ContractsList, ProfileEdit
    freelancer/     Dashboard, BrowseProjects, ProjectDetail, MyProposals,
                    ContractsList, Earnings, ProfileEdit
    admin/          Dashboard, Users, Verifications, Categories, Payments,
                    Reviews, AuditLogs, Reports, Settings
    shared/         ContractDetail, FreelancerPublicProfile, ChatView,
                    ConversationsList, PaymentHistory, ReviewForm
    public/         Landing (the homepage/marketing page)
    errors/         NotFound, Forbidden
```

---

## CHAT SERVER STRUCTURE

```
E:\operalyn-chat\
  server.js                 Main entry, attaches auth + handlers
  .env                      PORT=3001, LARAVEL_API_URL, NODE_SERVICE_TOKEN, CORS_ORIGIN
  middleware/auth.js         Validates Sanctum token via GET /auth/socket-verify
  handlers/messageHandler.js persist-first: POST /internal/messages → then broadcast
  handlers/presenceHandler.js join/leave rooms, online/offline events
  handlers/typingHandler.js  typing_start/stop with 3s server-side auto-stop
  services/laravelApi.js     axios with NODE_SERVICE_TOKEN Bearer header
```

---

## DATABASE — ALL 26 TABLES (brief)

```
users                   id, name, email, password, role(admin/client/freelancer),
                        status(active/suspended), avatar_path, deleted_at

freelancer_profiles     user_id, headline, bio, hourly_rate, availability,
                        verification_status(unsubmitted/pending/approved/rejected),
                        resume_path, total_earnings, rating_avg, rating_count

client_profiles         user_id, company_name, website, industry, location, total_spent

categories              id, name, slug, parent_id, icon, sort_order, is_active
                        SEEDED: 7 categories (Development/Design/Marketing/Writing/Business/Media/Architecture)

skills                  id, name, slug, category_id, is_approved
                        SEEDED: 31 skills across all categories

freelancer_skills       pivot: freelancer_profile_id ↔ skill_id
experiences             freelancer_profile_id, title, company, start/end dates
educations              freelancer_profile_id, institution, degree
certifications          freelancer_profile_id, name, issuer
portfolio_items         freelancer_profile_id, title, description, project_url
portfolio_images        portfolio_item_id, file_path
verification_documents  freelancer_profile_id, doc_type(id_front/id_back/selfie), file_path [private]

projects                client_id, category_id, title, description, budget_type,
                        budget_min, budget_max, deadline, visibility(public/invite_only),
                        status(draft/open/in_progress/completed/cancelled), deleted_at

project_skills          pivot: project_id ↔ skill_id

proposals               project_id, freelancer_id, cover_letter, bid_amount, duration_days,
                        status(pending/shortlisted/accepted/rejected/withdrawn)
                        UNIQUE(project_id, freelancer_id)

contracts               proposal_id, project_id, client_id, freelancer_id,
                        total_amount, commission_rate [SNAPSHOTTED],
                        status(active/completed/cancelled), started_at, completed_at

milestones              contract_id, title, description, amount, due_date,
                        status(pending/submitted/revision_requested/approved/paid)

milestone_deliveries    milestone_id, note
milestone_delivery_files delivery_id, file_path, original_name, mime_type, file_size [private]

conversations           contract_id [UNIQUE], client_id, freelancer_id, last_message_at
                        AUTO-CREATED when contract is created via ContractCreated event

messages                conversation_id, sender_id, type(text/file/image), body, file_path, read_at

payments                contract_id, milestone_id [UNIQUE], client_id, freelancer_id,
                        razorpay_order_id [UNIQUE], razorpay_payment_id,
                        amount, commission_rate, commission_amount, net_amount,
                        invoice_path, status(pending/captured/failed)

reviews                 contract_id, reviewer_id, reviewee_id,
                        communication/quality/timeliness/overall (1-5),
                        body, response, is_visible, is_hidden
                        UNIQUE(contract_id, reviewer_id) — BLIND until both submit

audit_logs              admin_id, action, target_type, target_id, payload(JSON), ip_address
                        IMMUTABLE — no updates/deletes ever

notifications           Laravel polymorphic DB notifications, polled 60s on frontend
settings                key_name, value, type, group_name (5 settings seeded)
                        KEY SETTINGS: commission_rate=12.00, max_active_proposals=5,
                        review_window_days=14, min_reviews_for_rating=3, max_file_upload_mb=10

jobs, failed_jobs, cache, sessions, password_reset_tokens, personal_access_tokens
```

---

## ALL 77 API ENDPOINTS (brief reference)

**Base URL:** `http://localhost:8000/api/v1`
**Auth:** Sanctum cookie. Must call `GET /sanctum/csrf-cookie` before login.

### Public
```
POST /register, /login, /forgot-password, /reset-password
GET  /email/verify/{id}/{hash}     [signed URL from email]
POST /webhooks/razorpay            [HMAC auth, no Sanctum]
GET  /projects, /projects/{id}
GET  /freelancers, /freelancers/{user}
GET  /categories, /skills
GET  /users/{user}/reviews
```

### Any authenticated user
```
POST /logout
GET  /auth/me
GET  /auth/socket-verify           [used by Node.js]
POST /email/resend

GET  /contracts, /contracts/{contract}
POST /contracts/{contract}/milestones
POST /contracts/{contract}/review
PUT  /milestones/{milestone}
DELETE /milestones/{milestone}
POST /milestones/{milestone}/deliver
POST /milestones/{milestone}/approve   ← creates Razorpay order
POST /milestones/{milestone}/request-revision
GET  /deliveries/{delivery}/files/{fileId}   [signed URL]

GET  /payments, /payments/{payment}
GET  /payments/{payment}/invoice     [signed URL]
POST /reviews/{review}/respond

GET  /conversations, /conversations/{id}
GET  /conversations/{id}/messages    [cursor-paginated]
PATCH /conversations/{id}/read

GET  /notifications
PATCH /notifications/{id}/read
PATCH /notifications/read-all
```

### Freelancer only (/freelancer/)
```
GET/PUT  /freelancer/profile
POST     /freelancer/resume
POST     /freelancer/projects/{project}/proposals
PUT/DELETE /freelancer/proposals/{proposal}
GET      /freelancer/proposals
```

### Client only (/client/)
```
GET/PUT  /client/profile
GET/POST /client/projects
GET/PUT/DELETE /client/projects/{project}
GET      /client/projects/{project}/proposals
PATCH    /client/proposals/{proposal}/shortlist|reject
POST     /client/proposals/{proposal}/accept          ← CREATES CONTRACT
```

### Admin only (/admin/ — all mutations logged to audit_logs)
```
GET  /admin/dashboard
GET  /admin/users
GET  /admin/users/{user}
PATCH /admin/users/{user}/status
DELETE /admin/users/{user}
GET  /admin/verifications
PATCH /admin/verifications/{profile}
GET  /admin/verifications/{profile}/documents/{docId}
GET/POST /admin/categories
PUT/DELETE /admin/categories/{category}
GET/PUT /admin/settings
GET  /admin/payments                  [?export=csv for CSV]
GET  /admin/reviews
PATCH /admin/reviews/{review}/hide|unhide
GET  /admin/audit-logs
GET  /admin/reports/overview
```

### Internal (Node.js service token only)
```
POST /internal/messages
```

---

## ALL 36 FRONTEND ROUTES

```
/                     Landing page (public marketing)
/auth/login
/auth/register        has role selector: client vs freelancer
/auth/forgot-password
/auth/reset-password
/auth/verify-email

CLIENT (require role=client — protected by RequireAuth):
/client               Dashboard
/client/projects      My projects list
/client/projects/new  3-step post-project wizard
/client/projects/:id  Project detail + proposals list
/client/contracts     Contracts list
/client/contracts/:id Contract detail + milestones + approve/revision
/client/contracts/:contractId/review  4-star review form
/client/chat          Conversations inbox
/client/chat/:conversationId  Chat view
/client/payments      Payment history + invoice download
/client/freelancers   Search freelancers
/client/freelancers/:userId  Freelancer public profile
/client/profile       Edit company profile

FREELANCER (require role=freelancer):
/freelancer           Dashboard
/freelancer/projects  Browse open projects
/freelancer/projects/:id  Project + proposal submission form
/freelancer/proposals My bids
/freelancer/contracts Contracts list
/freelancer/contracts/:id  Contract + deliver work
/freelancer/contracts/:contractId/review  Review the client
/freelancer/chat      Inbox
/freelancer/chat/:conversationId
/freelancer/earnings  Earnings dashboard
/freelancer/profile   Edit profile + skills + resume

ADMIN (require role=admin):
/admin                Stats dashboard
/admin/users          User management
/admin/verifications  ID verification queue
/admin/categories     Category CRUD
/admin/payments       Payment monitor + CSV
/admin/reviews        Review moderation
/admin/audit-logs     Immutable action log
/admin/reports        Charts: growth, revenue, top freelancers
/admin/settings       Platform settings (commission rate, etc.)

/403  Forbidden
/*    404 NotFound
```

---

## KEY ARCHITECTURE PATTERNS (follow these when adding code)

### Backend pattern: Controller → Service → Repository
```php
// Controllers only validate + delegate:
public function accept(Request $request, Proposal $proposal) {
    $this->authorize('accept', $proposal);  // always use policies
    $contract = $this->contractService->createFromProposal($proposal);
    return response()->json(['data' => ..., 'message' => '...'], 201);
}

// Services hold business logic:
// Services are in app/Services/

// Always use DB::transaction() for multi-table operations
// Always fire events for side-effects (notifications, auto-creates)
```

### Frontend pattern: API file → React Query hook → Component
```js
// 1. src/api/xxx.js — raw axios calls, no state
export const getContracts = (params) => api.get('/contracts', { params })

// 2. src/hooks/useXxx.js — React Query wrappers
export const useContracts = (params) =>
  useQuery({ queryKey: contractKeys.list(params), queryFn: () => api.getContracts(params) })

// 3. Component just calls the hook
const { data, isLoading } = useContracts()
```

### Route protection
```jsx
// In router.jsx — wrap with RequireAuth
{ element: <RequireAuth role="client" />, children: [{ element: <ClientLayout />, children: [...] }] }
```

### API response format
```json
// Success single:     {"data": {...}, "message": "..."}
// Success collection: {"data": [...], "meta": {"total": N}}
// Error:              {"message": "...", "errors": {"field": ["msg"]}}
```

---

## IMPORTANT GOTCHAS — READ BEFORE WRITING CODE

1. **Tailwind v4** — uses `@import "tailwindcss"` NOT a config file. Don't add tailwind.config.js.

2. **CSRF** — Before login/register, must call `GET /sanctum/csrf-cookie`. It's handled in `src/api/auth.js` via `initCsrf()`.

3. **commission_rate is SNAPSHOTTED** — stored in contracts table at creation time. Changing settings does NOT affect existing contracts.

4. **Razorpay fallback** — In dev without Razorpay keys, `MilestoneController@approve` falls back to marking milestone approved directly. This is intentional.

5. **Mail driver = log in dev** — Emails write to `storage/logs/laravel.log`. To find verification links: `cat storage/logs/laravel.log | grep "verify"`.

6. **Queue worker must run** — For invoice PDFs and email notifications to work, `php artisan queue:work` must be running.

7. **NODE_SERVICE_TOKEN** — Must be set in BOTH `.env` files (backend + chat server). Generate: `php artisan tinker → User::find(1)->createToken('node-service')->plainTextToken`.

8. **Interceptor 401 rule** — The Axios interceptor in `src/api/client.js` does NOT redirect to login from `/`, `/auth/*`, or `/freelancers/*` — only from protected routes.

9. **Blind reviews** — `reviews.is_visible=false` until both parties submit OR `php artisan operalyn:expire-reviews` runs. In dev, run manually to test.

10. **FULLTEXT index** — Removed from projects migration (SQLite doesn't support it). For MySQL production, add: `ALTER TABLE projects ADD FULLTEXT ft_search (title, description);`

11. **File storage** — Private files (ID docs, resumes, deliverables, invoices) are in `storage/app/private/`. Serve via signed URLs ONLY. Never expose paths directly.

12. **Admin audit log** — `LogAdminAction` middleware automatically logs every non-GET admin request. Do NOT manually call `AuditLog::record()` in controllers for HTTP actions — it's double-logged.

---

## DESIGN SYSTEM (for frontend changes)

Operalyn's frontend uses a clean light design:
- **Primary color:** `#334155` (slate-700) — buttons, active nav, links
- **Background:** white + `#F7F6F4` alternating sections
- **Text:** `#0F172A` (slate-900)
- **Muted text:** `#64748B` (slate-500)
- **Border:** `#E2E8F0` (slate-200)
- **Success:** `#15803D` (green-700)
- **Warning/money accent:** `#D97706` (amber-700)
- **Font display:** Georgia serif (headings, brand name)
- **Font body:** system-ui sans-serif

**Button patterns:**
```jsx
// Primary
className="rounded-lg bg-slate-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
// Outline
className="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
// Destructive
className="text-xs text-red-500 hover:text-red-700"
```

**Card pattern:**
```jsx
className="bg-white border border-slate-200 rounded-xl p-5"
```

**Layout max-width:** `max-w-6xl mx-auto` inside `<main>`.

---

## SPRINT HISTORY (what was built, in order)

| Sprint | What's in it |
|---|---|
| S1 | Auth (register/login/verify/reset) · Profiles (client + freelancer) · 26 DB migrations · 3 seeders (categories, settings, admin) · React scaffolding |
| S2 | Projects CRUD · Proposal submission + review + accept → contract · Freelancer search · Public profiles |
| S3 | Contracts · Milestones · File delivery (5 files, 10MB each) · Approve/revision · ContractService (atomic) |
| S4 | Node.js chat server (Socket.io) · Conversations · Notifications (60s poll) · NotificationBell |
| S5 | Razorpay payments · Invoice PDF (DomPDF) · Blind reviews · Earnings page |
| S6 | Full admin panel (9 pages) · Freelancer verification queue · Reports charts · Landing page redesign |

**Total shipped:** 77 API routes · 36 React pages · 26 DB tables · 3 services running · Clean build (196 modules)

---

## WHAT IS NOT BUILT YET (post-MVP)

These were explicitly out of scope:
- Escrow / payment holding
- Automated freelancer bank payout (Razorpay Route)
- AI matching
- Mobile app (iOS/Android)
- Multi-currency
- SMS/push notifications
- Dispute resolution workflow
- Subscription tiers
- Social login (Google/GitHub)
- Hindi localisation
- Editable CMS pages (Terms/Privacy)
- Code splitting in Vite (bundle is ~580KB — flagged as warning but works fine)

---

## COMMON COMMANDS YOU'LL NEED

```bash
# Backend
php artisan serve                          # start API server
php artisan migrate:fresh --seed           # reset DB + seed
php artisan migrate                        # run new migrations
php artisan make:migration create_xxx_table
php artisan make:controller Admin/XxxController
php artisan make:model Xxx -m              # model + migration
php artisan tinker                         # interactive PHP REPL
php artisan route:list --path=api/v1       # see all routes
php artisan queue:work                     # process jobs
php artisan operalyn:expire-reviews        # manually run review expiry
php artisan config:cache && php artisan route:cache  # production cache

# Frontend
npm run dev        # start dev server
npm run build      # production build (check for errors)

# Chat server
node server.js     # start chat server
node --check server.js  # syntax check without running
```

---

## ENVIRONMENT VARIABLES (local dev)

```
# E:\operalyn-backend\.env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=mysql
DB_DATABASE=operalyn_dev
DB_USERNAME=root
DB_PASSWORD=           ← set your MySQL password
MAIL_MAILER=log        ← emails go to storage/logs/laravel.log
QUEUE_CONNECTION=database
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
RAZORPAY_KEY_ID=rzp_test_xxxx   ← optional in dev (falls back gracefully)
NODE_SERVICE_TOKEN=              ← generate after first run

# E:\operalyn-chat\.env
PORT=3001
LARAVEL_API_URL=http://localhost:8000/api/v1
NODE_SERVICE_TOKEN=              ← same as backend
CORS_ORIGIN=http://localhost:5173
```

---

## HOW TO VERIFY EVERYTHING IS WORKING

```bash
# 1. API health check
curl http://localhost:8000/api/v1/categories
# Should return JSON with 7 categories

# 2. Route count
cd E:\operalyn-backend
php artisan route:list --path=api/v1 | grep -c "api/v1"
# Should print 77

# 3. Migrations
php artisan migrate:status | grep "Ran" | wc -l
# Should print 26

# 4. Frontend build
cd E:\operalyn-frontend
npm run build
# Should say "✓ built in ~500ms" with no errors

# 5. Chat server syntax
cd E:\operalyn-chat
node --check server.js && echo "OK"
# Should print OK
```

---

## WHEN THE USER ASKS YOU TO DO SOMETHING NEW

**Before writing any code:**
1. Read the relevant existing file first (Read tool) — the patterns are already established
2. Follow the existing Controller → Service → Repository pattern for backend
3. Follow the existing api/ → hook → component pattern for frontend
4. Check `routes/api.php` before adding a new route — the grouping structure is important
5. Check `router.jsx` before adding a new page route
6. For new DB columns: create a migration, don't edit existing migrations
7. Always run `npm run build` to verify no TypeScript/JSX errors after frontend changes

**File you're most likely to need for common tasks:**
- New API endpoint → `routes/api.php` + new controller in `app/Http/Controllers/`
- New frontend page → new file in `src/pages/` + add to `src/router.jsx`
- New nav item → update the correct layout in `src/layouts/`
- DB change → `php artisan make:migration` (NEVER edit existing migrations)
- New setting → add to `SettingsSeeder.php` + `php artisan db:seed --class=SettingsSeeder`

---

*This file was written at the end of Sprint 6 (June 2026). The platform is production-ready. All features in scope are complete.*
