# 🧠 DigiInventory System

**DigiInventory System** is an internal digital platform designed to streamline inventory tracking, asset distribution, employee borrowing workflows, and incident reporting.

---

## 🎯 Project Objective

This platform aims to manage, allocate, track, and monitor company assets and inventory items across different user levels. It empowers employees to request items, log incidents, print asset labels via QR codes, and manage approvals seamlessly, optimizing internal logistics and provisioning.

---

## 🧩 Key Features

- **User & Role Management**
  - Role-based access: Admin, Staff, Manager, Karyawan
  - Custom role middleware and session-based profile management

- **Inventory & Catalog Management**
  - Categorization of inventory items
  - Detailed unit physical tracking (serial numbers, conditions, availability)

- **Asset Borrowing Workflow**
  - Request submission with custom calendar availability check
  - Automatic SLA collision & FIFO queue checks

- **Handover & Return Control**
  - Staff handover scan and confirmation
  - Return verification with automated SLA late-days calculation (excluding weekends and national holidays)

- **Incident & Damage Logs**
  - Employee incident reporting (damage, loss)
  - Multi-level review, verification, and finalization workflow

- **Procurement Request Panel**
  - Manager procurement requests for new inventory stock
  - Admin/Staff validation and approval

- **Reporting & Asset Labels**
  - Periodic activity report builder (export to PDF/Excel)
  - Custom asset label choosing and QR code printing

---

## 🔐 Security & Performance

- Secure token-based API access via Laravel Sanctum
- Role-based middleware for routing access control
- Automated FIFO queue tracking preventing double-booking
- Scalable database schema supporting hundreds of concurrent borrowings
- High availability with automated SLA calculations

---

## 👥 Stakeholders

- **Primary**: Admin (IT/Asset Managers), Staff (Operations), Manager, Karyawan (Employees requesting assets)
- **Secondary**: Procurement Team, Management (Viewers of Reports)

---

## 🛠️ Technology Stack

- **Frontend**: Blade + Tailwind CSS + Alpine.js
- **Backend**: Laravel (REST API / Web Controller)
- **Database**: MySQL / PostgreSQL
- **Auth**: Laravel Breeze (Session) / Sanctum (Tokens)

---

## 📦 Organization Repositories

| Repository | Description |
|------------|-------------|
| `digi-inventory` | Main repository containing Laravel backend code, views, migrations, and assets |
| `files`  | Supporting document templates and asset databases |

---

## 🧪 Test Accounts & Roles

| Role | Email | Password |
|------|-------|----------|
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

## 🎓 Academic Support

This project is proudly supported by **Telkom University Surabaya**  
through its commitment to building strong industry partnerships and fostering innovation in digital transformation.

> As an academic institution, Telkom University Surabaya actively supports industry-driven projects and collaborative research by contributing expertise, resources, and community engagement initiatives.

This inventory platform reflects their shared vision of practical, impactful, and technology-based solutions for workforce development and internal enterprise transformation.
