<div align="center">

# ⚙️ GoSulawesi — Backend REST API Engine

[![PHP Version](https://img.shields.io/badge/PHP-8.1%20%7C%208.2%20%7C%208.3-777BB4?style=for-the-badge\&logo=php\&logoColor=white)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge\&logo=mysql\&logoColor=white)](https://www.mysql.com/)
[![Docker Compose](https://img.shields.io/badge/Docker_Compose-Supported-2496ED?style=for-the-badge\&logo=docker\&logoColor=white)](https://www.docker.com/)
[![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge\&logo=apache\&logoColor=white)](https://httpd.apache.org/)

The backend engine powering **GoSulawesi**, a tourism marketplace platform connecting tourists, local businesses, and tourism destinations across Sulawesi, Indonesia.

It provides REST APIs for authentication, businesses, destinations, bookings, reviews, messaging, media, role-based access control, and platform management.

[🌐 Monorepo Root](https://github.com/Moh-Shafi/Go-sulawesi) ·
[🎨 Frontend Repository](https://github.com/Moh-Shafi/Go-sulawesi-frontend)

</div>

---

# 📋 Table of Contents

* [System Architecture](#-system-architecture)
* [Technology Stack](#-technology-stack)
* [User Roles](#-user-roles)
* [Core Features](#-core-features)
* [API Structure](#-api-structure)
* [Security](#-production-security--white-hat-audit)
* [Database](#-database)
* [Local Development](#-local-development)
* [Docker Deployment](#-docker-deployment)
* [Production Deployment](#-production-deployment)
* [Environment Configuration](#-environment-configuration)
* [Project Structure](#-project-structure)
* [Application Flow](#-application-flow)
* [Roadmap](#-roadmap)
* [Project Background](#-project-background)
* [Developer](#-developer)

---

# 🏗️ System Architecture

The **GoSulawesi Backend** is a lightweight PHP REST API service connected to a MySQL relational database.

The backend follows a modular API architecture with authentication middleware, role-based authorization, database abstraction, and resource-specific endpoints.

### Architecture

```text
┌───────────────────────────────────────────┐
│              GoSulawesi Frontend          │
│        React + TypeScript + Vite          │
└─────────────────────┬─────────────────────┘
                      │
                      │ HTTPS / REST API
                      ▼
┌───────────────────────────────────────────┐
│            GoSulawesi Backend             │
│                                           │
│  Authentication                           │
│  RBAC / Authorization                     │
│  Business Logic                           │
│  Booking Management                       │
│  Review System                            │
│  Media / File Handling                    │
│  Messaging                                │
└─────────────────────┬─────────────────────┘
                      │
                      │ PDO
                      ▼
┌───────────────────────────────────────────┐
│                 MySQL                     │
│       Relational Production Database      │
└───────────────────────────────────────────┘
```

### Design Principles

* **Lightweight REST API** — focused PHP backend without unnecessary framework overhead.
* **Modular API structure** — resources are separated into dedicated endpoint directories.
* **Prepared SQL statements** — database queries use parameter binding.
* **Server-side authorization** — sensitive operations are protected at the API layer.
* **Cookie-based authentication** — authentication tokens can be delivered through secure `HttpOnly` cookies.
* **Production-ready deployment** — supports Docker-based development and Hostinger/VPS deployment.

---

# 🧰 Technology Stack

| Layer                  | Technology                |
| :--------------------- | :------------------------ |
| **Language**           | PHP 8.1 / 8.2 / 8.3       |
| **Database**           | MySQL 8.0                 |
| **API**                | Custom PHP REST API       |
| **Database Access**    | PDO                       |
| **Web Server**         | Apache 2.4                |
| **Containerization**   | Docker / Docker Compose   |
| **Authentication**     | HMAC-SHA256 signed tokens |
| **Authorization**      | Role-Based Access Control |
| **Frontend**           | React + TypeScript        |
| **Production Hosting** | Hostinger VPS             |
| **Source Control**     | Git / GitHub              |

---

# 👥 User Roles

GoSulawesi uses role-based access control to separate platform responsibilities.

### 🧳 Tourist

Tourists can:

* Browse destinations
* Explore local businesses
* View business details
* Create bookings
* Manage their bookings
* Send messages
* Submit reviews after eligible bookings
* Follow other users
* Build travel plans

### 🏪 Local Business

Local users can:

* Create business listings
* Manage business information
* Manage bookings
* Update booking status
* Manage business media
* Interact with tourists
* Manage tourism-related content

### 🛡️ Administrator

Administrators can:

* Manage users
* Approve and manage businesses
* Manage platform content
* Monitor bookings
* Access administrative statistics
* Manage sensitive platform operations

> Authorization is enforced on the backend rather than relying only on frontend route protection.

---

# 🚀 Core Features

### 🔐 Authentication

* User registration
* Login / logout
* Authentication verification
* Secure password hashing
* Token-based authentication
* Secure cookie support
* Session validation

### 🏪 Business Marketplace

* Business registration
* Business profiles
* Business verification
* Business listings
* Business ownership
* Business media
* Business management

### 🗺️ Tourism & Destinations

* Destination discovery
* Destination details
* Tourism content
* Media and video content
* Travel planning

### 📅 Booking System

* Create reservations
* Booking management
* Booking status workflow
* Business-side booking management
* Tourist booking management
* Ownership verification

### ⭐ Review System

* Business reviews
* Destination reviews
* Review authorization
* Completed-booking verification
* Review management

### 💬 Messaging

* User conversations
* Role-based conversation access
* Conversation ownership verification

### 📁 Media Management

* Image uploads
* Video uploads
* MIME validation
* File-size restrictions
* Server-generated filenames

---

# 🔌 Core API Structure

```text
api/
├── auth/
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── me.php
│
├── businesses/
│   ├── index.php
│   ├── show.php
│   └── ...
│
├── bookings/
│   ├── index.php
│   ├── update.php
│   └── ...
│
├── reviews/
│   └── index.php
│
├── videos/
│   └── ...
│
├── chat/
│   └── ...
│
├── follow/
│   └── ...
│
└── stats.php
```

### Authentication

```text
POST /api/auth/register.php
POST /api/auth/login.php
POST /api/auth/logout.php
GET  /api/auth/me.php
```

### Businesses

```text
GET  /api/businesses/
POST /api/businesses/
GET  /api/businesses/detail.php?id={id}
PUT  /api/businesses/show.php?id={id}
```

### Bookings

```text
GET  /api/bookings/
POST /api/bookings/
PUT  /api/bookings/update.php?id={id}
```

### Reviews

```text
GET  /api/reviews/
POST /api/reviews/
```

---

# 🛡️ Production Security & White-Hat Audit

Security was a core part of the GoSulawesi development process.

The platform underwent a **White-Hat security audit** covering:

* Authentication
* Authorization
* SQL Injection
* File Upload Security
* CORS
* XSS
* IDOR / ownership verification
* Privacy
* Business-logic vulnerabilities
* Session security
* Information disclosure

## 🔐 Security Controls

| Security Area        | Protection                                               |
| :------------------- | :------------------------------------------------------- |
| **Authentication**   | HMAC-SHA256 signed authentication tokens                 |
| **Session Security** | `HttpOnly`, `Secure`, and `SameSite` cookie protections  |
| **Access Control**   | Server-side RBAC for `tourist`, `local`, and `admin`     |
| **Authorization**    | Role and ownership verification                          |
| **SQL Injection**    | PDO prepared statements with bound parameters            |
| **CORS**             | Origin allowlist                                         |
| **Brute Force**      | Login rate limiting                                      |
| **File Uploads**     | MIME validation, size limits, server-generated filenames |
| **Review Integrity** | Completed-booking verification                           |
| **Error Handling**   | Sensitive database errors are logged server-side         |
| **Privacy**          | Restricted access to private platform information        |

---

## 🧪 White-Hat Audit Results

The audit identified **15 security findings** across different severity levels.

### Audit Summary

| Severity            | Findings |    Status    |
| :------------------ | :------: | :----------: |
| 🔴 Critical         |     3    | ✅ Remediated |
| 🟠 High             |     4    | ✅ Remediated |
| 🟡 Medium           |     5    | ✅ Remediated |
| 🟢 Low              |     3    | ✅ Remediated |
| ✅ Additional Checks |     9    |    Passed    |

**Current status: All identified findings have been remediated according to the project's audit and verification records.**

---

## 🔧 Key Security Remediations

### 1. Business Status Privilege Escalation

**Issue:**
Authenticated users could previously attempt to modify business approval status without sufficient authorization.

**Remediation:**

* Business status changes require appropriate authorization.
* Administrative operations require the `admin` role.
* Business owners are verified for permitted operations.

**Status:** ✅ Remediated

---

### 2. Token Secret Configuration

**Issue:**
Authentication secrets were previously stored directly in source configuration.

**Remediation:**

* Token secrets are loaded through environment-based configuration.
* Production deployments should provide their own secret values.
* Secrets are not intended to be committed to the repository.

**Status:** ✅ Remediated

---

### 3. Authentication Token Storage

**Issue:**
Authentication tokens were previously stored in browser `localStorage`.

**Remediation:**

* Authentication moved to `HttpOnly` cookies.
* Cookies use appropriate security attributes.
* Frontend requests use credentialed requests where required.
* Logout clears the authentication cookie.

**Status:** ✅ Remediated

---

### 4. Business Owner Privacy

**Issue:**
Business owner email information could previously be exposed through public business responses.

**Remediation:**

* Private owner information was removed from normal public responses.
* Sensitive information is restricted to authorized administrative contexts.

**Status:** ✅ Remediated

---

### 5. Booking Status Manipulation

**Issue:**
Tourists could previously attempt to submit or modify privileged booking statuses.

**Remediation:**

* New tourist bookings default to `pending`.
* Tourists cannot directly modify privileged booking status.
* Business/admin roles control appropriate booking workflow transitions.

**Status:** ✅ Remediated

---

### 6. Production Demo Credentials

**Issue:**
Demo account information was previously included in frontend source code.

**Remediation:**

* Demo functionality is restricted to development environments.
* Production builds do not expose the demo-account interface.

**Status:** ✅ Remediated

---

### 7. CORS Configuration

**Issue:**
The API previously allowed wildcard cross-origin access.

**Remediation:**

* Wildcard CORS was replaced with an origin allowlist.
* Authorized frontend origins are explicitly configured.

**Status:** ✅ Remediated

---

### 8. Backend Role Enforcement

**Issue:**
Frontend role protection alone is insufficient for API security.

**Remediation:**

Sensitive backend operations now require:

* Authentication
* Appropriate role authorization
* Ownership verification where applicable

**Status:** ✅ Remediated

---

### 9. Login Rate Limiting

**Issue:**
Login endpoints previously lacked sufficient brute-force protection.

**Remediation:**

* Failed login attempts are rate limited.
* Repeated failures receive HTTP `429`.
* Successful authentication clears the applicable failure counter.

**Status:** ✅ Remediated

---

### 10. Review Authorization

**Issue:**
Users could previously submit reviews without verification of an eligible booking.

**Remediation:**

The API verifies that the user has a completed booking before accepting an eligible review.

**Status:** ✅ Remediated

---

### 11. Database Error Disclosure

**Issue:**
Internal database exception details could previously be returned to API clients.

**Remediation:**

* Detailed errors are logged server-side.
* Clients receive generic production-safe error messages.

**Status:** ✅ Remediated

---

### 12. Duplicate Backend Files

**Issue:**
Unused nested cancellation directories existed in the backend source tree.

**Remediation:**

* Duplicate directories were removed.
* Only the intended cancellation handlers remain.

**Status:** ✅ Remediated

---

### 13. Platform Statistics Protection

**Issue:**
Platform statistics were previously accessible without sufficient authentication.

**Remediation:**

* Authentication is required.
* Sensitive business metrics are restricted to authorized administrative access.

**Status:** ✅ Remediated

---

### 14. Follow-List Privacy

**Issue:**
Users could previously query another user's following relationships.

**Remediation:**

* Following lists are restricted to the appropriate user context.
* Administrative access is handled separately.

**Status:** ✅ Remediated

---

### 15. Unused Frontend Template Code

**Issue:**
Unused Vite template files remained in the project.

**Remediation:**

* Unused template files were removed.
* The application uses the intended React entry point.

**Status:** ✅ Remediated

---

# ✅ Security Checks Passed

| Security Check                  |  Result  |
| :------------------------------ | :------: |
| SQL Injection                   | ✅ Passed |
| File Upload Security            | ✅ Passed |
| Password Hashing                | ✅ Passed |
| XSS Protection                  | ✅ Passed |
| Open Redirect                   | ✅ Passed |
| Token Exposure in URLs          | ✅ Passed |
| Chat Access Control             | ✅ Passed |
| Resource Ownership Verification | ✅ Passed |
| Booking Authorization           | ✅ Passed |

> **Security principle:** Security-sensitive operations are enforced at the backend/API layer and are not dependent solely on frontend restrictions.

---

# 🗄️ Database

GoSulawesi uses **MySQL 8.0** as its relational database.

Database access is handled through PHP PDO with prepared statements and parameter binding.

The database supports major platform domains including:

```text
Users
Businesses
Destinations
Bookings
Reviews
Messages
Followers
Media
Statistics
```

---

# 🚀 Local Development

## Requirements

* PHP 8.1+
* MySQL 8.0+
* Apache 2.4+
* Docker
* Docker Compose

### Clone Repository

```bash
git clone https://github.com/Moh-Shafi/Go-sulawesi-backend.git
cd Go-sulawesi-backend
```

### Environment Configuration

```bash
cp .env.example.php .env.php
```

Configure the required database and authentication variables before starting the application.

---

# 🐳 Docker Deployment

The backend includes Docker Compose configuration for local development.

```bash
docker compose up -d
```

Typical local services:

```text
API
http://localhost:8082

phpMyAdmin
http://localhost:8081

MySQL
localhost:3307
```

Check running containers:

```bash
docker compose ps
```

Stop services:

```bash
docker compose down
```

---

# 🌐 Production Deployment

GoSulawesi has been deployed using a **Hostinger VPS** environment.

Production architecture:

```text
Internet
   │
   ▼
Hostinger VPS
   │
   ├── Apache
   │
   ├── PHP
   │
   ├── GoSulawesi REST API
   │
   └── MySQL Database
```

The production environment separates application configuration and secrets from source-code configuration.

### Production Deployment Flow

```text
GitHub
   │
   ▼
Source Code
   │
   ▼
Hostinger VPS
   │
   ├── Backend API
   ├── MySQL
   └── Uploaded Media
          │
          ▼
      GoSulawesi
```

---

# 🔑 Environment Configuration

Production secrets should be supplied through environment configuration rather than committed to Git.

Example:

```env
TOKEN_SECRET=your-production-secret
CORS_ALLOWED_ORIGIN=https://your-frontend-domain.com

DB_HOST=localhost
DB_NAME=gosulawesi
DB_USER=your_database_user
DB_PASSWORD=your_database_password
```

> Never commit real passwords, API keys, payment credentials, or authentication secrets to the repository.

---

# 📁 Project Structure

```text
Go-sulawesi-backend/
│
├── api/
│   ├── auth/
│   ├── businesses/
│   ├── bookings/
│   ├── chat/
│   ├── follow/
│   ├── reviews/
│   ├── videos/
│   └── stats.php
│
├── database/
│
├── uploads/
│
├── sounds/
│
├── .env.example.php
├── .gitignore
├── .htaccess
├── Dockerfile
├── docker-compose.yml
├── docker-compose.override.yml
├── config.php
├── apache-config.conf
└── README.md
```

---

# 🔄 Application Flow

### Authentication

```text
User
  │
  ▼
Login / Register
  │
  ▼
Backend Authentication
  │
  ▼
Signed Authentication Token
  │
  ▼
Secure Cookie
  │
  ▼
Authenticated API Requests
```

### Booking

```text
Tourist
   │
   ▼
Select Business / Destination
   │
   ▼
Create Booking
   │
   ▼
Pending
   │
   ▼
Business / Admin
   │
   ▼
Confirmed
   │
   ▼
Completed
   │
   ▼
Eligible Review
```

### Business Management

```text
Local Business
      │
      ▼
Create Listing
      │
      ▼
Verification
      │
      ▼
Approved
      │
      ▼
Published Business
      │
      ▼
Bookings & Customers
```

---

# 🗺️ Roadmap

Planned integrations and improvements include:

### 💳 Payment Integration

Potential payment gateway integrations:

* Midtrans
* Xendit

The payment layer is planned to support secure online booking payments and transaction processing.

### 📍 Maps & Location Services

Potential integrations:

* Google Maps API
* Leaflet
* OpenStreetMap

These services can support:

* Destination locations
* Business locations
* Interactive maps
* Travel planning
* Location-based discovery

### 📱 Mobile Application

A Flutter mobile application is planned using the same backend API architecture.

```text
                GoSulawesi API
                      │
             ┌────────┴────────┐
             ▼                 ▼
       React Web App       Flutter App
```

---

# 🌴 Project Background

**GoSulawesi** was created as a tourism marketplace concept focused on improving how tourists discover local destinations and businesses across Sulawesi.

The project connects three major groups:

```text
Tourists
   │
   ├── Discover destinations
   ├── Find businesses
   ├── Book experiences
   └── Share reviews
        │
        ▼
GoSulawesi Platform
        │
        ▼
Local Businesses
   │
   ├── Promote services
   ├── Manage listings
   ├── Manage bookings
   └── Connect with tourists
```

The platform was developed with a focus on:

* Local tourism
* Digital marketplace infrastructure
* Secure API development
* Role-based workflows
* Scalable backend architecture
* Real-world deployment

---

# 📚 Related Repositories

### Backend

https://github.com/Moh-Shafi/Go-sulawesi-backend

### Frontend

https://github.com/Moh-Shafi/Go-sulawesi-frontend

### Monorepo / Main Project

https://github.com/Moh-Shafi/Go-sulawesi

---

# 👨‍💻 Developer

**Abdul Shafi Afzal Ehrari**

Full-Stack Software Developer & Informatics Student

* GitHub: https://github.com/Moh-Shafi
* LinkedIn: https://linkedin.com/in/shafi-afzalehrari

---

<div align="center">

### 🌴 GoSulawesi

**Connecting tourists with local experiences across Sulawesi, Indonesia 🇮🇩**

Built with ❤️ using PHP, MySQL, React, TypeScript, and modern web technologies.

</div>
