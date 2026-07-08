# 🧠 DigiInventory System

DigiInventory System is an internal digital platform designed to streamline inventory tracking, asset distribution, employee borrowing workflows, and incident reporting.
---

## 🎯 Project Objective

This platform aims to manage, allocate, track, and monitor company assets and inventory items across different user levels. It empowers employees to request items, log incidents, print asset labels via QR codes, and manage approvals seamlessly, optimizing internal logistics and provisioning.

---

## 🧩 Key Features

- **User & Role Management**
  - Role-based access control (RBAC): Admin, Staff, Manager, and Karyawan (Employee)
  - Custom role middleware and session-based profile management

- **Inventory & Asset Catalog**
  - Categorization of inventory items
  - Product unit tracking (individual items with unique serial numbers, condition status, and availability)
  - Direct QR Code generation for asset units (`simple-qrcode`) for quick scanning

- **Asset Borrowing & Return Workflow**
  - Employees (Karyawan) can request asset borrowing with calendar/availability validation
  - SLA validations for borrowing approvals (excluding national holidays)
  - Manager approval for specific high-value items/requests
  - Staff handover confirmation & letter generation (export to PDF)
  - Searchable return logs and check-in system

- **Procurement Requests**
  - Manager can request procurement of new products
  - Admin/Staff can verify, approve, or reject procurement requests

- **Incident & Damage Reporting**
  - Employees can report incidents (damage or loss of borrowed units)
  - Admin & Staff verification process
  - Finalization of incidents by Managers or Admins

- **Periodic Reports & Exporting**
  - Auto-generated periodic activity reports
  - Export data to PDF (`barryvdh/laravel-dompdf`) or Excel (`maatwebsite/excel`)

- **SLA & Calendar Integrations**
  - Public holidays synchronization and management to ensure SLA and duration calculations exclude non-working days

- **Notifications & Audit Logging**
  - In-app alerts for borrowing status updates, returns, and incident reporting
  - Admin log monitoring and API Token management

---

## 🔐 Security & Performance

- Role-based middleware for security access control
- API tokens generated securely for administrative integrations
- Unique QR-based endpoints for quick unit scans without authentication
- Integrated CSRF protection and Laravel-standard security practices

---

## 👥 Stakeholders

- **Primary**: Admin (IT/Asset Managers), Staff (Operations), Manager, Karyawan (Employees requesting assets)
- **Secondary**: Procurement Team, Management (Viewers of Reports)

---

## 🛠️ Technology Stack

- **Frontend**: Blade Templates + Tailwind CSS + Alpine.js (via Vite)
- **Backend**: Laravel 13 (PHP ^8.3)
- **Database**: MySQL
- **Dependencies**: 
  - `barryvdh/laravel-dompdf` for PDF generation
  - `maatwebsite/excel` for Excel exports
  - `simplesoftwareio/simple-qrcode` for QR code generation
  - `laravel/sanctum` for API Token authorization

---

## 📦 Organization Repositories

| Repository | Description |
|------------|-------------|
| `digi-inventory` | Main repository containing Laravel backend code, views, migrations, and assets |

---

## 🧪 Seeded Test Accounts

| Role | Email | Password |
|---|---|---|
| Admin | `admin@digi.test` | `password` |
| Staff | `staff@digi.test` | `password` |
| Manager | `manager@digi.test` | `password` |
| Karyawan | `karyawan@digi.test` | `password` |

---

## 📅 Timeline Overview

- **July 2026**: System development begins
- **August 2026**: Internal testing & deployment
- **September 2026**: Public launch for internal divisions

---

## 📖 References

- Laravel 13.x Documentation
- Tailwind CSS Styling Guidelines
- ISO/IEC 27001 Information Security Management (Best Practices)

---

## 🎓 Corporate Support

This project is proudly supported by the company through its commitment to building digital solutions and fostering innovation in internal enterprise transformation.
