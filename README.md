# Modern PHP Application Boilerplate

This project serves as a robust, modern PHP application boilerplate, meticulously crafted without a full-stack framework. It leverages essential standalone libraries and follows best practices for dependency management, routing, templating, database interaction, dependency injection, error handling, and middleware.

---

## Features

-   **Composer-driven:** Modern dependency management and PSR-4 autoloading.
-   **Clean Architecture:** Clear separation of concerns (Controllers, Services, Repositories, Models).
-   **PSR-7/15 HTTP Messages & Middleware:** For robust request/response handling and pluggable cross-cutting concerns (global and route-specific).
-   **FastRoute:** High-performance routing for mapping URLs to application logic.
-   **Twig:** Fast, secure, and flexible templating engine for clean presentation layers.
-   **PDO with Repository Pattern:** Secure and organized database interaction with MySQL, preventing SQL injection.
-   **PHP-DI:** Powerful and flexible Dependency Injection Container for managing service dependencies and promoting testability.
-   **Monolog:** Comprehensive logging for debugging and monitoring.
-   **Custom Error Handling:** Graceful error and exception handling with detailed debug output in development.
-   **Environment Variables (.env):** Secure management of sensitive configurations.

## Getting Started

Follow these steps to get the project up and running on your local machine.

### Prerequisites

-   PHP 8.1+
-   Composer
-   MySQL 5.7+ (or compatible database)

### Installation

1. **Clone the repository:**

```bash
git clone https://github.com/your-username/your-project-name.git
cd your-project-name
```

2. **Install Composer dependencies:**

```bash
composer install
```

3. **Configure Environment Variables:**

-   Create a `.env` file in the project root by copying the example:

```bash
cp .env.example .env
```

-   Edit the `.env` file and fill in your database credentials and other necessary settings:

```ini
APP_ENV=development
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=your_mysql_user
DB_PASS=your_mysql_password
```

4. **Database Setup:**

-   Create a MySQL database (e.g., `your_database_name`)`.
-   Execute the following SQL to create a sample `users` table and insert some data:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (name, email) VALUES ('Alice Smith', 'alice@example.com');
INSERT INTO users (name, email) VALUES ('Bob Johnson', 'bob@example.com');
```

5. **Set Directory Permissions:**
   Ensure the `var/` directory and its subdirectories are writable by your web server:

```bash
chmod -R 777 var
```

_(Note: For production, more restrictive permissions are recommended.)_

### Running the Application

You can use PHP's built-in web server for local development:

```bash
php -S localhost:8000 -t public
```
