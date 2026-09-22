<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Northwind Product Manager | Web Application Project</title>

    <!-- Google Fonts: Prompt -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-dark navbar-custom">
        <div class="container">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-25 p-2 text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="fa-solid fa-boxes-stacked fs-4 text-white"></i>
                </div>
                <div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle badge-tag mb-1">
                        CPE66 • COURSE PROJECT
                    </span>
                    <h1 class="brand-title text-white mb-0">Northwind Product Manager</h1>
                </div>
            </div>
            <div class="d-none d-md-flex align-items-center gap-2">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                    <i class="fa-solid fa-circle-check me-1"></i> REST API & Cloud PaaS Active
                </span>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container py-4">

        <!-- KPI Metrics Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon blue">
                        <i class="fa-solid fa-box-archive"></i>
                    </div>
                    <div>
                        <div class="metric-label">สินค้าทั้งหมด (Total Products)</div>
                        <h3 class="metric-val" id="kpiTotalProducts">--</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon green">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div>
                        <div class="metric-label">หมวดหมู่สินค้า (Categories)</div>
                        <h3 class="metric-val" id="kpiTotalCategories">--</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="metric-icon purple">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div>
                        <div class="metric-label">ราคาเฉลี่ยต่อหน่วย (Avg Price)</div>
                        <h3 class="metric-val" id="kpiAvgPrice">--</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card: Toolbar + Table -->
        <div class="main-card">
            
            <!-- Toolbar & Search -->
            <div class="toolbar-section">
                <div class="row g-2 align-items-center">
                    <div class="col-lg-5 col-md-6">
                        <div class="input-group search-input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0" placeholder="ค้นหาชื่อสินค้า หรือ รหัส ID...">
                            <button class="btn btn-outline-secondary" type="button" onclick="resetSearch()" title="ล้างการค้นหา">
                                <i class="fa-solid fa-rotate-left"></i> รีเซ็ต
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-3">
                        <select id="categoryFilter" class="form-select">
                            <option value="">กำลังโหลดหมวดหมู่...</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3 text-md-end">
                        <button class="btn btn-primary w-100 w-md-auto fw-semibold shadow-sm px-3" onclick="openAddModal()">
                            <i class="fa-solid fa-plus me-1"></i> เพิ่มสินค้าใหม่
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Header Bar -->
            <div class="d-flex justify-content-between align-items-center px-4 py-3 bg-light border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="mb-0 fw-bold text-dark">รายการสินค้าในระบบ (Northwind Products)</h6>
                    <span id="productCountBadge" class="badge bg-secondary-subtle text-secondary rounded-pill">0 รายการ</span>
                </div>
                <button class="btn btn-sm btn-link text-decoration-none text-muted p-0" onclick="loadProducts()">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> รีเฟรชตาราง
                </button>
            </div>

            <!-- Table Container -->
            <div class="table-responsive">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>ชื่อสินค้า (Product Name)</th>
                            <th>หมวดหมู่ (Category)</th>
                            <th>ผู้จัดจำหน่าย (Supplier)</th>
                            <th>ขนาด/บรรจุภัณฑ์ (Quantity Per Unit)</th>
                            <th>ราคาต่อหน่วย (Unit Price)</th>
                            <th style="width: 110px;" class="text-end">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody">
                        <!-- Dynamic Rows by JavaScript -->
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Footer Info -->
        <footer class="text-center py-4 mt-4 text-muted small">
            <div>Web Application Project: Development and Deployment of Web App with PHP and MySQL</div>
            <div class="text-secondary opacity-75">Cloud Platform: Railway (PaaS) • Database: Northwind (MySQL) • Architecture: REST API CRUD</div>
        </footer>

    </main>

    <!-- Modal เพิ่ม / แก้ไข สินค้า -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">
                        <i class="fa-solid fa-plus-circle text-primary me-2"></i> เพิ่มข้อมูลสินค้าใหม่
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm" onsubmit="event.preventDefault(); saveProduct();">
                        <input type="hidden" id="formProductID">

                        <!-- ชื่อสินค้า -->
                        <div class="mb-3">
                            <label for="formProductName" class="form-label">
                                ชื่อสินค้า (Product Name) <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="formProductName" placeholder="เช่น Chai, Chang, Tofu" maxlength="30" required>
                            <div class="form-text">ความยาวไม่เกิน 30 ตัวอักษร</div>
                        </div>

                        <!-- หมวดหมู่ และ ผู้จัดจำหน่าย -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="formCategory" class="form-label">หมวดหมู่สินค้า (Category)</label>
                                <select id="formCategory" class="form-select">
                                    <option value="">กำลังโหลดหมวดหมู่...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="formSupplier" class="form-label">ผู้จัดจำหน่าย (Supplier)</label>
                                <select id="formSupplier" class="form-select">
                                    <option value="">กำลังโหลดผู้จัดจำหน่าย...</option>
                                </select>
                            </div>
                        </div>

                        <!-- หน่วยบรรจุ และ ราคาต่อหน่วย -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <label for="formQuantityPerUnit" class="form-label">ขนาดบรรจุภัณฑ์ / หน่วย</label>
                                <input type="text" class="form-control" id="formQuantityPerUnit" placeholder="เช่น 10 boxes x 20 bags" maxlength="30">
                            </div>
                            <div class="col-md-5">
                                <label for="formUnitPrice" class="form-label">
                                    ราคาต่อหน่วย (฿) <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" min="0" class="form-control" id="formUnitPrice" placeholder="0.00" required>
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" id="btnSubmitProduct" class="btn btn-primary px-4" onclick="saveProduct()">
                        <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกข้อมูล
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3.3 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom Application JS -->
    <script src="assets/js/app.js"></script>

</body>
</html>
