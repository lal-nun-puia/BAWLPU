README:
1. Project Overview
BAWLPu is a PHP + MySQL web application for connecting clients with nurses and health-workers.
This document explains how to run the project on your local machine using XAMPP.
2. Prerequisites
Before you start, install:
- XAMPP (Apache + PHP + MySQL/MariaDB)
- A web browser (Chrome, Firefox, Edge, etc.)
- (Optional) Git, if you want to clone from GitHub/GitLab
3. Getting the Source Code
1) Download or clone the project folder that contains the PHP files.
2) Rename the folder to bawlpu (or keep the same folder name you used inside the code).
3) Copy that folder to your XAMPP htdocs directory, for example:
   C:\xampp\htdocs\bawlpu
4. Start Apache and MySQL
1) Open the XAMPP Control Panel.
2) Start Apache.
3) Start MySQL. Make sure the MySQL status turns green.
5. Create the Database
1) Open your browser and go to:
   http://localhost/phpmyadmin
2) Click “Databases”.
3) Create a new database with this name:
   nurse_portal
4) Go to the “Import” tab.
5) Choose the SQL file from the project (for example: database/nurse_portal.sql).
6) Click “Go” to import all tables and sample data.
6. Configure Database Connection (if needed)
1) In the project folder open the configuration file (for example: config.php or
includes/db.php).
2) Check that these values match your local setup:
   $host = "localhost";
   $username = "root";
   $password = "";   // empty password by default in XAMPP
   $database = "nurse_portal";
3) Save the file.
7. Create or Promote an Admin User (CLI Script)
1) Open Command Prompt (CMD) or PowerShell.
2) Go to the project folder, for example:
   cd C:\xampp\htdocs\bawlpu
3) Run the admin script:
   php create_or_promote_admin.php
4) Follow the on-screen instructions to enter the email and password for the admin account.
5) Use those credentials to log in to the admin panel.
8. Run the Web Application
1) Make sure Apache and MySQL are still running in XAMPP.
2) Open your browser and visit:
   http://localhost/bawlpu/
3) You should see the BAWLPu home page.
4) Use the navigation menu to:
   - Register/Login as a client or nurse
   - Browse services and prices
   - Create bookings, etc.
5) To open the admin dashboard, either:
   - Click the “Admin Login” link (if available), or
   - Directly visit the admin login page (for example):
     http://localhost/bawlpu/admin_login.php
9. Common Problems and Quick Fixes
- If MySQL does not start in XAMPP:
  - Close other programs that may use port 3306.
  - Or change the MySQL port in XAMPP settings and update your config.php if needed.
- If you see “Connection failed” or “Unable to connect to database”:
  - Check that the database name is nurse_portal.
  - Check that MySQL is running.
  - Check that username/password in config.php are correct.
10. Stopping the Project
- Close the browser tabs.
- Stop Apache and MySQL from the XAMPP Control Panel.
- Your project files will remain in C:\xampp\htdocs\bawlpu and can be used again later.
End of document.
