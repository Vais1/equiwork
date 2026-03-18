# EquiWork - Inclusive Employment Platform

WEB2202 Final Assessment Project

## Student Information
- Subject: WEB2202 Web Programming
- Project Name: EquiWork
- Student Name: Muhammad Awais Ghaffar
- Student ID: 22116024

## Purpose
EquiWork is a web platform that connects job seekers (especially users needing accessibility accommodations) with employers offering inclusive work opportunities. The project is aligned with SDG 08: Decent Work and Economic Growth.

## Core Features
- Role-based authentication for Seeker, Employer, and Admin users.
- Unified login routing by role:
  - Admin to admin dashboard.
  - Employer to employer dashboard.
  - Seeker to job board.
- Session security with timeout checks and role guards.
- Admin management panel for users, jobs, and accommodation categories.
- Employer dashboard to post jobs, manage job status, and review applicants.
- Accommodation-based job filtering engine.
- Resume parsing pipeline (local PDF and DOCX support, optional external OCR fallback).
- Job application workflow with parsed resume data and cover letter.
- Profile update flow for authenticated users.
- Flash messaging system for user feedback.
- CSRF protection for form submissions and protected POST endpoints.
- Responsive UI built with locally compiled Tailwind CSS.

## Technology Stack
- Backend: PHP 8.x
- Database: MariaDB / MySQL (via XAMPP)
- Frontend: HTML, Tailwind CSS (local build), Vanilla JavaScript
- Security: Password hashing, prepared statements, session timeout, CSRF tokens
- Local Tooling: Node.js + Tailwind CLI for CSS build

## Project File Tree
```text
c:/xampp/htdocs/equiwork
|-- .gitignore
|-- README.md
|-- admin_dashboard.php
|-- index.php
|-- login.php
|-- register.php
|-- jobs.php
|-- apply_job.php
|-- post_job.php
|-- profile_update.php
|-- employer_dashboard.php
|-- project-overview.md
|-- package.json
|-- package-lock.json
|-- tailwind.config.js
|-- actions/
|   |-- logout.php
|   |-- parse_resume.php
|   |-- process_application.php
|   |-- process_login.php
|   |-- process_post_job.php
|   |-- process_profile.php
|   |-- process_register.php
|-- admin/
|   |-- login.php
|   |-- dashboard.php
|   |-- add_job.php
|-- assets/
|   |-- css/
|   |   |-- tailwind-input.css
|   |   |-- tailwind.css
|   |-- js/
|   |   |-- custom-controls.js
|   |   |-- form-validation.js
|   |   |-- theme.js
|   |-- img/
|-- database/
|   |-- schema.sql
|   |-- admin_demo.sql
|-- includes/
|   |-- auth_check.php
|   |-- config.php
|   |-- csrf.php
|   |-- db.php
|   |-- flash.php
|   |-- header.php
|   |-- header2.php
|   |-- footer.php
```

## Local Setup
- Place the project in XAMPP htdocs:
  - c:/xampp/htdocs/equiwork
- Start Apache and MySQL from XAMPP Control Panel.
- Import database schema from:
  - database/schema.sql
- Build Tailwind CSS locally:
  - npm install
  - npm run build:css
- Open in browser:
  - http://localhost/equiwork/

## Security and Quality Notes
- Passwords are stored with secure hashing and verified at login.
- SQL operations use prepared statements.
- CSRF tokens are validated on protected requests.
- Role-based authorization is enforced on protected pages.
- UI is responsive and accessibility-oriented, with semantic forms and ARIA support where needed.

## Assessment Scope Summary
This project demonstrates end-to-end implementation of:
- Authentication and role routing.
- Admin and employer operational workflows.
- Accessible job matching and application lifecycle.
- Secure web programming practices expected for WEB2202.
