# EquiWork — Project Blueprint & Development Guide
> **WEB2202 Final Assessment** | SDG 08: Decent Work & Economic Growth

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Technology Stack](#2-technology-stack)
3. [System Modules & Grading Rubric](#3-system-modules--grading-rubric)
4. [Database Architecture](#4-database-architecture)
5. [Security Standards](#5-security-standards)
6. [Frontend & Accessibility Standards](#6-frontend--accessibility-standards)
7. [Development Workflow](#7-development-workflow)
8. [Project Scope Boundaries](#8-project-scope-boundaries)

---

## 1. Executive Summary

**EquiWork** is a purpose-built web platform that bridges the gap between individuals with physical disabilities and quality remote employment. The platform prioritises dignity, accessibility, and economic inclusion — connecting job seekers with employers who genuinely support adaptive work environments.

### Mission Alignment

| SDG Goal | Target |
|----------|--------|
| **SDG 08** — Decent Work & Economic Growth | Full and productive employment for all, including persons with disabilities |

### What EquiWork Does
- Matches disability-accessible job seekers with verified remote-friendly employers
- Provides a transparent, filter-driven job board based on real accessibility features
- Manages the full application lifecycle from discovery to confirmation

### What EquiWork Explicitly Does Not Do
- Medical background checks or health data processing
- Legal mediation between parties
- Payroll or financial transaction processing

---

## 2. Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Local Server** | XAMPP (Apache + MariaDB) | HTTP serving & relational data storage |
| **Backend** | PHP 8.x | Server-side routing, DB transactions, session management |
| **Database UI** | phpMyAdmin | Schema creation, relational mapping, query testing |
| **Styling** | Tailwind CSS (CDN or local build) | Utility-first, responsive, accessible UI |
| **Interactivity** | Vanilla JavaScript | DOM manipulation, async fetch, client-side validation |

> **No framework changes permitted.** The stack above is fixed for this assessment.

---

## 3. System Modules & Grading Rubric

### Module A — Dual-Role Authentication & Security
**Weight: 20 Marks**

This module governs how users enter the system and what they can access. All authentication logic must be airtight before any other module is built.

**Registration System**
- Capture full user profile on sign-up: name, email, password, role selection
- Roles: `Seeker`, `Employer`, `Admin`
- Store passwords exclusively using `password_hash()` — never plain text

**Login & Role Routing**
- Single unified login form for all roles
- On success, resolve the user's `role_type` from the database and redirect:
  - `Admin` → `admin_dashboard.php`
  - `Employer` → `employer_dashboard.php`
  - `Seeker` → `jobs.php`

**Security Requirements**
- All passwords hashed with `password_hash()` / verified with `password_verify()`
- Session timeout: track `$_SESSION['last_action']` timestamp; auto-logout after inactivity threshold (e.g. 15–30 minutes)
- `session_start()` must appear at the top of every protected page

---

### Module B — Administrative Dashboard
**Weight: 10 Marks**

A protected backend panel exclusively for Admin users. Access must be gated by a session-based role check on every page load.

**Required CRUD Operations**

| Operation | Target | Method |
|-----------|--------|--------|
| **Read** | All users, all job postings | `SELECT` with pagination |
| **Update** | User account details, job listings, accommodation categories | `UPDATE` via parameterized query |
| **Delete** | User accounts, job postings | `DELETE` with confirmation prompt |

> The admin panel must not be accessible by URL-guessing. Always verify `$_SESSION['role'] === 'Admin'` server-side.

---

### Module C — Forms & Robust Validation
**Weight: 20 Marks**

Every data entry point in the system requires a two-tier validation approach. Neither tier replaces the other — both must exist.

**Two-Tier Validation Model**

```
User submits form
       │
       ▼
[Tier 1: Client-Side JavaScript / HTML5]
  • Real-time inline feedback
  • Field format checks (email, password length)
  • Prevents unnecessary server round-trips
       │
       ▼
[Tier 2: Server-Side PHP] ← Ultimate source of truth
  • htmlspecialchars() strips XSS vectors
  • filter_var() validates email format
  • Prepared statements prevent SQL injection
  • Re-validates all fields regardless of client result
```

**Forms Requiring Validation**
1. Registration form
2. Login form
3. Job posting form (Employer)
4. Profile update form (Seeker & Employer)
5. Job application submission form

---

### Module D — Accommodation Matching Engine
**Core Differentiator**

The heart of EquiWork. A dynamic job board where seekers can filter opportunities by the accessibility features that matter to them.

**Filter Categories (examples)**

| Filter Type | Example Values |
|-------------|---------------|
| Work Arrangement | 100% Remote, Hybrid, Flexible Hours |
| Assistive Technology | Screen Reader Compatible, Voice Control Ready |
| Communication | Async-first, No Video Required |
| Physical | No Keyboard Required, Custom Equipment Provided |

**How the Query Works**

Filters map to the `job_accommodations` intersection table. When a user selects filters, a dynamic parameterized `SELECT` query joins `jobs`, `job_accommodations`, and `accommodations` — returning only roles that match *all* selected criteria.

```sql
-- Simplified concept
SELECT j.* FROM jobs j
INNER JOIN job_accommodations ja ON j.job_id = ja.job_id
INNER JOIN accommodations a ON ja.accommodation_id = a.accommodation_id
WHERE a.name IN (?, ?, ?)   -- parameterized, safe
GROUP BY j.job_id
HAVING COUNT(DISTINCT a.accommodation_id) = ?;  -- must match ALL filters
```

---

### Module E — Additional Features
**Weight: 10 Marks**

**Automated Email Notifications**

Triggered on every successful job application submission:

| Recipient | Email Content |
|-----------|--------------|
| **Job Seeker** | "Application Received" — confirms submission, lists job title & company |
| **Employer** | "New Application Alert" — notifies of new candidate, links to dashboard |

Implementation: PHP's native `mail()` function or PHPMailer for SMTP reliability.

---

## 4. Database Architecture

> All tables must be in **Third Normal Form (3NF)** to prevent data redundancy and maintain referential integrity.

### Entity Relationship Overview

```
users ──────────────────────────────────────────┐
  │ (employer_id FK)                             │
  ▼                                             │ (seeker_id FK)
jobs ◄─────────── job_accommodations            ▼
                        │              applications
                        │ (accommodation_id FK)
                        ▼
                  accommodations
```

### Table Definitions

#### `users`
| Column | Type | Constraints |
|--------|------|-------------|
| `user_id` | INT | PRIMARY KEY, AUTO_INCREMENT |
| `username` | VARCHAR(100) | NOT NULL, UNIQUE |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE |
| `password_hash` | VARCHAR(255) | NOT NULL |
| `role_type` | ENUM('Admin','Employer','Seeker') | NOT NULL |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

#### `jobs`
| Column | Type | Constraints |
|--------|------|-------------|
| `job_id` | INT | PRIMARY KEY, AUTO_INCREMENT |
| `employer_id` | INT | FOREIGN KEY → `users.user_id` |
| `title` | VARCHAR(255) | NOT NULL |
| `description` | TEXT | NOT NULL |
| `location_type` | ENUM('Remote','Hybrid','On-site') | NOT NULL |
| `status` | ENUM('Active','Closed') | DEFAULT 'Active' |
| `posted_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

#### `accommodations`
| Column | Type | Constraints |
|--------|------|-------------|
| `accommodation_id` | INT | PRIMARY KEY, AUTO_INCREMENT |
| `name` | VARCHAR(150) | NOT NULL, UNIQUE |
| `category` | VARCHAR(100) | NOT NULL |

#### `job_accommodations` *(Intersection Table)*
| Column | Type | Constraints |
|--------|------|-------------|
| `job_id` | INT | FOREIGN KEY → `jobs.job_id` |
| `accommodation_id` | INT | FOREIGN KEY → `accommodations.accommodation_id` |
| — | — | PRIMARY KEY (`job_id`, `accommodation_id`) |

#### `applications`
| Column | Type | Constraints |
|--------|------|-------------|
| `application_id` | INT | PRIMARY KEY, AUTO_INCREMENT |
| `job_id` | INT | FOREIGN KEY → `jobs.job_id` |
| `seeker_id` | INT | FOREIGN KEY → `users.user_id` |
| `status` | ENUM('Pending','Reviewed','Accepted','Rejected') | DEFAULT 'Pending' |
| `submitted_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

---

## 5. Security Standards

### Never Trust User Input

Every value arriving from a form or URL must be treated as hostile until sanitised.

```php
// XSS prevention — output escaping
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // reject
}

// Password verification
if (password_verify($input_password, $stored_hash)) {
    // authenticated
}
```

### Prepared Statements — No Exceptions

Raw SQL string concatenation is **never acceptable**, even for internal queries.

```php
// ✅ CORRECT — parameterized query
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

// ❌ WRONG — SQL injection vector
$result = $conn->query("SELECT * FROM users WHERE email = '$email'");
```

### File Structure Rule

PHP logic **must** appear before any HTML output. Mixing output before `session_start()` or `header()` calls causes fatal "Headers already sent" errors.

```php
<?php
// ✅ CORRECT order
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
...
```

### Session Timeout Implementation

```php
$timeout = 1800; // 30 minutes

if (isset($_SESSION['last_action']) && (time() - $_SESSION['last_action']) > $timeout) {
    session_unset();
    session_destroy();
    header('Location: login.php?reason=timeout');
    exit;
}

$_SESSION['last_action'] = time();
```

---

## 6. Frontend & Accessibility Standards

### WCAG Compliance Requirements

Because EquiWork serves users with disabilities, accessibility is not optional — it is the product.

| Requirement | Implementation |
|-------------|---------------|
| Semantic structure | Use `<fieldset>`, `<legend>`, `<label for="">` on all forms |
| Screen reader support | Add `aria-label`, `aria-describedby`, `role` attributes where HTML semantics are insufficient |
| Colour contrast | Minimum 4.5:1 ratio — use `text-gray-900` on `bg-white` as the baseline |
| Keyboard navigation | All interactive elements reachable and operable via keyboard only |
| Focus indicators | Never remove `outline` — use `focus:ring-4 focus:ring-blue-300` via Tailwind |

### Responsive Layout Rules

```html
<!-- Mobile-first approach — never code desktop-first -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
  <!-- Job cards -->
</div>
```

> The rubric deducts marks for layout breakage on small viewports. Test at 375px, 768px, and 1280px widths.

### Button & CTA Styling Consistency

All primary actions must follow a consistent, high-visibility pattern:

```html
<!-- Primary CTA -->
<button class="bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 
               text-white font-semibold px-6 py-3 rounded-lg transition-colors">
  Apply Now
</button>

<!-- Destructive action (Admin delete) -->
<button class="bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 
               text-white font-semibold px-6 py-3 rounded-lg transition-colors">
  Delete Account
</button>
```

---

## 7. Development Workflow

Follow these phases **in strict order**. Each phase must be functional before the next begins.

### Phase 1 — Database Foundation
**Goal:** Stable, seeded database with verified relationships

- [ ] Create `equiwork_db` in phpMyAdmin
- [ ] Build all five tables with correct Primary/Foreign Keys
- [ ] Insert meaningful dummy data (≥5 users across all roles, ≥10 jobs, ≥8 accommodation types)
- [ ] Manually test JOIN queries before writing any PHP

---

### Phase 2 — Authentication Core
**Goal:** Flawless login/logout cycle with correct role routing

- [ ] Build `register.php` with hashed password storage
- [ ] Build `login.php` with role-based redirect logic
- [ ] Build `logout.php` with full session destruction
- [ ] Implement session timeout check in a shared `auth_check.php` include
- [ ] **Do not proceed until login/logout is completely stable**

---

### Phase 3 — Admin Panel (CRUD)
**Goal:** Protected admin dashboard with full data control

- [ ] Build `admin_dashboard.php` with role guard
- [ ] Implement paginated user list (`SELECT` with `LIMIT`/`OFFSET`)
- [ ] Build edit form for user accounts (`UPDATE`)
- [ ] Build delete functionality with confirmation step (`DELETE`)
- [ ] Repeat for job postings management

---

### Phase 4 — Matching Engine & Job Board UI
**Goal:** Filterable job board with live accommodation matching

- [ ] Build `jobs.php` with Tailwind-styled job cards
- [ ] Implement filter sidebar (checkboxes mapped to `accommodations` table)
- [ ] Write dynamic parameterized SQL using `job_accommodations` intersection
- [ ] Test filter combinations (single filter, multiple filters, no filter)
- [ ] Ensure mobile responsiveness of the board layout

---

### Phase 5 — Validation, Email & Refinement
**Goal:** Production-grade form handling and notification system

- [ ] Add JavaScript real-time validation to all forms (regex patterns, length checks)
- [ ] Audit all PHP forms for `htmlspecialchars()`, `filter_var()`, prepared statements
- [ ] Implement `mail()` / PHPMailer notification on application submit
- [ ] Test email sending for both seeker and employer recipients
- [ ] Final cross-browser and mobile viewport testing

---

## 8. Project Scope Boundaries

| In Scope ✅ | Out of Scope ❌ |
|-------------|----------------|
| User registration & authentication | Medical or disability verification |
| Job posting & management | Legal dispute mediation |
| Accommodation-based job matching | Payroll or payment processing |
| Application submission & tracking | Third-party HR system integration |
| Admin CRUD dashboard | Government benefits coordination |
| Automated email notifications | Real-time chat or messaging |

---

*EquiWork — Building pathways to economic participation, one accessible opportunity at a time.*