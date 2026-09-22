/**
 * Northwind Product Manager - JavaScript Application
 * รองรับ CRUD API (RESTful), Search, Validation, และ SweetAlert2 Notifications
 */

const API_PRODUCTS = './api/products.php';
const API_CATEGORIES = './api/categories.php';
const API_SUPPLIERS = './api/suppliers.php';

let productModalInstance = null;
let allProducts = [];
let categoriesList = [];
let suppliersList = [];

// Toast notification helper with SweetAlert2
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    }
});

// รันเมื่อโหลด DOM เรียบร้อยแล้ว
document.addEventListener('DOMContentLoaded', () => {
    // กำหนด Modal Instance
    const modalEl = document.getElementById('productModal');
    if (modalEl) {
        productModalInstance = new bootstrap.Modal(modalEl);
    }

    // โหลดข้อมูลเริ่มต้น (Categories, Suppliers, Products)
    loadCategories();
    loadSuppliers();
    loadProducts();

    // Event Listeners สำหรับการค้นหา
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                searchProducts();
            }, 350);
        });

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchProducts();
            }
        });
    }

    // Category Filter dropdown change
    const catFilter = document.getElementById('categoryFilter');
    if (catFilter) {
        catFilter.addEventListener('change', () => {
            filterByCategory(catFilter.value);
        });
    }
});

/**
 * 1. โหลดข้อมูลหมวดหมู่ (Categories)
 */
async function loadCategories() {
    try {
        const res = await fetch(API_CATEGORIES);
        const result = await res.json();
        if (result.success && Array.isArray(result.data)) {
            categoriesList = result.data;
            populateCategorySelects();
        }
    } catch (err) {
        console.error('Failed to load categories:', err);
    }
}

/**
 * เติมข้อมูลลงใน Dropdown หมวดหมู่
 */
function populateCategorySelects() {
    const filterSelect = document.getElementById('categoryFilter');
    const formSelect = document.getElementById('formCategory');

    if (filterSelect) {
        filterSelect.innerHTML = '<option value="">ทุกหมวดหมู่ (All Categories)</option>';
        categoriesList.forEach(c => {
            filterSelect.innerHTML += `<option value="${c.CategoryID}">${c.CategoryName}</option>`;
        });
    }

    if (formSelect) {
        formSelect.innerHTML = '<option value="">-- เลือกหมวดหมู่ --</option>';
        categoriesList.forEach(c => {
            formSelect.innerHTML += `<option value="${c.CategoryID}">${c.CategoryName}</option>`;
        });
    }
}

/**
 * 2. โหลดข้อมูลผู้จัดจำหน่าย (Suppliers)
 */
async function loadSuppliers() {
    try {
        const res = await fetch(API_SUPPLIERS);
        const result = await res.json();
        if (result.success && Array.isArray(result.data)) {
            suppliersList = result.data;
            const formSelect = document.getElementById('formSupplier');
            if (formSelect) {
                formSelect.innerHTML = '<option value="">-- เลือกผู้จัดจำหน่าย --</option>';
                suppliersList.forEach(s => {
                    formSelect.innerHTML += `<option value="${s.SupplierID}">${s.SupplierName}</option>`;
                });
            }
        }
    } catch (err) {
        console.error('Failed to load suppliers:', err);
    }
}

/**
 * 3. ดึงรายการสินค้า (READ / SEARCH)
 */
async function loadProducts(keyword = '') {
    const tbody = document.getElementById('productTableBody');
    if (!tbody) return;

    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="loading-spinner">
                <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                กำลังโหลดข้อมูลสินค้า...
            </td>
        </tr>
    `;

    try {
        let url = API_PRODUCTS;
        if (keyword) {
            url += '?q=' + encodeURIComponent(keyword);
        }

        const res = await fetch(url);
        const result = await res.json();

        if (!result.success) {
            throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลสินค้าได้');
        }

        allProducts = result.data || [];
        renderTable(allProducts);
        updateDashboardKPIs(allProducts);

    } catch (err) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-danger">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> ${err.message}
                </td>
            </tr>
        `;
        Toast.fire({ icon: 'error', title: err.message });
    }
}

/**
 * วาดข้อมูลลงในตาราง HTML
 */
