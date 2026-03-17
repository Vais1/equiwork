# EquiWork – Career Equality Platform

**WEB2202 Web Programming Final Assessment: Web Project**

*Student Name:* [INSERT STUDENT NAME]
*Student ID:* [INSERT STUDENT ID]

---

## Academic Mission
EquiWork is a dedicated web application designed to advance **SDG 08 (Decent Work and Economic Growth)** by connecting uniquely skilled professionals with adaptable, remote-first organizations. The platform ensures that candidates are matched based on the explicit accessibility accommodations they require, guaranteeing that the work environment adjusts to the individual, not the other way around.

## Technology Stack
Built strictly to modern web standards without heavy frameworks.
- **Backend**: Native PHP 8.x
- **Database**: MySQL (via XAMPP)
- **Styling**: Tailwind CSS (Utility-first framework via CDN)
- **Frontend Logic**: Vanilla JavaScript (ES6 Modules)

## Core Platform Features
EquiWork incorporates a wide array of advanced web programming patterns structured around user security, accessibility, and robust data management:
- **Dual-Role Authentication:** Dedicated sign-up and login workflows customized separately for "Job Seekers" and "Employers", backed by secure password hashing (`password_hash`).
- **Administrative CRUD Dashboard:** Secure backend access requiring 'Admin' privileges, featuring comprehensive create, read, update, and delete functionality for user management via a custom interactive modal UI.
- **Strict Web Security:** Deep integration of parameterized PDO statements, manual CSRF tokens across all form actions, and stringent XSS mitigation (`htmlspecialchars`) on output.
- **Accessible Resume Parsing:** Built-in form structures supporting multi-format document uploads (.pdf, .docx, .png) tailored to screen-reader compliant drop zones.
- **Robust Multi-Tier Validation:** Defensive, localized validation spanning native HTML5, immediate Client-Side JavaScript UI feedback, and ultimate strict Server-Side PHP data purification.
- **Fluid & Responsive UI:** Intelligent, fully responsive Tailwind layouts implementing a dark/light mode toggle via Vanilla JS, enhanced with fluid, hardware-accelerated micro-interactions across all system endpoints.

## Local Installation / Evaluation Instructions

Follow these precise steps to deploy the application completely locally using XAMPP for academic grading.

1. **Clone & Mount**
   Clone or download the `equiwork` repository and place the folder directly into your XAMPP server's operational root:
   ```bash
   C:\xampp\htdocs\equiwork\
   ```
2. **Launch Services**
   Open the XAMPP Control Panel and start the following required services:
   - **Apache** (Web Server)
   - **MySQL** (Database Server)

3. **Initialize the Database Schema**
   - Open your web browser and navigate directly to `http://localhost/phpmyadmin/`
   - Select the **SQL** tab.
   - Using your code editor, open the file `equiwork/database/schema.sql`.
   - Copy the entire SQL script, paste it into the the phpMyAdmin console, and press **Go**. This handles generating the database (`equiwork_db`), building related tables, and inserting default dummy data including a pre-made Admin account.
   
4. **Access the Application**
   - Navigate to the root folder via `http://localhost/equiwork/`
   - You can test standard user registration from the landing page.

5. **Test Administrative Access**
   To test the primary CRUD functionality, visit the hidden admin endpoint at `http://localhost/equiwork/admin/login.php` and login using default generated credentials:
   - **User:** `admin`
   - **Password:** `admin123`
