# Eventra - Campus Event Management System

Eventra is a web-based application designed to help manage and discover campus events easily. It allows students, non-students, and event organizers to interact seamlessly.

## Project Structure

- `FRONTEND/`: Contains all HTML, CSS, and JS files for the user interface.
- `BACKEND/`: Contains PHP scripts that handle database interactions, authentication, and API endpoints.

## Prerequisites

- **XAMPP** (or any similar local server like MAMP/WAMP) to run Apache and MySQL locally.
- **PHP 8.x** or higher.
- **MySQL** database.

## Local Setup Instructions

1. **Clone the Repository**
   Move the cloned repository into your local server's root directory. For XAMPP, this is usually `C:\xampp\htdocs\`.
   **Important:** Ensure the folder is named exactly `UASSoftdev`. So the path should be `C:\xampp\htdocs\UASSoftdev`.

2. **Start Services**
   Open your XAMPP Control Panel and start **Apache** and **MySQL**.

3. **Database Configuration**
   - Go to `http://localhost/phpmyadmin`.
   - Create a new database named `eventra` (or whatever matches your local config).
   - Import the `BACKEND/eventra.sql` file into this new database.
   - Open `BACKEND/koneksi.php` and update the connection details to match your local database:
     ```php
     $host = "localhost";
     $user = "root";     
     $pass = "";         
     $db   = "eventra";
     ```
     *(Remember not to commit your local database credentials if you plan to push changes!)*

4. **Run the Application**
   Open your web browser and navigate to:
   `http://localhost/UASSoftdev/FRONTEND/index.html`

## Best Practices
- **Formatting:** We use Prettier for code formatting. A `.prettierrc` configuration file is included in the root directory.