function renderTable(products) {
    const tbody = document.getElementById('productTableBody');
    const countBadge = document.getElementById('productCountBadge');
    if (!tbody) return;

    if (countBadge) {
        countBadge.textContent = `${products.length} รายการ`;
    }

    if (!products || products.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-state">
                    <i class="fa-regular fa-folder-open d-block"></i>
                    <h5 class="fw-semibold text-dark">ไม่พบข้อมูลสินค้า</h5>
                    <p class="small text-muted mb-0">ลองค้นหาด้วยคำอื่น หรือกดปุ่ม "เพิ่มสินค้าใหม่"</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = products.map(p => {
        const id = p.ProductID;
        const name = escapeHtml(p.ProductName);
        const category = escapeHtml(p.CategoryName || '-');
        const supplier = escapeHtml(p.SupplierName || '-');
        const unit = escapeHtml(p.QuantityPerUnit || '-');
        const price = Number(p.UnitPrice || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        return `
            <tr>
                <td class="text-muted fw-semibold">#${id}</td>
                <td>
                    <span class="fw-bold text-dark">${name}</span>
                </td>
                <td>
                    <span class="badge-category">${category}</span>
                </td>
                <td class="text-secondary small">
                    <i class="fa-regular fa-building me-1"></i> ${supplier}
                </td>
                <td class="text-muted small">${unit}</td>
                <td>
                    <span class="price-tag">฿${price}</span>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary btn-action me-1" title="แก้ไขสินค้า" onclick="openEditModal(${id})">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger btn-action" title="ลบสินค้า" onclick="deleteProduct(${id}, '${escapeJs(name)}')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * อัปเดตการ์ดตัวเลขสรุปด้านบน (KPI Metrics)
 */
function updateDashboardKPIs(products) {
    const totalEl = document.getElementById('kpiTotalProducts');
    const catEl = document.getElementById('kpiTotalCategories');
    const avgPriceEl = document.getElementById('kpiAvgPrice');

    if (totalEl) {
        totalEl.textContent = products.length.toLocaleString('th-TH');
    }

    if (catEl) {
        const distinctCats = new Set(products.map(p => p.CategoryID).filter(Boolean));
        catEl.textContent = distinctCats.size.toString();
    }

    if (avgPriceEl) {
        if (products.length > 0) {
            const sum = products.reduce((acc, curr) => acc + (Number(curr.UnitPrice) || 0), 0);
            const avg = sum / products.length;
            avgPriceEl.textContent = '฿' + avg.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
            avgPriceEl.textContent = '฿0.00';
        }
    }
}

/**
 * 4. ค้นหาสินค้า
 */
function searchProducts() {
    const input = document.getElementById('searchInput');
    const keyword = input ? input.value.trim() : '';
    loadProducts(keyword);
}

/**
 * รีเซ็ตการค้นหา
 */
function resetSearch() {
    const input = document.getElementById('searchInput');
    const catFilter = document.getElementById('categoryFilter');
    if (input) input.value = '';
    if (catFilter) catFilter.value = '';
    loadProducts('');
}

/**
 * กรองตามหมวดหมู่
 */
function filterByCategory(categoryId) {
    if (!categoryId) {
        renderTable(allProducts);
        updateDashboardKPIs(allProducts);
        return;
    }
    const filtered = allProducts.filter(p => String(p.CategoryID) === String(categoryId));
    renderTable(filtered);
    updateDashboardKPIs(filtered);
}

/**
 * 5. เปิด Modal เพิ่มสินค้าใหม่ (CREATE)
 */
function openAddModal() {
    const form = document.getElementById('productForm');
    if (form) form.reset();

    document.getElementById('formProductID').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-plus-circle text-primary me-2"></i> เพิ่มข้อมูลสินค้าใหม่';
    document.getElementById('btnSubmitProduct').innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> บันทึกข้อมูล';

    if (productModalInstance) {
        productModalInstance.show();
    }
}

/**
 * 6. เปิด Modal แก้ไขสินค้า (UPDATE)
 */
async function openEditModal(productId) {
    try {
        const res = await fetch(`${API_PRODUCTS}?id=${productId}`);
        const result = await res.json();

        if (!result.success || !result.data) {
            throw new Error(result.message || 'ไม่พบข้อมูลสินค้านี้');
        }

        const p = result.data;
        document.getElementById('formProductID').value = p.ProductID;
        document.getElementById('formProductName').value = p.ProductName || '';
        document.getElementById('formCategory').value = p.CategoryID || '';
        document.getElementById('formSupplier').value = p.SupplierID || '';
        document.getElementById('formQuantityPerUnit').value = p.QuantityPerUnit || '';
        document.getElementById('formUnitPrice').value = p.UnitPrice;

        document.getElementById('modalTitle').innerHTML = `<i class="fa-solid fa-pen-to-square text-primary me-2"></i> แก้ไขข้อมูลสินค้า #${p.ProductID}`;
        document.getElementById('btnSubmitProduct').innerHTML = '<i class="fa-solid fa-check me-1"></i> อัปเดตข้อมูล';

        if (productModalInstance) {
            productModalInstance.show();
        }

    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: err.message
        });
    }
}

/**
 * 7. บันทึกข้อมูลสินค้า (CREATE / UPDATE) พร้อม Validation
 */
async function saveProduct() {
    const id = document.getElementById('formProductID').value;
    const name = document.getElementById('formProductName').value.trim();
    const catId = document.getElementById('formCategory').value;
    const suppId = document.getElementById('formSupplier').value;
    const unit = document.getElementById('formQuantityPerUnit').value.trim();
    const priceVal = document.getElementById('formUnitPrice').value.trim();

    // ==========================================
    // Client-side Validation
    // ==========================================
    if (!name) {
        Swal.fire({
            icon: 'warning',
            title: 'แจ้งเตือน Validation',
            text: 'กรุณากรอก "ชื่อสินค้า" (Product Name)',
            confirmButtonColor: '#2563eb'
        });
        document.getElementById('formProductName').focus();
        return;
    }

    if (name.length > 30) {
        Swal.fire({
            icon: 'warning',
            title: 'แจ้งเตือน Validation',
            text: 'ชื่อสินค้าต้องมีความยาวไม่เกิน 30 ตัวอักษร',
            confirmButtonColor: '#2563eb'
        });
        document.getElementById('formProductName').focus();
        return;
    }

    if (priceVal === '' || isNaN(priceVal) || Number(priceVal) < 0) {
        Swal.fire({
            icon: 'warning',
            title: 'แจ้งเตือน Validation',
            text: 'กรุณากรอก "ราคาต่อหน่วย" ให้ถูกต้อง (ต้องเป็นตัวเลขที่ไม่ติดลบ)',
            confirmButtonColor: '#2563eb'
        });
        document.getElementById('formUnitPrice').focus();
        return;
    }

    const payload = {
        ProductName: name,
        CategoryID: catId ? parseInt(catId, 10) : 1,
        SupplierID: suppId ? parseInt(suppId, 10) : 1,
        QuantityPerUnit: unit,
        UnitPrice: parseFloat(priceVal)
    };

    const isEdit = Boolean(id);
    const url = isEdit ? `${API_PRODUCTS}?id=${id}` : API_PRODUCTS;
    const method = isEdit ? 'PUT' : 'POST';

    // Disable button & show spinner
    const btnSubmit = document.getElementById('btnSubmitProduct');
    const origBtnHtml = btnSubmit.innerHTML;
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> กำลังบันทึก...';

    try {
        const res = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await res.json();

        if (!result.success) {
            throw new Error(result.message || 'บันทึกข้อมูลไม่สำเร็จ');
        }

        // ปิด Modal
        if (productModalInstance) {
            productModalInstance.hide();
        }

        // แจ้งเตือนสำเร็จ
        Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ!',
            text: result.message || (isEdit ? 'อัปเดตข้อมูลสินค้าเรียบร้อยแล้ว' : 'เพิ่มสินค้าใหม่เรียบร้อยแล้ว'),
            timer: 2000,
            showConfirmButton: false
        });

        // โหลดข้อมูลตารางใหม่
        loadProducts();

    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: err.message,
            confirmButtonColor: '#2563eb'
        });
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = origBtnHtml;
    }
}

/**
 * 8. ลบข้อมูลสินค้า (DELETE)
 */
function deleteProduct(id, productName) {
    Swal.fire({
        title: 'ยืนยันการลบสินค้า?',
        html: `คุณแน่ใจหรือไม่ว่าต้องการลบสินค้า <br><strong>#${id} - ${escapeHtml(productName)}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await fetch(`${API_PRODUCTS}?id=${id}`, {
                    method: 'DELETE'
                });
                const data = await res.json();

                if (!data.success) {
                    throw new Error(data.message || 'ไม่สามารถลบสินค้าได้');
                }

                Toast.fire({
                    icon: 'success',
                    title: data.message || `ลบสินค้า #${id} เรียบร้อยแล้ว`
                });

                loadProducts();

            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'ลบสินค้าไม่สำเร็จ',
                    text: err.message,
                    confirmButtonColor: '#2563eb'
                });
            }
        }
    });
}

/**
 * Helper ฟังก์ชันป้องกัน XSS
 */
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeJs(str) {
    if (!str) return '';
    return String(str)
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"');
}
