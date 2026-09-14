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