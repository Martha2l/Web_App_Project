# Northwind Product Manager (CPE66 Web Application Project)

ระบบจัดการข้อมูลสินค้า (Northwind Product Management) พัฒนาด้วย **PHP + MySQL** บนสถาปัตยกรรม **REST API CRUD** พร้อมรองรับการนำขึ้นระบบออนไลน์ (Deployment) บน Cloud Platform **Railway (PaaS)**

---

## สารบัญ (Table of Contents)
1. [ภาพรวมและสถาปัตยกรรมระบบ (System Architecture)](#1-ภาพรวมและสถาปัตยกรรมระบบ-system-architecture)
2. [โครงสร้างโฟลเดอร์โปรเจกต์ (Project Structure)](#2-โครงสร้างโฟลเดอร์โปรเจกต์-project-structure)
3. [โครงสร้างฐานข้อมูล (Database Schema)](#3-โครงสร้างฐานข้อมูล-database-schema)
4. [คู่มือ REST API Endpoints (CRUD)](#4-คู่มือ-rest-api-endpoints-crud)
5. [การทดสอบรันบนเครื่องตนเอง (Localhost with MAMP / XAMPP)](#5-การทดสอบรันบนเครื่องตนเอง-localhost-with-mamp--xampp)
6. [ขั้นตอนการ Deploy ขึ้น Railway PaaS อย่างละเอียด (Step-by-Step)](#6-ขั้นตอนการ-deploy-ขึ้น-railway-paas-อย่างละเอียด-step-by-step)
7. [แนวทางการเตรียมเอกสารส่งงาน (Submission Guide)](#7-แนวทางการเตรียมเอกสารส่งงาน-submission-guide)

---

## 1. ภาพรวมและสถาปัตยกรรมระบบ (System Architecture)

ระบบถูกออกแบบตามหลักการ **Client-Server Architecture** และ **RESTful API**:
- **Client (Frontend)**: พัฒนาด้วย HTML5, Modern CSS, Bootstrap 5.3.3, และ Vanilla JavaScript (Fetch API + DOM Manipulation) พร้อมใช้ **SweetAlert2** สำหรับ Form Validation และแจ้งผลการทำรายการ
- **Server (Backend API)**: พัฒนาด้วย PHP 8.x PDO รองรับ HTTP Methods: `GET`, `POST`, `PUT`, `DELETE` ส่งและรับข้อมูลในรูปแบบ JSON
- **Database (Data Layer)**: MySQL ฐานข้อมูล `db_northwind` จากไฟล์ `dbNorthwind.sql` ในรายวิชา
- **Cloud PaaS**: โฮสต์ทั้ง Web Application และ MySQL บนแพลตฟอร์ม **Railway.com**

```
+-------------------------------------------------------+
|                Client (Web Browser)                  |
|    - Bootstrap 5 UI / Dashboard Metrics               |
|    - SweetAlert2 Validation & Confirmation Popups     |
|    - Vanilla JS Fetch API (Asynchronous AJAX)        |
+---------------------------+---------------------------+
                            |
                     HTTP JSON (REST)
                            |
+---------------------------v---------------------------+
|              Backend Engine (PHP 8.x)                |
|    - index.php (Front Controller / Single Page App)   |
|    - api/products.php (RESTful CRUD Endpoint)         |
|    - api/categories.php & api/suppliers.php           |
|    - config/database.php (Multi-Env PDO Connection)   |
+---------------------------+---------------------------+
                            |
                    PDO MySQL Connection
                            |
+---------------------------v---------------------------+
|              Database (MySQL / Railway)               |
|    - tb_products (รหัส, ชื่อสินค้า, ราคา, หน่วย, ...)  |
|    - tb_categories (หมวดหมู่สินค้า)                     |
|    - tb_suppliers (ผู้จัดจำหน่าย)                     |
+-------------------------------------------------------+
```

---

## 2. โครงสร้างโฟลเดอร์โปรเจกต์ (Project Structure)

```
Web_App_Project/
├── api/
│   ├── products.php             # REST API จัดการสินค้า (GET, POST, PUT, DELETE)
│   ├── categories.php           # API ดึงรายการหมวดหมู่สำหรับ Dropdown
│   ├── suppliers.php            # API ดึงรายชื่อผู้จัดจำหน่ายสำหรับ Dropdown
│   └── products/                # Sub-endpoints สำรอง (เพื่อความเข้ากันได้ 100%)
│       ├── read.php
│       ├── create.php
│       ├── update.php
│       └── delete.php
├── assets/
│   ├── css/
│   │   └── style.css            # สไตล์โมเดิร์น Responsive, KPI Cards, ตาราง
│   └── js/
│       └── app.js               # จัดการ Fetch API, DOM, Form Validation, SweetAlert2
├── config/
│   └── database.php             # เชื่อมต่อฐานข้อมูล PDO รองรับ Railway และ MAMP/XAMPP อัตโนมัติ
├── database/
│   ├── dbNorthwind.sql          # ไฟล์ Dump ฐานข้อมูลจากรายวิชา (สำหรับ Import ขึ้น Railway)
│   └── schema.sql               # โครงสร้างฐานข้อมูลสำรอง
├── public/                      # โฟลเดอร์ทางเลือกสำหรับ Web Server บางประเภท
│   └── index.php
├── index.php                    # หน้าเว็บหลักของแอปพลิเคชัน
├── composer.json                # กำหนด PHP runtime และ extensions (pdo_mysql) สำหรับ Railway
├── Procfile                     # คำสั่ง Start สำหรับ Cloud PaaS
├── railway.toml                 # กำหนดค่า Build และ Deploy บน Railway
├── .gitignore
└── README.md                    # คู่มือฉบับนี้
```

---

## 3. โครงสร้างฐานข้อมูล (Database Schema)

ฐานข้อมูลหลักใช้ชื่อว่า `db_northwind` อ้างอิงตามไฟล์ `dbNorthwind.sql`:

### ตารางหลัก: `tb_products` (ข้อมูลสินค้า)
| คอลัมน์ | ชนิดข้อมูล | คุณสมบัติ | คำอธิบาย |
|---|---|---|---|
| `i_ProductID` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | รหัสสินค้า |
| `c_ProductName` | VARCHAR(30) | NOT NULL | ชื่อสินค้า (ความยาวสูงสุด 30 ตัวอักษร) |
| `i_SupplierID` | INT(11) | NOT NULL, DEFAULT 1 | รหัสผู้จัดจำหน่าย (เชื่อมกับ `tb_suppliers`) |
| `i_CategoryID` | INT(11) | NOT NULL, DEFAULT 1 | รหัสหมวดหมู่ (เชื่อมกับ `tb_categories`) |
| `c_Unit` | VARCHAR(30) | NOT NULL | ขนาดบรรจุภัณฑ์/หน่วยสินค้า |
| `i_Price` | FLOAT | NOT NULL | ราคาต่อหน่วย (ต้อง >= 0) |

### ตารางเสริมที่นำมา JOIN แสดงผล:
- `tb_categories`: คอลัมน์ `i_CategoryID`, `c_CategoryName`, `c_Description`
- `tb_suppliers`: คอลัมน์ `i_SupplierID`, `c_SupplierName`, `c_Country`

---

## 4. คู่มือ REST API Endpoints (CRUD)

| Method | Endpoint | คำอธิบาย | ตัวอย่าง Request Body (JSON) |
|---|---|---|---|
| **GET** | `/api/products.php` | ดึงรายการสินค้าทั้งหมด | - |
| **GET** | `/api/products.php?q=chai` | ค้นหาสินค้าตามชื่อหรือรหัส ID | - |
| **GET** | `/api/products.php?id=1` | ดึงข้อมูลสินค้ารายชิ้นตาม ID | - |
| **POST** | `/api/products.php` | เพิ่มสินค้าใหม่ (Create) | `{"ProductName":"Latte","UnitPrice":65,"QuantityPerUnit":"1 cup","CategoryID":1,"SupplierID":1}` |
| **PUT** | `/api/products.php?id=1` | แก้ไขข้อมูลสินค้า (Update) | `{"ProductID":1,"ProductName":"Chai Tea","UnitPrice":45,"QuantityPerUnit":"10 boxes","CategoryID":1,"SupplierID":2}` |
| **DELETE**| `/api/products.php?id=1` | ลบสินค้าตาม ID (Delete) | - |
| **GET** | `/api/categories.php` | ดึงรายชื่อหมวดหมู่ทั้งหมด | - |
| **GET** | `/api/suppliers.php` | ดึงรายชื่อผู้จัดจำหน่ายทั้งหมด | - |

---

## 5. การทดสอบรันบนเครื่องตนเอง (Localhost with MAMP / XAMPP)

1. นำโฟลเดอร์โปรเจกต์ `Web_App_Project` วางในโฟลเดอร์ DocumentRoot:
   - **MAMP**: `C:\MAMP\htdocs\CPE66\Web_App_Project`
   - **XAMPP**: `C:\xampp\htdocs\CPE66\Web_App_Project`
2. นำเข้าฐานข้อมูล `dbNorthwind.sql`:
   - เปิด phpMyAdmin (เช่น `http://localhost/phpmyadmin` หรือ `http://localhost:8888/phpmyadmin`)
   - เลือกแท็บ **Import** -> เลือกไฟล์ `database/dbNorthwind.sql` -> กด **Import/Go**
3. เข้าใช้งานผ่าน Web Browser:
   - เปิด URL: `http://localhost/CPE66/Web_App_Project/`
   *(ไฟล์ `config/database.php` จะตรวจจับและเชื่อมต่อ MAMP รหัสผ่าน `root` หรือ XAMPP รหัสผ่านว่าง `""` ให้อัตโนมัติ)*

---

## 6. ขั้นตอนการ Deploy ขึ้น Railway PaaS อย่างละเอียด (Step-by-Step)

### ขั้นตอนที่ 1: เตรียม Git Repository
1. นำโค้ดขึ้น GitHub:
   ```bash
   git init
   git add .
   git commit -m "Initial commit for Railway deployment"
   git branch -M main
   git remote add origin https://github.com/<YOUR_GITHUB_USER>/<YOUR_REPO_NAME>.git
   git push -u origin main
   ```

### ขั้นตอนที่ 2: สร้างโปรเจกต์และฐานข้อมูล MySQL บน Railway
1. เข้าเว็บไซต์ [https://railway.com/](https://railway.com/) และ Login ด้วยบัญชี GitHub
2. กดปุ่ม **+ New Project**
3. เลือก **Provision MySQL** เพื่อสร้าง Database Service
4. รอระบบสร้าง MySQL เสร็จสิ้น จะเห็นไอคอน MySQL ปรากฏขึ้นใน Canvas

### ขั้นตอนที่ 3: นำเข้าฐานข้อมูล (Import dbNorthwind.sql) บน Railway
สามารถเลือกทำได้ 2 วิธี:
- **วิธีที่ 1 (ง่ายที่สุดผ่าน Web Query Editor)**:
  1. คลิกที่กล่อง **MySQL Service** บน Railway Dashboard
  2. ไปที่แท็บ **Data**
  3. เปิดไฟล์ `database/dbNorthwind.sql` ด้วยโปรแกรม Notepad หรือ VS Code แล้วคัดลอก (Copy) คำสั่ง SQL ทั้งหมด
  4. นำไปวางในช่อง Query แล้วกด **Run Query**
- **วิธีที่ 2 (ผ่าน MySQL CLI)**:
  1. ไปที่แท็บ **Connect** ของ MySQL Service เพื่อดูคำสั่งเชื่อมต่อ
  2. รันคำสั่งใน Terminal:
     ```bash
     mysql -h <MYSQLHOST> -u <MYSQLUSER> -p<MYSQLPASSWORD> -P <MYSQLPORT> <MYSQLDATABASE> < database/dbNorthwind.sql
     ```

### ขั้นตอนที่ 4: สร้าง Web Service สำหรับ PHP Web App
1. ในหน้าโปรเจกต์เดิมบน Railway กดปุ่ม **+ Create** หรือ **+ New**
2. เลือก **GitHub Repo** แล้วเลือก Repository โปรเจกต์นี้
3. Railway จะตรวจจับ `composer.json` และ `railway.toml` เพื่อทำการ Build อัตโนมัติด้วย PHP 8.x

### ขั้นตอนที่ 5: ผูก Environment Variables เชื่อมต่อฐานข้อมูล
1. คลิกที่กล่อง **Web Service** (บริการโค้ด PHP)
2. ไปที่แท็บ **Variables**
3. กดปุ่ม **Add Variable** หรือ **Add Reference** เพื่อดึงค่าจาก MySQL Service:
   - `MYSQLHOST` = `${{MySQL.MYSQLHOST}}`
   - `MYSQLPORT` = `${{MySQL.MYSQLPORT}}`
   - `MYSQLDATABASE` = `${{MySQL.MYSQLDATABASE}}`
   - `MYSQLUSER` = `${{MySQL.MYSQLUSER}}`
   - `MYSQLPASSWORD` = `${{MySQL.MYSQLPASSWORD}}`
   *(ระบบ `config/database.php` ของโปรเจกต์นี้รองรับตัวแปรเหล่านี้โดยตรง)*
4. กด **Save / Deploy Changes** ระบบจะทำการ Redeploy ให้อัตโนมัติ

### ขั้นตอนที่ 6: สร้าง Public Domain เพื่อรับ Live Application URL
1. คลิกที่กล่อง **Web Service** -> ไปที่แท็บ **Settings**
2. ในส่วน **Networking** กดปุ่ม **Generate Domain**
3. ท่านจะได้รับ URL ประจำโปรเจกต์ เช่น:
   `https://northwind-product-manager-production.up.railway.app`
4. ทดสอบคลิกเปิด URL เพื่อตรวจสอบว่าหน้าเว็บโหลดข้อมูลจากฐานข้อมูลขึ้นมาแสดงผลถูกต้อง และทดลอง ค้นหา / เพิ่ม / แก้ไข / ลบ สินค้า

---

## 7. แนวทางการเตรียมเอกสารส่งงาน (Submission Guide)

อ้างอิงจากเกณฑ์การส่งงาน (Deadline: 23/09/69 23:59):

1. **Live Application URL**:
   - นำ Domain ที่สร้างจากขั้นตอนที่ 6 (เช่น `https://xxxx.up.railway.app`) ไปกรอกในช่องส่งงาน
2. **ลิงก์ Google Doc (Process Documentation)**:
   - นำเนื้อหาในข้อ 1 ถึงข้อ 6 ของคู่มือฉบับนี้ไปใส่ใน Google Docs
   - แคปภาพหน้าจอ (Screenshots) ประกอบ 4 ส่วนหลัก:
     1. หน้าจอ Railway Dashboard แสดงทั้ง Web App Service และ MySQL Service
     2. หน้าจอแท็บ Data/Query แสดงตาราง `tb_products` บน Railway
     3. หน้าจอแท็บ Variables แสดงการผูกตัวแปรฐานข้อมูล
     4. หน้าจอ Live Application แสดงการทำงาน CRUD (การค้นหา, เพิ่มสินค้าพร้อม Validation, แจ้งเตือน SweetAlert2 สำเร็จ, แก้ไข และลบ)
   - ตั้งค่าสิทธิ์การแชร์ Google Doc เป็น **"ทุกคนที่มีลิงก์มีสิทธิ์อ่าน" (Anyone with the link can view)**
3. **ลิงก์ Google Drive ของ Source Code ทั้งหมด**:
   - บีบอัด (Zip) โฟลเดอร์ `Web_App_Project` ทั้งหมด
   - อัปโหลดไฟล์ Zip ขึ้น Google Drive
   - ตั้งค่าสิทธิ์การแชร์ Google Drive เป็น **"ทุกคนที่มีลิงก์มีสิทธิ์ดาวน์โหลด" (Anyone with the link can view)**
