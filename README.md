<div align="center">

# ⚙️ GoSulawesi — Backend

### PHP REST API + MySQL + Docker

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com)
[![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org)

This is the **backend API** of the GoSulawesi tourism platform.  
For the full project overview, visit the [main repository](https://github.com/Moh-Shafi/Go-sulawesi).

</div>

---

## 🚀 Getting Started

### With Docker (Recommended)

```bash
git clone https://github.com/Moh-Shafi/Go-sulawesi-backend.git
cd Go-sulawesi-backend
cp .env.example.php .env.php
# Edit .env.php with your database credentials
docker-compose up -d
```

| Service | URL |
|---------|-----|
| PHP API | http://localhost:8082 |
| phpMyAdmin | http://localhost:8081 |
| MySQL | localhost:3307 |

### Without Docker

Requirements: PHP 8+, MySQL 8.0, Apache with mod_rewrite

```bash
# Configure your web server to point to /backend
# Import database/init.sql into MySQL
# Copy .env.example.php to .env.php and configure
```

---

## 📁 Project Structure

```
backend/
├── api/                    # REST API endpoints
│   ├── auth/               # Login, logout, register
│   ├── bookings/           # Booking management
│   ├── businesses/         # Business listings
│   ├── cancellations/      # Cancellation policies
│   ├── chat/               # Messaging system
│   ├── dashboard/          # Dashboard stats
│   ├── destinations/       # Travel destinations
│   ├── follow/             # Follow/unfollow
│   ├── promotions/         # Promotions & deals
│   ├── reviews/            # Reviews & ratings
│   ├── users/              # User management
│   ├── videos/             # Video feed (Reels)
│   └── stats.php           # Platform statistics
├── database/
│   ├── init.sql            # Initial database schema
│   ├── seed.php            # Sample data seeder
│   └── migrations/         # Database migrations
├── uploads/                # User uploaded files
├── sounds/                 # Audio assets
├── config.php              # App configuration & auth
├── .env.example.php        # Environment template
├── .htaccess               # Apache URL rewriting
└── Dockerfile
```

---

## 🔌 API Endpoints

### Auth
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login.php` | Login user |
| POST | `/api/auth/register.php` | Register new user |
| POST | `/api/auth/logout.php` | Logout user |
| GET | `/api/auth/me.php` | Get current user |

### Businesses
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/businesses/` | List all businesses |
| POST | `/api/businesses/` | Create business |
| GET | `/api/businesses/:id` | Get business detail |
| PUT | `/api/businesses/:id` | Update business |

### Bookings
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/bookings/` | List bookings |
| POST | `/api/bookings/` | Create booking |
| PUT | `/api/bookings/:id` | Update booking status |

### Reviews
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/reviews/` | List reviews |
| POST | `/api/reviews/` | Submit review |

### Videos (Reels)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/videos/` | Get video feed |
| POST | `/api/videos/` | Upload video |

---

## 🔐 Authentication

Authentication uses **HMAC-SHA256 signed tokens** stored as **httpOnly cookies** (XSS-resistant).

```
Token Format: base64(payload) . hmac_sha256(payload, TOKEN_SECRET)
Expiry: 7 days
Storage: httpOnly cookie (gosulawesi_token)
```

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | MySQL host | `db` (Docker) |
| `DB_NAME` | Database name | `gosulawesi` |
| `DB_USER` | Database user | `gosulawesi_user` |
| `DB_PASS` | Database password | `gosulawesi_pass` |
| `TOKEN_SECRET` | JWT signing secret | *(auto-generated in dev)* |
| `CORS_ALLOWED_ORIGIN` | Allowed frontend origin | `http://localhost:5173` |

---

## 👥 User Roles

| Role | Value | Description |
|------|-------|-------------|
| Tourist | `tourist` | End user / traveler |
| Business | `local` | Local business owner / guide |
| Admin | `admin` | Platform administrator |

---

## 🔗 Related Repositories

| Repo | Description |
|------|-------------|
| [Go-sulawesi](https://github.com/Moh-Shafi/Go-sulawesi) | Main repo & documentation |
| [Go-sulawesi-frontend](https://github.com/Moh-Shafi/Go-sulawesi-frontend) | React frontend |

---

<div align="center">

Made with ❤️ for the people of Sulawesi, Indonesia 🇮🇩

</div>
