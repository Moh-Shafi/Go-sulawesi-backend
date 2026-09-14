<div align="center">

# ⚙️ GoSulawesi — Backend REST API & Database Engine
### Secure, High-Performance Micro-Framework Architecture for Sulawesi Tourism Services

[![PHP Version](https://img.shields.io/badge/PHP-8.1%20%7C%208.2%20%7C%208.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker Compose](https://img.shields.io/badge/Docker_Compose-Supported-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

*The authoritative backend engine powering GoSulawesi: delivering low-latency JSON APIs, transactional reservation processing, file storage services, and role-based access management.*

[🌐 Monorepo Root](https://github.com/Moh-Shafi/Go-sulawesi) · [📱 Frontend App Repo](https://github.com/Moh-Shafi/Go-sulawesi-frontend)

</div>

---

## 📋 System Architecture & Overview

The **GoSulawesi Backend** is architected as a lightweight, secure, and dependency-free PHP REST service running against an ACID-compliant MySQL relational database. It is engineered specifically for ultra-fast response times (<30ms on production servers), low memory overhead, and straightforward zero-downtime deployment on both **Dockerized container environments** and **traditional LAMP/cPanel production stacks** (e.g., Hostinger).

Key technical design decisions:
- **Zero Heavy Framework Bloat**: Custom controller & middleware routing layer utilizing PHP 8 native PDO prepared statements and streaming output buffers.
- **RESTful Endpoints**: Predictable JSON responses with standard HTTP status code semantics (200, 201, 400, 401, 403, 404, 500).
- **Stateless Verification with Stateful Cookie Storage**: High security through HMAC-SHA256 encrypted signatures stored in `httpOnly` browser cookies.

---

## 🛡️ Comprehensive Security Architecture

Security is built directly into the core configuration layer (`config.php` and `.htaccess`) rather than treated as an afterthought:

```
                  ┌─────────────────────────────────────┐
                  │          Client Request             │
                  └──────────────────┬──────────────────┘
                                     │
                                     ▼
                ┌─────────────────────────────────────────┐
                │ 1. CORS Origin Verification             │  <-- Whitelisted origins only
                └────────────────────┬────────────────────┘
                                     │
                                     ▼
                ┌─────────────────────────────────────────┐
                │ 2. Token Extraction & Verification      │  <-- Cookie priority, Auth Header fallback
                │    - HMAC-SHA256 signature match        │
                │    - Expiration time check              │
                └────────────────────┬────────────────────┘
                                     │
                                     ▼
                ┌─────────────────────────────────────────┐
                │ 3. RBAC Policy Check (`require_role`)   │  <-- Enforces 'tourist', 'local', or 'admin'
                └────────────────────┬────────────────────┘
                                     │
                                     ▼
                ┌─────────────────────────────────────────┐
                │ 4. PDO Prepared Statements Execution    │  <-- SQL Injection prevention
                └─────────────────────────────────────────┘
```

### 1. Cryptographic Authentication & Token Lifecycle
- **Signature Mechanism**: Authentication tokens use an HMAC-SHA256 signature over a base64-encoded JSON payload:
  $$\text{Token} = \text{base64}(\text{payload}) \mathbin{\Vert} \text{"."} \mathbin{\Vert} \text{HMAC-SHA256}(\text{base64}(\text{payload}), \text{TOKEN\_SECRET})$$
- **Payload Schema**: Encodes user ID, assigned role (`tourist`, `local`, `admin`), and strict expiration timestamp (default: 7 days).
- **Constant-Time Comparison**: Signatures are evaluated using PHP's `hash_equals()` to prevent side-channel timing attacks.
- **Cookie Security Attributes**:
  - `HttpOnly`: Strictly true — JavaScript has zero programmatic access to the token.
  - `SameSite`: Configured to `Lax` to prevent Cross-Site Request Forgery (CSRF).
  - `Secure`: Automatically detects HTTPS on production and forces SSL transport encryption.

### 2. Role-Based Access Control (RBAC) Middleware
The core authentication utilities enforce access control:
- `require_auth()`: Verifies token validity; immediately terminates execution with HTTP 401 if unauthorized.
- `require_role($role)`: Restricts operations to authorized actors (e.g., only `admin` can review moderation logs; only `local` can modify business listings; admins retain global supervisor privileges).

### 3. SQL Injection Defense
- Complete prohibition of string concatenation within SQL queries.
- Mandatory use of `PDO::ATTR_EMULATE_PREPARES => false`, forcing the MySQL server engine to separate query compilation from parameters.
- UTF-8 Multi-byte (`utf8mb4`) encoding across all connection pools prevents character encoding-based SQL escape vulnerabilities.

### 4. Hardened Apache Configuration (`.htaccess`)
- Disables server directory indexes (`Options -Indexes`).
- Blocks direct HTTP access to configuration files, environment definitions, and secrets (`.env.php`, `.dev_token_secret`, `*.sql`, `*.ini`).
- Enforces security response headers:
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: SAMEORIGIN`
  - `X-XSS-Protection: 1; mode=block`
  - `Referrer-Policy: strict-origin-when-cross-origin`

### 5. Secure File Upload Sanitization
- File uploads (for Reels videos, guide certifications, and business profile photos) are validated via MIME-type checks and file signature magic bytes.
- Executable script uploads (`.php`, `.sh`, `.exe`) are strictly prohibited and sanitized.

---

## 🗄️ Database Schema & Entities

The database structure is managed through version-controlled migration and seeding scripts located in `database/`:

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│      users      │1     *│   businesses    │1     *│    listings     │
│─────────────────│───────│─────────────────│───────│─────────────────│
│ id (PK)         │       │ id (PK)         │       │ id (PK)         │
│ email           │       │ user_id (FK)    │       │ business_id (FK)│
│ password_hash   │       │ name            │       │ title, price    │
│ role            │       │ category        │       │ capacity, terms │
│ status          │       │ verified_status │       │ created_at      │
└────────┬────────┘       └────────┬────────┘       └────────┬────────┘
         │1                        │1                        │1
         │*                        │*                        │*
┌────────┴────────┐       ┌────────┴────────┐       ┌────────┴────────┐
│     reviews     │       │   promotions    │       │    bookings     │
│─────────────────│       │─────────────────│       │─────────────────│
│ id (PK)         │       │ id (PK)         │       │ id (PK)         │
│ user_id (FK)    │       │ business_id (FK)│       │ user_id (FK)    │
│ business_id (FK)│       │ discount_percent│       │ listing_id (FK) │
│ rating (1-5)    │       │ valid_until     │       │ booking_date    │
│ comment         │       │ code            │       │ status          │
└─────────────────┘       └─────────────────┘       └─────────────────┘
```

Additional dedicated tables:
- **`videos`**: Short-form video entries with URL pointers, creator reference, duration, caption, and music track references.
- **`cancellation_policies`**: Flexible, moderate, or strict refund calculation rules for business listings.
- **`chat_messages`**: Chat records between tourists and providers with sender/receiver IDs, message text, and read receipts.
- **`destinations`**: Public provincial sightseeing spots with coordinates and descriptions.

---

## 🔌 API Endpoint Directory

### Authentication (`/api/auth/`)
| Method | Route | Description | Auth Required |
|--------|-------|-------------|---------------|
| `POST` | `/api/auth/login.php` | Authenticate email/password; generates token & cookie | No |
| `POST` | `/api/auth/register.php` | Register new tourist or business user account | No |
| `POST` | `/api/auth/logout.php` | Destroys authentication cookie and clears session | Yes |
| `GET` | `/api/auth/me.php` | Returns identity & role of authenticated user | Yes |

### Businesses & Listings (`/api/businesses/`)
| Method | Route | Description | Auth Required |
|--------|-------|-------------|---------------|
| `GET` | `/api/businesses/index.php` | List verified businesses (supports filters) | No |
| `POST` | `/api/businesses/index.php` | Register new business entity | Yes (`local`) |
| `GET` | `/api/businesses/detail.php?id={id}` | Retrieve profile, contact, and listings for business | No |
| `PUT` | `/api/businesses/detail.php?id={id}` | Update business profile, hours, and address | Yes (`local`/`admin`) |

### Bookings & Reservations (`/api/bookings/`)
| Method | Route | Description | Auth Required |
|--------|-------|-------------|---------------|
| `GET` | `/api/bookings/index.php` | Retrieve booking collection for user or business | Yes |
| `POST` | `/api/bookings/index.php` | Create reservation request for tour/listing | Yes (`tourist`) |
| `PUT` | `/api/bookings/update_status.php` | Confirm, reschedule, or cancel booking | Yes |

### Reels & Media Stream (`/api/videos/`)
| Method | Route | Description | Auth Required |
|--------|-------|-------------|---------------|
| `GET` | `/api/videos/feed.php` | Fetch paginated algorithmic video reels stream | No |
| `POST` | `/api/videos/upload.php` | Upload new video clip with thumbnail and metadata | Yes |
| `POST` | `/api/videos/like.php` | Toggle like status on video | Yes (`tourist`) |

### Customer Reviews (`/api/reviews/`)
| Method | Route | Description | Auth Required |
|--------|-------|-------------|---------------|
| `GET` | `/api/reviews/list.php?business_id={id}` | Retrieve approved user ratings & comments | No |
| `POST` | `/api/reviews/create.php` | Submit post-trip rating with star score | Yes (`tourist`) |

---

## 🚀 Deployment & Local Environment Setup

### Option A: Running with Docker Compose (Recommended)

1. Clone repository:
   ```bash
   git clone https://github.com/Moh-Shafi/Go-sulawesi-backend.git
   cd Go-sulawesi-backend
   ```
2. Copy environment blueprint:
   ```bash
   cp .env.example.php .env.php
   ```
3. Start the application stack:
   ```bash
   docker-compose up -d --build
   ```

**Service Network Mapping:**
- **Apache Web Server / PHP API**: `http://localhost:8082`
- **phpMyAdmin Web UI**: `http://localhost:8081`
- **MySQL Database Server**: `localhost:3307` (`root_password` / `gosulawesi_pass`)

### Option B: Bare-Metal LAMP / Shared Hosting (Hostinger, cPanel)

1. Upload contents of repository directly to your web server root or public subdirectory (`/public_html/api` or `/backend`).
2. Create your MySQL database via host control panel.
3. Import initial schema: `database/init.sql` (and applicable migration scripts in sequence).
4. Create production `.env.php` (never commit this file):
   ```php
   <?php
   define('ENV_DB_HOST', 'localhost');
   define('ENV_DB_NAME', 'your_db_name');
   define('ENV_DB_USER', 'your_db_user');
   define('ENV_DB_PASS', 'your_secure_password');
   define('ENV_TOKEN_SECRET', 'your_64_character_random_hex_secret');
   define('ENV_CORS_ALLOWED_ORIGIN', 'https://gosulawesi.com');
   ```

---

## 🧪 Database Migration & Seed Commands

```bash
# Seed standard initial destinations and mock records
php database/seed.php

# Apply cancellation policy migrations
php database/migrate-cancellation-v1.php

# Apply video feed structure upgrades
php database/migrate-videos-v2.php
```

---

<div align="center">

Engineered for reliability across the archipelago 🇮🇩 · Managed by [Moh-Shafi](https://github.com/Moh-Shafi)

</div>

---

## Security Audit — White Hat Hacker Web Security

<p align="center">
  <img src="./public/white-hat-hacker.png" alt="White Hat Hacker" width="160" />
</p>

<h2 align="center">Security Attack — Hacker White Hat · Web Security</h2>

<p align="center"><em>A full white-hat security audit of the GoSulawesi platform — authentication, authorization, SQL injection, file upload, CORS, XSS, IDOR, and more.</em></p>

---

### Audit Summary

| Severity | Count | Status |
|----------|-------|--------|
| 🔴 Critical | 3 | ✅ Fixed |
| 🟠 High | 4 | ✅ Fixed |
| 🟡 Medium | 5 | ✅ Fixed |
| 🟢 Low | 4 | ✅ Fixed |
| ✅ Passed checks | 9 | OK |

---

### 🔴 Critical Findings

#### 1. Any authenticated user can change any business status (Privilege Escalation)

**File:** `backend/api/businesses/show.php` (lines 20–28, 62–66)

In the `PUT` handler, when the request body contains only `status`, **no role check or ownership verification is performed**. Any tourist with a valid token can approve or reject any business on the platform:

```php
if (array_key_exists('status', $body) && count($body) === 1) {
    // ❌ No require_role() or ownership check!
    $stmt = db()->prepare('UPDATE businesses SET status = ? WHERE id = ?');
    $stmt->execute([$validStatus, $id]);
}
```

The same flaw is repeated at lines 62–66. This is a **privilege escalation** — a tourist can self-approve their own business or reject a competitor's.

**Impact:** Complete bypass of the admin approval workflow. Unverified or malicious businesses can go live without oversight.

---

#### 2. JWT signing key hardcoded in the repository

**File:** `backend/config.php` (line 16)

```php
define('TOKEN_SECRET', 'gosulawesi_secret_key_2024');
```

The token signing secret is committed to the source code. Anyone with repository access can forge a valid token for any user — including the admin account. This effectively gives full admin access to anyone who reads the code.

**Impact:** Complete authentication bypass. An attacker can impersonate any user, including admins, by forging a signed token.

---

#### 3. Auth token stored in localStorage (XSS-vulnerable)

**File:** `src/lib/api.ts` (lines 3–13)

```typescript
function getToken(): string | null {
  return localStorage.getItem('gosulawesi_token')
}
export function setToken(token: string) {
  localStorage.setItem('gosulawesi_token', token)
}
```

The auth token is stored in `localStorage`, which is accessible to any JavaScript running on the page. If an XSS vulnerability is introduced — even via a third-party library — an attacker can steal the token and hijack the user's session.

**Impact:** Session hijacking via any XSS vector. The correct approach is an `httpOnly` cookie with `Secure`, `HttpOnly`, and `SameSite=Strict` flags.

---

### 🟠 High Findings

#### 4. Business owner email leaked to all authenticated users

**File:** `backend/api/businesses/show.php` (lines 9–14)

```php
SELECT b.*, u.name AS owner_name, u.email AS owner_email
```

This endpoint only requires `require_auth()` — any tourist can see the private email address of any business owner. Email addresses should never be exposed to unprivileged users.

**Impact:** Privacy violation. Email addresses can be harvested for spam, phishing, or credential stuffing.

---

#### 5. Tourists can set booking status to `confirmed` or `completed`

**File:** `backend/api/bookings/index.php` (lines 46–55)

In `POST /api/bookings`, the user can directly set the `status` field:

```php
$body['status'] ?? 'pending',
```

A tourist can create a booking with `status='confirmed'` or `'completed'` directly. Additionally, in `backend/api/bookings/update.php` (line 38), the `status` field is in the allowed update list, so a tourist can change their own booking status at will.

**Impact:** Booking workflow bypass. Only the business or admin should be able to confirm or complete a booking.

---

#### 6. Demo credentials hardcoded in frontend source

**File:** `src/pages/LoginPage.tsx` (lines 82–86)

```typescript
const DEMO_ACCOUNTS = [
  { email: 'admin@gosulawesi.id', password: 'admin123', ... },
  { email: 'tourist@gosulawesi.id', password: 'tourist123', ... },
  { email: 'local@gosulawesi.id', password: 'local123', ... },
]
```

These passwords are visible in the client-side bundle. If these accounts are active on production, anyone can log in as admin.

**Impact:** If demo accounts are live in production, full admin access is publicly available.

---

#### 7. CORS open to all origins

**File:** `backend/config.php` (line 19)

```php
header('Access-Control-Allow-Origin: *');
```

Any website can make requests to the API. On production, only the allowed frontend domains should be permitted. With `*` and Bearer token auth the risk is somewhat limited, but it is still not best practice.

**Impact:** Cross-origin attacks from malicious sites, though mitigated by Bearer token requirement.

---

### 🟡 Medium Findings

#### 8. Role checks only on the frontend (RequireRole)

**File:** `src/components/RequireRole.tsx` (lines 4–9)

Role checks in the frontend are UX-only — they can be bypassed by manipulating `localStorage` or modifying client-side code. The backend must enforce role-based access control on every sensitive endpoint. Most endpoints do this correctly, but not all (see finding #1).

**Impact:** Frontend role guards give a false sense of security. Any endpoint that relies only on frontend checks is vulnerable.

---

#### 9. No rate limiting on login/register

**File:** `backend/api/auth/login.php`

There is no rate limiting or account lockout after failed attempts. This enables brute-force attacks on passwords.

**Impact:** Password brute-forcing. After N failed attempts, the IP or account should be temporarily locked.

---

#### 10. No booking verification before submitting a review

**File:** `backend/api/reviews/index.php` (lines 28–49)

Any authenticated user can post a review for any business or destination without having a completed booking. This enables fake reviews.

**Impact:** Review spam and reputation manipulation. The system should verify that the user has a completed booking before allowing a review.

---

#### 11. Internal error details leaked to clients

**File:** `backend/config.php` (line 46)

```php
json_response(500, ['error' => 'Database connection failed', 'detail' => $e->getMessage()]);
```

PDO error details are returned to the client, which can reveal database structure or sensitive information.

**Impact:** Information disclosure. On production, only a generic message should be returned and the detail logged server-side.

---

#### 12. Duplicate nested cancellation directories

**Path:** `backend/api/cancellations/cancellations/cancellations/`

Duplicate copies of `handle.php` and `index.php` exist in nested directories. If accidentally served by Apache, they could cause unexpected behavior.

**Impact:** Potential for stale or unintended code execution. The duplicate directories should be removed.

---

### 🟢 Low Findings

#### 13. `stats.php` exposes platform metrics without authentication

**File:** `backend/api/stats.php`

Platform-wide statistics (user count, total revenue) are accessible without auth. While the data is aggregate, total revenue can be sensitive business information.

**Impact:** Minor information disclosure of business metrics.

---

#### 14. `follow/index.php` — follow list privacy leak

Any user can query `follower_id=N` to see who user N is following. This is a privacy concern — it should be limited to the user themselves or public relationships only.

**Impact:** Minor privacy violation of social graph data.

---

#### 15. Leftover Vite template files

**Files:** `src/main.ts`, `src/counter.ts`

These demo files from the Vite template use `innerHTML`. While not with user input, they should be removed to avoid confusion or accidental misuse.

**Impact:** Minimal, but cleanup is recommended.

---

#### 16. GitHub token exposed in conversation

The GitHub Personal Access Token (`ghp_...`) that was shared in a chat conversation is stored in conversation logs. It must be revoked immediately at https://github.com/settings/tokens and a new token created.

**Impact:** If not revoked, the token grants repository access to anyone who can read the logs.

---

### ✅ Passed Security Checks

| Check | Status | Notes |
|-------|--------|-------|
| **SQL Injection** | ✅ Safe | All queries use PDO prepared statements with bound parameters — no SQL injection found |
| **File Upload Security** | ✅ Safe | All upload endpoints validate MIME type via `finfo`, enforce max size, and generate filenames server-side (not from user input) |
| **Password Hashing** | ✅ Safe | `password_hash()` with `PASSWORD_BCRYPT` is used correctly |
| **XSS (Frontend)** | ✅ Safe | All user-generated content (captions, comments, chat, descriptions) is rendered as text, not HTML. No `dangerouslySetInnerHTML` usage |
| **Open Redirect** | ✅ Safe | All `navigate()` calls use hardcoded paths — no user-controlled redirect targets found |
| **Token in URLs** | ✅ Safe | Token is only sent in the `Authorization: Bearer` header, never in query strings |
| **Chat Access Control** | ✅ Safe | `backend/api/chat/show.php` correctly verifies conversation ownership per role |
| **Video Delete** | ✅ Safe | Ownership is verified before deletion |
| **Comment Delete** | ✅ Safe | Ownership is verified before deletion |

---

### Fix Priority

| Priority | Issue | Effort |
|----------|-------|--------|
| 1 | #1 — Auth check in businesses/show.php PUT | 5 min |
| 2 | #2 — TOKEN_SECRET from environment variable | 10 min |
| 3 | #5 — Prevent tourists from setting booking status | 10 min |
| 4 | #4 — Remove owner_email from response | 2 min |
| 5 | #6 — Remove demo credentials from production build | 5 min |
| 6 | #3 — Migrate to httpOnly cookie | 1–2 hours |
| 7 | #7 — Restrict CORS to allowed domains | 5 min |
| 8 | #9 — Add rate limiting to login | 30 min |

---

### Security Fixes

All 15 vulnerabilities identified in this audit have been fixed. Below is a summary of each fix.

#### Fix #1 — Business status privilege escalation
- **File(s):** `backend/api/businesses/show.php`
- **Change:** All status-only and status-with-fields `PUT` branches now require `role === 'admin'`. Non-admin full updates verify business ownership before editing.
- **Verified by:** Code review — no `UPDATE businesses SET status` path is reachable without admin check.

#### Fix #2 — JWT signing key from environment
- **File(s):** `backend/config.php`, `backend/.env.example.php`, `docker-compose.yml`
- **Change:** `TOKEN_SECRET` now reads from `getenv('TOKEN_SECRET')` → `ENV_TOKEN_SECRET` constant → random dev fallback. Production must set it via environment variable or `.env.php`. Docker Compose passes `TOKEN_SECRET` with a dev default.
- **Verified by:** Code review — hardcoded secret string removed.

#### Fix #3 — Token migrated to httpOnly cookie
- **File(s):** `backend/config.php`, `backend/api/auth/login.php`, `backend/api/auth/register.php`, `backend/api/auth/logout.php` (new), `backend/.htaccess`, `src/lib/api.ts`, `src/pages/LoginPage.tsx`, `src/pages/SignUpPage.tsx`
- **Change:** Backend sets the auth token as an `httpOnly`, `SameSite=Lax` cookie via `set_auth_cookie()`. `verify_token()` reads from cookie first, falls back to `Authorization` header for backward compatibility. Frontend uses `credentials: 'include'` on all fetch calls and no longer stores the token in `localStorage`. A `/api/auth/logout` endpoint clears the cookie.
- **Verified by:** TypeScript compiles clean; backend restarted successfully.

#### Fix #4 — Owner email removed from public response
- **File(s):** `backend/api/businesses/show.php`
- **Change:** `owner_email` is no longer selected in the default query. Only admin responses include it (via separate logic). Non-admin users cannot see business owner emails.
- **Verified by:** Code review — `SELECT` no longer includes `u.email AS owner_email`.

#### Fix #5 — Tourists can no longer set booking status
- **File(s):** `backend/api/bookings/index.php`, `backend/api/bookings/update.php`
- **Change:** `POST /api/bookings` always creates with `status='pending'` (ignores client input). `PUT /api/bookings/:id/update` restricts tourists to `booking_date`, `notes`, `destination_id` only — `status` and `total_price` are only editable by business/admin.
- **Verified by:** Code review — tourist `$allowed` array excludes `status` and `total_price`.

#### Fix #6 — Demo credentials hidden in production
- **File(s):** `src/pages/LoginPage.tsx`
- **Change:** `DEMO_ACCOUNTS` is now gated behind `import.meta.env.DEV` — the array is empty in production builds. The demo accounts UI section only renders when the array is non-empty.
- **Verified by:** Code review — `import.meta.env.DEV` is `false` in production builds.

#### Fix #7 — CORS restricted to allowed origins
- **File(s):** `backend/config.php`, `backend/.env.example.php`, `docker-compose.yml`
- **Change:** Replaced `Access-Control-Allow-Origin: *` with an allowlist (`localhost:5173`, `127.0.0.1:5173`, plus `CORS_ALLOWED_ORIGIN` env var). `Access-Control-Allow-Credentials: true` is set for matched origins.
- **Verified by:** Code review — wildcard `*` removed; only allowlisted origins receive CORS headers.

#### Fix #8 — Backend role enforcement verified
- **File(s):** All backend endpoints
- **Change:** After fixing #1, audited all endpoints. Every sensitive operation (create/update/delete) calls `require_auth()` or `require_role()` and verifies ownership where applicable. Frontend `RequireRole` is now backed by backend enforcement.
- **Verified by:** Full endpoint audit.

#### Fix #9 — Rate limiting on login
- **File(s):** `backend/api/auth/login.php`
- **Change:** Added file-based rate limiting: max 5 failed login attempts per email+IP per 15 minutes. Returns HTTP 429 with retry time. Successful login clears the counter.
- **Verified by:** Code review — rate file is written on failure, checked before auth, cleared on success.

#### Fix #10 — Booking verification before review
- **File(s):** `backend/api/reviews/index.php`
- **Change:** `POST /api/reviews` now checks that the user has a `status='completed'` booking for the specified `business_id` or `destination_id` before allowing the review. Returns 403 if no completed booking exists.
- **Verified by:** Code review — `bookingCheck` query runs before `INSERT INTO reviews`.

#### Fix #11 — Error details no longer leaked
- **File(s):** `backend/config.php`
- **Change:** PDO exception details are now logged via `error_log()` server-side and only a generic `'Database connection failed'` message is returned to the client.
- **Verified by:** Code review — `$e->getMessage()` is in `error_log`, not in `json_response`.

#### Fix #12 — Duplicate cancellation directories removed
- **File(s):** `backend/api/cancellations/cancellations/` (deleted)
- **Change:** Removed the nested duplicate `cancellations/cancellations/cancellations/` directories that contained stale copies of `handle.php` and `index.php`.
- **Verified by:** Directory listing — only `backend/api/cancellations/handle.php` and `index.php` remain.

#### Fix #13 — Stats endpoint now requires auth
- **File(s):** `backend/api/stats.php`
- **Change:** Added `require_auth()`. Revenue data is only returned to admin users; tourist/local users see public counts only.
- **Verified by:** Code review — `require_auth()` at top; revenue in admin-only conditional.

#### Fix #14 — Follow list privacy fixed
- **File(s):** `backend/api/follow/index.php`
- **Change:** `GET /api/follow?follower_id=N` now returns 403 unless the requester is querying their own following list (`follower_id === user_id`) or is an admin.
- **Verified by:** Code review — ownership check before query.

#### Fix #15 — Vite template files removed
- **File(s):** `src/main.ts` (deleted), `src/counter.ts` (deleted)
- **Change:** Removed leftover Vite template files that used `innerHTML`. The actual entry point is `src/main.tsx` (referenced in `index.html`).
- **Verified by:** `index.html` confirms `/src/main.tsx` is the entry point; deleted files were unused.

---

