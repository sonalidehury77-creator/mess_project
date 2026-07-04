# Installation Guide

## Smart Hostel Mess Management System

This guide explains how to install and run the project on your local machine using XAMPP.

---

# System Requirements

- Windows 10/11
- XAMPP 8.x or above
- PHP 8.x
- MySQL
- Modern Web Browser (Chrome, Edge, Firefox)

---

# Step 1 : Install XAMPP

Download and install XAMPP from:

https://www.apachefriends.org/

After installation, open the XAMPP Control Panel.

Start the following services:

- Apache
- MySQL

---

# Step 2 : Copy the Project

Copy the project folder

```
MESS_PROJECT
```

into

```
C:\xampp\htdocs\
```

---

# Step 3 : Create Database

Open your browser.

Go to

```
http://localhost/phpmyadmin
```

Create a database named

```
mess_system
```

---

# Step 4 : Import Database

Open the newly created database.

Click

Import

Select

```
database/mess_system.sql
```

Click

Go

Wait until the import finishes successfully.

---

# Step 5 : Configure Database Connection

Open

```
config/db_connect.php
```

Verify the following settings:

```php
define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','mess_system');
```

---

# Step 6 : Run the Project

Open your browser.

Visit

```
http://localhost/MESS_PROJECT/
```

---

# Student Portal

Open

```
http://localhost/MESS_PROJECT/auth/login.php
```

---

# Admin Portal

Open

```
http://localhost/MESS_PROJECT/admin/login.php
```

---

# Features

- Student Registration
- Student Login
- Admin Login
- Dashboard
- Meal Selection
- QR Code
- Leave Management
- Food Feedback
- Monthly Bill
- Reports
- Analytics
- Announcements

---

# Troubleshooting

## Database Connection Error

Verify

- Apache is running
- MySQL is running
- Database name is correct
- Username is root
- Password is correct

---

## Images Not Showing

Verify

```
uploads/
```

folder exists.

---

## Login Not Working

Verify

- Database imported correctly
- Student/Admin records exist
- Passwords are correctly stored

---

## White Screen

Enable PHP error reporting for debugging.

---

# Project Successfully Installed

If all steps are completed successfully, the Smart Hostel Mess Management System is ready to use.