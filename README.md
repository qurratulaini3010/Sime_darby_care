# Sime Darby Care

A role-based PHP web application for township issue reporting and assistance coordination in **Elmina Township**. Premise owners (homes and shops) register, report issues and track updates; an operator coordinates the response; and service providers carry out the work.

> Tagline: *Your township, connected to help.*

--- live link - https://simedarbycare.ct.ws/sime_darby_care_php/login.php
--- Demo Link - https://youtu.be/OIw4Pd5K8xw

## Introduction

Sime Darby Care is a team project that explores how a township can respond to residents' problems faster and more transparently. In many neighbourhoods, reporting a problem means calling around, waiting for a reply and never knowing who is handling it. Our platform puts residents, the township coordination centre and service providers on one system.

- **Residents (owners)** register their home or shop and report issues.
- **The operator** at the coordination centre receives requests and coordinates the response.
- **Service providers**, such as plumbers, take on assigned work and keep everyone updated.

The project is built by a team of four members, covering the full stack: user authentication, database design, role-based dashboards and the user interface. Elmina Township is our pilot community.

### Project Objectives

1. Give premise owners a simple way to register and request help.
2. Give the township operator a single place to coordinate assistance.
3. Give service providers a clear view of the work assigned to them.
4. Keep all three roles connected through status updates and secure, role-based access.

---

## Features

- **Premise registration**: owners sign up with their contact details and register a Home or Shop (address and township) in a single step.
- **Secure authentication**: password hashing, session ID regeneration on login and registration, and a clean logout that destroys the session and cookie.
- **Role-based routing**: after sign-in, users are redirected to the dashboard for their role.
- **CSRF protection** on all forms via `verify_csrf()` and `csrf_field()`.
- **Output escaping** with the `e()` helper to reduce XSS risk.
- **Demo accounts** shown on the login page for quick testing.

## User Roles

| Role | Landing page | Purpose |
|------|--------------|---------|
| Owner | `owner/index.php` | Premise owners who report issues and track updates |
| Operator | `operator/index.php` | Township coordination centre that manages and assigns requests |
| Provider | `provider/index.php` | Service providers (e.g. plumbers) who handle assigned work |

The root `index.php` checks the current session and sends the user to the right dashboard, or to `login.php` if not signed in.

## Tech Stack

- **Backend:** PHP (procedural), PDO with prepared statements
- **Database:** MySQL / MariaDB (via PDO)
- **Frontend:** Server-rendered HTML and CSS (shared `header.php` / `footer.php` layout)
- **Sessions:** native PHP sessions

## Project Structure

```
.
├── index.php          # Entry point: redirects by role or to login
├── login.php          # Sign-in form and authentication
├── register.php       # Owner + premise registration
├── logout.php         # Destroys session and redirects to login
├── includes/
│   ├── config.php     # DB connection ($pdo), session start, BASE_URL
│   ├── functions.php  # Helpers: current_user(), redirect(), e(), CSRF
│   ├── header.php     # Shared layout header
│   └── footer.php     # Shared layout footer
├── owner/             # Owner dashboard and pages
├── operator/          # Operator dashboard and pages
└── provider/          # Provider dashboard and pages
```

> The `includes/`, `owner/`, `operator/` and `provider/` contents are referenced by the authentication files above. Update this tree to match your actual folders.

## Database

The authentication flow uses at least these two tables.

**`users`**

| Column | Notes |
|--------|-------|
| `id` | Primary key |
| `full_name` | |
| `phone` | |
| `email` | Unique, stored lowercase |
| `password_hash` | Generated with `password_hash()` |
| `role` | `owner`, `operator` or `provider` |
| `is_active` | Only active users can sign in |

**`premises`**

| Column | Notes |
|--------|-------|
| `owner_id` | References `users.id` |
| `property_type` | `Home` or `Shop` |
| `address` | |
| `township` | Defaults to `Elmina` |

Registration inserts into both tables inside a single transaction, so a failed insert leaves no partial data. Add your own schema/seed SQL file (for example `database/schema.sql`) and reference it here.

## Getting Started

### Prerequisites

- PHP 8.0 or newer
- MySQL or MariaDB
- A local server stack such as XAMPP, Laragon or MAMP, or PHP's built-in server

### Installation

1. Clone or copy the project into your web root (e.g. `htdocs/simecare`).
2. Create a database and import your schema and seed data.
3. Edit `includes/config.php` with your database credentials and set `BASE_URL` to match where the app is served (e.g. `/simecare`).
4. Open the app in your browser, e.g. `http://localhost/simecare/`.

## Demo Accounts

All seeded accounts use the password **`Demo123!`**.

| Role | Email |
|------|-------|
| Owner | `amira.owner@example.com` |
| Operator | `operator@simecare.test` |
| Provider | `plumber@simecare.test` |

> Remove the demo accounts table from `login.php` and change these passwords before any real deployment.

## Security Notes

- Passwords are hashed with `password_hash()` and checked with `password_verify()`.
- All database queries use PDO prepared statements.
- `session_regenerate_id(true)` runs on login and registration to prevent session fixation.
- Forms include CSRF tokens; output is escaped before rendering.
- Registration requires a valid email, a password of at least 8 characters, and a Home/Shop premise type.

## Roadmap Ideas

- Issue reporting with photo uploads and status tracking
- Operator assignment of requests to providers
- Notifications for status changes
- Self-service password reset
- Rate limiting on login attempts

## Team

We are a team of four members.

| # | Name | Student ID | Role / Responsibility |
|---|------|------------|-----------------------|
| 1 | Qurratul Aini   | 2024654176 | Developing the prototype |
| 2 | Amirah Zikri    | 2024201774 | Project lead, writing Pitch Deck |
| 3 | Hazirah Putri   | 2024443184 | Making The Demo Video, Main ideas, Assisting in adhoc |
| 4 | Nur Haziqqa     | 2024235138 | Making The Demo Video, Main ideas, Assisting in adhoc |

**Course / Event:** SIMEDARBY HACKATHON 2026


## License

This project is for the use of hackathon only.
