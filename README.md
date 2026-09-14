<div align="center">

# ⚙️ GoSulawesi — Backend REST API Engine

[![PHP Version](https://img.shields.io/badge/PHP-8.1%20%7C%208.2%20%7C%208.3-777BB4?style=for-the-badge\&logo=php\&logoColor=white)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge\&logo=mysql\&logoColor=white)](https://www.mysql.com/)
[![Docker Compose](https://img.shields.io/badge/Docker_Compose-Supported-2496ED?style=for-the-badge\&logo=docker\&logoColor=white)](https://www.docker.com/)
[![Apache](https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge\&logo=apache\&logoColor=white)](https://httpd.apache.org/)

The backend engine powering **GoSulawesi**, providing RESTful JSON APIs for authentication, tourism listings, reservations, reviews, messaging, media management, and role-based platform operations.

[🌐 Monorepo Root](https://github.com/Moh-Shafi/Go-sulawesi) · [📱 Frontend App Repo](https://github.com/Moh-Shafi/Go-sulawesi-frontend)

</div>

---

## 📋 System Architecture

The **GoSulawesi Backend** is a lightweight PHP REST API service designed to provide a structured and secure backend layer for the GoSulawesi tourism marketplace.

The application uses a modular API architecture with PHP, PDO, and MySQL, while keeping business logic, authentication, authorization, database access, and request handling separated into maintainable components.

### Architecture Highlights

* **Lightweight PHP Architecture** — Custom controller and middleware structure without unnecessary framework overhead.
* **RESTful API Design** — JSON-based endpoints with predictable HTTP status codes and structured responses.
* **Secure Database Access** — PHP PDO with parameterized queries and prepared statements.
* **Role-Based Access Control** — Server-side authorization for `tourist`, `local`, and `admin` roles.
* **Cookie-Based Authentication** — Signed authentication tokens delivered through secure HTTP-only cookies.
* **Production Deployment** — Deployed on a Hostinger VPS with a LAMP-based production environment.

---

## 🏗️ Technology Stack

| Layer                  | Technology                                    |
| :--------------------- | :-------------------------------------------- |
| **Language**           | PHP 8.1 / 8.2 / 8.3                           |
| **API**                | RESTful JSON API                              |
| **Database**           | MySQL 8.0                                     |
| **Database Access**    | PHP PDO                                       |
| **Web Server**         | Apache 2.4                                    |
| **Authentication**     | HMAC-SHA256 signed tokens + HTTP-only cookies |
| **Authorization**      | Role-Based Access Control (RBAC)              |
| **Containerization**   | Docker / Docker Compose                       |
| **Production Hosting** | Hostinger VPS                                 |
| **Source Control**     | GitHub                                        |

---

## 🛡️ Production Security Standards

The backend follows security practices aligned with common **OWASP recommendations**.

| Security Domain              | Applied Defense                                                                                                                    |
| :--------------------------- | :--------------------------------------------------------------------------------------------------------------------------------- |
| **Authentication & Session** | HMAC-SHA256 signed tokens delivered through `HttpOnly`, `SameSite=Lax`, and `Secure` cookies.                                      |
| **Access Control (RBAC)**    | Server-side role verification for `tourist`, `local`, and `admin` operations.                                                      |
| **Injection Defense**        | Parameterized queries using PHP PDO with prepared statements and native prepares.                                                  |
| **API & Cross-Origin**       | Origin-whitelisted CORS policy for authorized client domains.                                                                      |
| **Brute-Force Mitigation**   | Rate limiting on authentication-related routes.                                                                                    |
| **Secure File Handling**     | MIME validation using `finfo`, extension validation, and randomized server-side filenames.                                         |
| **Configuration Security**   | Sensitive credentials and secrets are provided through environment-specific configuration rather than committed production values. |

> **Note:** Security controls are implemented at the application and server layers and are continuously reviewed as the platform evolves.

---

## 🔌 Core API Directory

### Authentication — `/api/auth/`

| Method | Endpoint                 | Description                              |
| :----- | :----------------------- | :--------------------------------------- |
| `POST` | `/api/auth/login.php`    | User authentication and session creation |
| `POST` | `/api/auth/register.php` | Create a new user account                |
| `POST` | `/api/auth/logout.php`   | Clear the authentication session         |
| `GET`  | `/api/auth/me.php`       | Verify the current authenticated user    |

### Business & Listings — `/api/businesses/`

| Method | Endpoint                             | Description                         |
| :----- | :----------------------------------- | :---------------------------------- |
| `GET`  | `/api/businesses/`                   | List available businesses           |
| `POST` | `/api/businesses/`                   | Create a business listing (`local`) |
| `GET`  | `/api/businesses/detail.php?id={id}` | Retrieve business details           |

### Bookings — `/api/bookings/`

| Method | Endpoint                          | Description                               |
| :----- | :-------------------------------- | :---------------------------------------- |
| `GET`  | `/api/bookings/`                  | Retrieve user/business bookings           |
| `POST` | `/api/bookings/`                  | Create a reservation (`tourist`)          |
| `PUT`  | `/api/bookings/update_status.php` | Update booking status (`local` / `admin`) |

### Additional API Modules

The backend also provides functionality for:

* User profiles
* Destinations
* Reviews
* Messaging
* Media and file uploads
* Itineraries
* Administrative operations
* Business management

---

## 👥 User Roles

GoSulawesi uses server-side RBAC with three primary roles:

```text
                    GoSulawesi API
                          │
          ┌───────────────┼───────────────┐
          │               │               │
       Tourist          Local           Admin
          │               │               │
      Bookings       Businesses      Platform
      Reviews        Services        Management
      Itinerary      Bookings        Users
      Discovery      Content         Content
```

Authorization is enforced on protected endpoints before sensitive operations are processed.

---

## 🗄️ Database

The backend uses **MySQL 8.0** as its relational database.

Core data domains include:

* Users and roles
* Businesses
* Destinations
* Services
* Bookings
* Reviews
* Messages
* Media
* Itineraries
* Administrative data

Database schema and migration-related files are maintained within the repository.

---

## 🐳 Quick Start — Docker

### 1. Clone the repository

```bash
git clone https://github.com/Moh-Shafi/Go-sulawesi-backend.git
cd Go-sulawesi-backend
```

### 2. Configure the environment

Create your local environment configuration based on the provided example:

```bash
cp .env.example.php .env.php
```

> Never place production credentials or private secrets in the repository.

### 3. Start the services

```bash
docker compose up -d
```

### 4. Check running containers

```bash
docker ps
```

### Local Services

| Service         | URL                     |
| :-------------- | :---------------------- |
| **Backend API** | `http://localhost:8082` |
| **phpMyAdmin**  | `http://localhost:8081` |

The exact ports may be adjusted through the Docker Compose configuration.

---

## 🚀 Production Deployment

The GoSulawesi backend is currently deployed on a **Hostinger VPS**.

### Production Architecture

```text
                    Internet
                       │
                       ▼
                Hostinger VPS
                       │
                ┌──────┴──────┐
                │   Apache    │
                └──────┬──────┘
                       │
                       ▼
              GoSulawesi REST API
                       │
                       ▼
                  MySQL 8.0
```

### Deployment Workflow

1. Prepare the production VPS environment.
2. Configure Apache and PHP.
3. Configure the production database.
4. Deploy the backend source code.
5. Configure environment-specific secrets.
6. Apply database schema/migrations.
7. Configure the production API endpoint.
8. Verify authentication and protected API routes.
9. Test critical booking and business workflows.

The production Web App is currently deployed and ready for use.

---

## 🔗 Frontend Application

The GoSulawesi frontend is maintained in a separate repository:

**GoSulawesi Frontend:**
https://github.com/Moh-Shafi/Go-sulawesi-frontend

Frontend technology stack:

* React 19
* TypeScript
* Vite
* Tailwind CSS
* React Router

The frontend communicates with this backend through the REST API.

---

## 🔄 Application Flow

```text
React / TypeScript Frontend
            │
            ▼
       REST API Layer
            │
            ▼
 Authentication + RBAC
            │
            ▼
     Business Logic
            │
            ▼
        PDO / MySQL
```

Protected requests are authenticated and authorized by the backend before sensitive resources are accessed or modified.

---

## 📊 Current Platform Features

* 🌏 Tourism destination discovery
* 🏪 Local business listings
* 👤 Tourist accounts
* 🏢 Local business accounts
* 🛡️ Administrative management
* 📅 Booking and reservation management
* ⭐ Reviews
* 💬 Messaging
* 🎥 Media uploads
* 🗺️ Itinerary management
* 🔐 Role-based access control
* 🔑 Secure authentication
* 🐳 Docker-based development environment
* ☁️ Production VPS deployment

---

## 🗺️ Future Roadmap

Planned integrations and improvements include:

### 💳 Payment Gateway

* Midtrans
* Xendit

Planned for automated local payment processing, including QRIS, Virtual Accounts, and supported e-wallet payment methods.

### 🗺️ Maps & Navigation

* Google Maps API
* Leaflet + OpenStreetMap

Planned for interactive destination mapping and navigation features.

> These integrations are part of the future roadmap and are not currently presented as production dependencies.

---

## 🌴 Project Background

GoSulawesi is a tourism marketplace focused on connecting international tourists with local businesses and tourism services across Sulawesi, Indonesia.

The project was developed based on research involving:

* **18 international tourists from 9 countries**
* **12 local businesses**

The research helped identify challenges faced by international tourists when discovering destinations and accessing local tourism services.

---

## 👨‍💻 Developer

**Abdul Shafi Afzal Ehrari**

Full-Stack Software Developer
Indonesia

* GitHub: https://github.com/Moh-Shafi
* GoSulawesi Frontend: https://github.com/Moh-Shafi/Go-sulawesi-frontend
* GoSulawesi Backend: https://github.com/Moh-Shafi/Go-sulawesi-backend

---

<div align="center">

Made with ❤️ for **Sulawesi, Indonesia** 🇮🇩

</div>
