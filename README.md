<div align="center">

# ⚙️ GoSulawesi — Backend REST API Engine

[![PHP Version](https://img.shields.io/badge/PHP-8.1%20%7C%208.2%20%7C%208.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker Compose](https://img.shields.io/badge/Docker_Compose-Supported-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
[![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org/)

The authoritative backend engine powering GoSulawesi: delivering low-latency JSON APIs, transactional reservation processing, file storage services, and role-based access management.

[🌐 Monorepo Root](https://github.com/Moh-Shafi/Go-sulawesi) · [📱 Frontend App Repo](https://github.com/Moh-Shafi/Go-sulawesi-frontend)

</div>

---

## 📋 System Architecture

The **GoSulawesi Backend** is architected as a lightweight, secure, and dependency-free PHP REST service running against an ACID-compliant MySQL relational database.

- **Zero Heavy Framework Bloat**: Custom controller & middleware routing layer utilizing PHP 8 native PDO prepared statements.
- **RESTful Endpoints**: Predictable JSON responses with standard HTTP status code semantics.
- **Stateless Verification with Stateful Cookie Storage**: High security through HMAC-SHA256 encrypted signatures stored in `httpOnly` browser cookies.

---

## 🛡️ Production Security Standards (OWASP Aligned)

| Security Domain | Applied Defense | Compliance |
| :--- | :--- | :---: |
| **Authentication & Session** | Signed tokens via HMAC-SHA256 delivered strictly via `HttpOnly`, `SameSite=Lax`, and `Secure` cookies. | ✅ **Compliant** |
| **Access Control (RBAC)** | Strict server-side role verification (`tourist`, `local`, `admin`) on every mutation endpoint. | ✅ **Compliant** |
| **Injection Defense** | 100% parameter-bound queries via PHP PDO (`PDO::ATTR_EMULATE_PREPARES => false`) with `utf8mb4` charset. | ✅ **Compliant** |
| **API & Cross-Origin** | Origin-whitelisted CORS policy matching authorized client domains only. | ✅ **Compliant** |
| **Brute-Force Mitigation** | Rate limiting on authentication routes. | ✅ **Compliant** |
| **Secure File Handling** | MIME-type validation via `finfo`, strict file extension checks, randomized server-side filenames. | ✅ **Compliant** |

---

## 🔌 Core API Directory

### Authentication (`/api/auth/`)
- `POST /api/auth/login.php` — User authentication & session set
- `POST /api/auth/register.php` — Account creation
- `POST /api/auth/logout.php` — Clear auth session
- `GET /api/auth/me.php` — User verification

### Business & Listings (`/api/businesses/`)
- `GET /api/businesses/` — List verified businesses
- `POST /api/businesses/` — Create listing (`local` role)
- `GET /api/businesses/detail.php?id={id}` — Business profile

### Bookings (`/api/bookings/`)
- `GET /api/bookings/` — List user/business bookings
- `POST /api/bookings/` — Create reservation (`tourist` role)
- `PUT /api/bookings/update_status.php` — Update status (`local`/`admin`)

---

## 🚀 Quick Start & Docker Deployment

```bash
git clone https://github.com/Moh-Shafi/Go-sulawesi-backend.git
cd Go-sulawesi-backend
cp .env.example.php .env.php
docker-compose up -d
```

- **API Endpoint**: `http://localhost:8082`
- **phpMyAdmin**: `http://localhost:8081`

---

<div align="center">

Made with ❤️ for **Sulawesi, Indonesia** 🇮🇩 · Managed by [Moh-Shafi](https://github.com/Moh-Shafi)

</div>