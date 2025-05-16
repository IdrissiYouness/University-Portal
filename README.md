# Bachelor's Graduate Registration System

A simple PHP-based web application for bachelor's graduates to register for Computer Science fields. This system includes user and admin panels, secure registration and login, file uploads, data export capabilities, and email notifications.

## Features

- Student registration and login
- Admin panel with statistics
- PDF and CSV export of registered users
- Email notifications using PHPMailer
- Input validation and database interaction using PDO
- File uploads support
- Modular code structure

## Technologies Used

- PHP ( v8+ )
- Vanilla HTML / CSS / JavaScript ( + AJAX , Chart.js )
- MySQL (via PDO)
- Third party libs
  - PHPMailer
  - DomPDF

## Project Structure

```
registration-system/
│
├── admin/ # Admin dashboard pages
│ ├── index.php
│ ├── logout.php
│ ├── sidebar.php
│ └── stats.php
│
├── assets/ # Static assets
│ ├── css/
│ ├── fonts/
│ ├── images/
│ └── js/
│
├── config/
│ └── db.php # Database connection setup
│
├── includes/ # Reusable PHP modules
│ ├── db_helpers.php
│ ├── exportcsv.php
│ ├── exportpdf.php
│ ├── send_email.php
│ ├── update_status.php
│ ├── utils.php
│ └── validation.php
│
├── student/ # Student user pages
│ ├── index.php
│ └── logout.php
│
├── uploads/ # Directory for storing uploaded files
├── .gitignore # Git ignore rules
├── index.php # Home page
├── login.php # Login form
├── register.php # Registration form
├── process-register.php # Handles registration logic
├── test_mail.php # Email testing script
└── README.md # Project documentation
```

## Setup Instructions

1. Clone this repository.
2. Set up your local server (e.g., XAMPP, WAMP).
3. Import the database schema (SQL file will be added soon).
4. Configure your DB and SMTP settings( If needed ).
5. Start the server and visit the index page.
