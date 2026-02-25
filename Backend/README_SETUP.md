# Z-Volta Project Setup

This project has been converted to a **Node.js + Vue 3 + Tailwind CSS** stack.

## Prerequisites
1.  **XAMPP** running (Apache and MySQL).
2.  **Node.js** installed.

## Database Setup
Ensure the database is imported. If you haven't done so:
```bash
/opt/lampp/bin/mysql -u root < database.sql
```
(Or import `database.sql` via phpMyAdmin).

## Installation
Run the helper script:
```bash
bash setup.sh
```
Or manually:
1.  `cd backend && npm install`
2.  `cd frontend && npm install`

## Running the Application

### 1. Start Backend API
```bash
cd backend
node server.js
```
*Server runs on http://localhost:3000*

### 2. Start Frontend
```bash
cd frontend
npm run dev
```
*App runs on http://localhost:5173*

## Login Credentials (from database.sql)
*   **Admin:** `admin` / `Admin123!`
*   **Coordinator:** `coord1` / `Coord123!`
*   **Employee:** `dip1` / `User123!`
