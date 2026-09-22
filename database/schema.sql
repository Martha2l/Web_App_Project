-- ใช้กับ Northwind ที่มีตาราง Products อยู่แล้ว
-- ถ้ายังไม่มีฐานข้อมูล ให้สร้างก่อน:
CREATE DATABASE IF NOT EXISTS northwind CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE northwind;

-- โครงสร้างนี้ใช้กรณีต้องสร้าง Products เอง
CREATE TABLE IF NOT EXISTS Products (
  ProductID int NOT NULL AUTO_INCREMENT,
  ProductName varchar(40) NOT NULL,
  SupplierID int DEFAULT NULL,
  CategoryID int DEFAULT NULL,
  QuantityPerUnit varchar(20) DEFAULT NULL,
  UnitPrice decimal(10,2) DEFAULT 0.00,
  UnitsInStock smallint DEFAULT 0,
  UnitsOnOrder smallint DEFAULT 0,
  ReorderLevel smallint DEFAULT 0,
  Discontinued tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (ProductID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
