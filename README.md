# portfolio-website
Dynamic personal portfolio website with a PHP, MySQL and PDO-based admin CMS.

## 🏗️ System Architecture

The portfolio website uses PHP and MySQL with a dynamic Content Management System (CMS). The architecture separates the public-facing portfolio, admin panel, database operations, and file management.

```mermaid
flowchart TD
    U["Users / Visitors"]

    U --> P["Public Portfolio"]
    U --> A["Admin CMS"]

    subgraph FRONTEND["Presentation Layer"]
        P --> I["index.php"]
        A --> L["admin/login.php"]
        A --> D["admin/dashboard.php"]
        A --> M["Admin Management Pages"]
    end

    subgraph BACKEND["PHP Application Layer"]
        I --> C["Contact Form Processing"]
        D --> AUTH["Session Authentication"]
        M --> CRUD["CRUD Operations"]
        C --> DB
        CRUD --> DB
        AUTH --> DB
    end

    subgraph CONFIG["Database Connection"]
        DB["config/database.php"]
        DB --> PDO["PHP PDO Connection"]
    end

    subgraph STORAGE["Data and File Storage"]
        PDO --> MYSQL[("MySQL Database")]
        CRUD --> UP["includes/upload_helper.php"]
        UP --> FILES["uploads/images and documents"]
    end

    subgraph ASSETS["Frontend Assets"]
        I --> CSS["assets/css/style.css"]
        I --> JS["assets/js/script.js"]
        I --> IMG["assets/images"]
        D --> ADMINCSS["assets/css/admin.css"]
        M --> SIDEBAR["includes/admin_sidebar.php"]
    end
```

### Main Technologies

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Database Connectivity:** PDO
- **Authentication:** PHP Sessions and Password Hashing
- **Security:** CSRF Protection and Prepared Statements
- **Hosting:** InfinityFree

### Main Features

- Dynamic personal portfolio
- Secure administrator login
- Content Management System (CMS)
- Portfolio content management
- Project, skills, education and experience management
- Contact form with database storage
- Image and document uploads
- Responsive user interface
