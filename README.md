# CBT Pro - Computer Based Testing System

A complete, production-ready Computer Based Testing (CBT) application built with native PHP, MySQL, Bootstrap 5, and vanilla JavaScript.

## Features

### Admin Panel
- **Dashboard** - Statistics, charts, recent activity
- **Student Management** - Add/edit/delete students, bulk CSV upload
- **Exam Management** - Create, publish, start, end, archive exams
- **Question Bank** - Add individual or bulk upload questions with images
- **Student Assignment** - Filter and assign students to exams with batch scheduling
- **Live Monitoring** - Real-time student tracking with time extension and force submit controls
- **Biometric Enrollment** - Web-based fingerprint capture with multiple SDK support
- **Results & Analytics** - Grade distribution, pass/fail statistics, CSV export
- **Settings** - System configuration, grading scale, biometric settings
- **Offline Nodes** - Control offline exam halls from the online hub (sync, restrictions, signed updates)

### Student Portal
- **Login** - Matric number + password authentication
- **Dashboard** - View assigned exams with status indicators
- **Biometric Enrollment** - Self-service fingerprint registration
- **Exam Interface** - Fullscreen mode, countdown timer, question navigation
- **Anti-Cheating** - Right-click block, tab switch detection, copy/paste prevention
- **Auto-Save** - Answers saved every 30 seconds via AJAX
- **Results** - View published results with grade cards
- **Background sync** - Offline halls push/pull exam data automatically when the internet is available

### Security
- PDO prepared statements (SQL injection prevention)
- CSRF token protection on all forms
- Password hashing with BCRYPT
- Session timeout and IP binding
- Rate limiting on login attempts
- XSS output escaping
- File upload validation

## Tech Stack
- **Backend:** PHP 8.x (native OOP, no frameworks)
- **Database:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3, Bootstrap 5, Bootstrap Icons
- **AJAX:** Pure JavaScript fetch API
- **Charts:** Chart.js
- **Biometric:** WebAuthn API + Simulated mode for development

## Quick Start

### Prerequisites
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx web server
- mod_rewrite enabled (for .htaccess)

### Installation
1. Clone or extract to your web root directory
2. Make `config/`, `storage/`, `logs/` and `assets/uploads/` writable
3. Open `http://localhost/cbt-system/` and complete the installer (online server or offline exam hall)
4. For a second offline hall, add it from Admin > Offline Nodes and paste the pairing code into that hall's installer
5. Details: `docs/INSTALLATION.md` and `docs/SYNC.md`

### First login
Use the admin username and password you entered in the installer.
Students use a matric number (password = matric number on first login).

### File Structure
```
cbt-system/
├── index.php                 # Entry point
├── install/                  # Browser installer (online hub or offline hall)
├── config/                   # Configuration files
│   ├── database.php          # PDO connection
│   ├── config.php            # Global settings
│   └── functions.php         # Helper functions
├── admin/                    # Admin panel
│   ├── index.php             # Admin login
│   ├── dashboard.php         # Dashboard
│   ├── students.php          # Student management
│   ├── bulk_upload.php       # CSV import
│   ├── exams.php             # Exam management
│   ├── questions.php         # Question bank
│   ├── assign_exam.php       # Student assignment
│   ├── monitoring.php        # Live monitoring
│   ├── results.php           # Results & analytics
│   ├── biometric_enroll.php  # Biometric enrollment
│   ├── settings.php          # System settings
│   └── logout.php            # Logout
├── student/                  # Student portal
│   ├── login.php             # Student login
│   ├── dashboard.php         # Student dashboard
│   ├── take_exam.php         # Exam interface
│   ├── results.php           # View results
│   ├── biometric_enroll.php  # Self enrollment
│   └── logout.php            # Logout
├── includes/                 # Shared components
│   ├── auth.php              # Authentication check
│   ├── header.php            # Page header
│   ├── footer.php            # Page footer
│   └── sidebar.php           # Admin sidebar
├── ajax/                     # AJAX endpoints
│   ├── admin/                # Admin AJAX APIs
│   └── student/              # Student AJAX APIs
├── assets/                   # Static files
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript files
│   ├── uploads/              # Uploaded files
│   └── libs/                 # Third-party libraries
├── sql/                      # Database schema
├── logs/                     # Error logs
└── .htaccess                 # Security rules
```

## Documentation
- See `docs/INSTALLATION.md` for detailed setup instructions
- See `docs/SYNC.md` for online hub + offline exam-hall synchronisation
- See `docs/BIOMETRIC_INTEGRATION.md` for fingerprint SDK integration
- See `docs/API_DOCUMENTATION.md` for AJAX endpoint reference

## License
Commercial Software - All rights reserved.
