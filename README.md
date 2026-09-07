# Bean Rooted Cafe Ordering and Management System

## System Overview

Bean Rooted Cafe is a web-based food ordering and cafe management system. It
allows customers to browse the cafe menu, add products to a cart, place orders,
choose a payment method, and track delivery progress. Staff and administrators
can manage products, categories, promotions, orders, users, and sales from the
protected management area.

## Key Features

- Customer menu browsing by category
- Shopping cart and checkout
- Email verification during checkout
- Delivery address and location support
- Cash-on-delivery and e-wallet payment options
- Promotion and discount management
- Order creation and status tracking
- Delivery tracking and proof-of-delivery support
- Product and category management
- User authentication and role-based access
- Sales dashboard and reports
- Responsive customer and admin interfaces

## User Roles

- Administrator
- Staff
- Customers
- Other authorized users

## Technologies Used

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Bootstrap
- Leaflet
- Chart.js

## Database

The system uses MySQL as its database management system. The database schema
and initial data are provided in `database.sql`. The main tables store users,
categories, products, orders, order items, sales, settings, and promotions.

## Installation

1. Install XAMPP.
2. Copy the project folder into the XAMPP `htdocs` directory.
3. Start Apache and MySQL from the XAMPP Control Panel.
4. Create a MySQL database named `foodpanda_clone` in phpMyAdmin, or import
	`database.sql`, which creates the database automatically.
5. Configure the database credentials in `config/database.php` if they differ
	from the local XAMPP defaults.
6. Open `http://localhost/food-panda-clone/` in a browser.
7. Use `login.php` to access the staff and administrator management area.

The included `install.php` can also be used to assist with the initial setup.

## Main Areas

- `index.html` — customer menu and ordering interface
- `tracking.html` — order tracking interface
- `login.php` — staff and administrator login
- `admin_panel/` — protected management dashboard and modules
- `api/` — customer-facing product, category, and promotion endpoints
- `controllers/` — order-processing logic
- `config/` — database and authentication configuration
- `database.sql` — database schema and starter records

## Purpose

The system aims to simplify cafe ordering and daily operations by bringing
menu management, customer orders, delivery tracking, promotions, and sales
monitoring into one organized platform.
