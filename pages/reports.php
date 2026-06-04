<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

if (!in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])) {
    header('Location: ../dashboard.php');
    exit;
}

$page_title = 'รายงานสรุปภาพรวม';

// 1. Fetch Summary Data
$stats = $pdo->query("SELECT 
    COUNT(id) as total_units,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
    SUM(CASE WHEN status = 'broken' THEN 1 ELSE 0 END) as broken
FROM product_serials")->fetch();

// 2. Fetch Total Value (from products joined with serials)
$total_value = $pdo->query("SELECT SUM(p.price) FROM product_serials ps JOIN products p ON ps.product_id = p.id")->fetchColumn();

// 3. Fetch Category Breakdown
$categories = $pdo->query("SELECT category, COUNT(*) as count FROM products GROUP BY category")->fetchAll();

require_once '../includes/header.php';
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<style>
    .dataTables_info {
        color: #adb5bd !important; /* Make it a lighter/muted color */
        font-size: 0.85rem;
    }
    .dataTables_length label, .dataTables_filter label {
        color: #6c757d !important;
        font-size: 0.85rem;
    }
    .dataTables_paginate .pagination {
        font-size: 0.85rem;
    }
</style>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0">รายการเบิกทั้งหมด</h6>
                <div class="btn-group">
                    <button id="btnBorrowExcel" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i> Excel</button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="borrowTable" class="table table-hover align-middle text-nowrap w-100">
                    <thead class="table-light">
                        <tr>
                            <th>ผู้เบิก</th>
                            <th>สินค้า</th>
                            <th>Serial Code</th>
                            <th>เลขครุภัณฑ์</th>
                            <th>สถานที่</th>
                            <th>วันที่เบิก</th>
                            <th class="text-end">ราคา</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Table -->
<div class="row g-4">
    <div class="col-12">
        <div class="card p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0">รายการพัสดุ</h6>
                <div class="btn-group">
                    <button id="btnInventoryExcel" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i> Excel</button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="inventoryTable" class="table table-hover align-middle text-nowrap w-100">
                    <thead class="table-light">
                        <tr>
                            <th>สินค้า</th>
                            <th>หมวดหมู่</th>
                            <th class="text-end">ราคาต่อหน่วย</th>
                            <th class="text-center">ทั้งหมด</th>
                            <th class="text-center">พร้อมใช้</th>
                            <th class="text-center">ถูกเบิก</th>
                            <th class="text-center">ชำรุด/สูญหาย</th>
                            <th class="text-end">มูลค่ารวม</th>
                            <th>ระดับสต็อก</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    var thaiLang = {
        "sProcessing":   "กำลังดำเนินการ...",
        "sLengthMenu":   "แสดง _MENU_ เร็คคอร์ด",
        "sZeroRecords":  "ไม่พบข้อมูล",
        "sInfo":         "แสดง _START_ ถึง _END_ จาก _TOTAL_ เร็คคอร์ด",
        "sInfoEmpty":    "แสดง 0 ถึง 0 จาก 0 เร็คคอร์ด",
        "sInfoFiltered": "(กรองข้อมูล _MAX_ ทุกเร็คคอร์ด)",
        "sSearch":       "ค้นหา:",
        "oPaginate": {
            "sFirst":    "หน้าแรก",
            "sPrevious": "ก่อนหน้า",
            "sNext":     "ถัดไป",
            "sLast":     "หน้าสุดท้าย"
        }
    };

    // Function to export all data from Server-Side Processing
    function newexportaction(e, dt, button, config) {
        var self = this;
        var oldStart = dt.settings()[0]._iDisplayStart;
<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

$page_title = 'รายงานสรุปภาพรวม';

// 1. Fetch Summary Data
$stats = $pdo->query("SELECT 
    COUNT(id) as total_units,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
    SUM(CASE WHEN status = 'broken' THEN 1 ELSE 0 END) as broken
FROM product_serials")->fetch();

// 2. Fetch Total Value (from products joined with serials)
$total_value = $pdo->query("SELECT SUM(p.price) FROM product_serials ps JOIN products p ON ps.product_id = p.id")->fetchColumn();

// 3. Fetch Category Breakdown
$categories = $pdo->query("SELECT category, COUNT(*) as count FROM products GROUP BY category")->fetchAll();

require_once '../includes/header.php';
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<style>
    .dataTables_info {
        color: #adb5bd !important; /* Make it a lighter/muted color */
        font-size: 0.85rem;
    }
    .dataTables_length label, .dataTables_filter label {
        color: #6c757d !important;
        font-size: 0.85rem;
    }
    .dataTables_paginate .pagination {
        font-size: 0.85rem;
    }
</style>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0">รายการเบิกทั้งหมด</h6>
                <div class="btn-group">
                    <button id="btnBorrowExcel" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i> Excel</button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="borrowTable" class="table table-hover align-middle text-nowrap w-100">
                    <thead class="table-light">
                        <tr>
                            <th>ผู้เบิก</th>
                            <th>สินค้า</th>
                            <th>Serial Code</th>
                            <th>เลขครุภัณฑ์</th>
                            <th>สถานที่</th>
                            <th>วันที่เบิก</th>
                            <th class="text-end">ราคา</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Table -->
<div class="row g-4">
    <div class="col-12">
        <div class="card p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0">รายการพัสดุ</h6>
                <div class="btn-group">
                    <button id="btnInventoryExcel" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i> Excel</button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="inventoryTable" class="table table-hover align-middle text-nowrap w-100">
                    <thead class="table-light">
                        <tr>
                            <th>สินค้า</th>
                            <th>หมวดหมู่</th>
                            <th class="text-end">ราคาต่อหน่วย</th>
                            <th class="text-center">ทั้งหมด</th>
                            <th class="text-center">พร้อมใช้</th>
                            <th class="text-center">ถูกเบิก</th>
                            <th class="text-center">ชำรุด/สูญหาย</th>
                            <th class="text-end">มูลค่ารวม</th>
                            <th>ระดับสต็อก</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded by DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    // We will dynamically configure fonts in the customize function
    var thaiLang = {
        "sProcessing":   "กำลังดำเนินการ...",
        "sLengthMenu":   "แสดง _MENU_ เร็คคอร์ด",
        "sZeroRecords":  "ไม่พบข้อมูล",
        "sInfo":         "แสดง _START_ ถึง _END_ จาก _TOTAL_ เร็คคอร์ด",
        "sInfoEmpty":    "แสดง 0 ถึง 0 จาก 0 เร็คคอร์ด",
        "sInfoFiltered": "(กรองข้อมูล _MAX_ ทุกเร็คคอร์ด)",
        "sSearch":       "ค้นหา:",
        "oPaginate": {
            "sFirst":    "หน้าแรก",
            "sPrevious": "ก่อนหน้า",
            "sNext":     "ถัดไป",
            "sLast":     "หน้าสุดท้าย"
        }
    };

    // Function to export all data from Server-Side Processing
    function newexportaction(e, dt, button, config) {
        var self = this;
        var oldStart = dt.settings()[0]._iDisplayStart;
        dt.one('preXhr', function (e, s, data) {
            data.start = 0;
            data.length = -1; // -1 to get all records
            dt.one('preDraw', function (e, settings) {
                if (button[0].className.indexOf('buttons-excel') >= 0) {
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config);
                }
                dt.one('preXhr', function (e, s, data) {
                    settings._iDisplayStart = oldStart;
                    data.start = oldStart;
                });
                setTimeout(dt.ajax.reload, 0);
                return false;
            });
        });
        dt.ajax.reload();
    }

    var borrowTable = $('#borrowTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajax_reports_borrow.php",
            "type": "POST"
        },
        "order": [[ 5, "desc" ]],
        "language": thaiLang,
        "columnDefs": [
            { "orderable": false, "targets": [3, 4] },
            { "className": "text-end", "targets": [6] }
        ],
        "buttons": [
            {
                extend: 'excelHtml5',
                action: newexportaction,
                title: 'รายงานการเบิกทั้งหมด',
                exportOptions: { columns: ':visible' }
            }
        ]
    });

    var inventoryTable = $('#inventoryTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajax_reports_inventory.php",
            "type": "POST"
        },
        "order": [[ 0, "asc" ]],
        "language": thaiLang,
        "columnDefs": [
            { "className": "text-end", "targets": [2, 7] },
            { "className": "text-center", "targets": [3, 4, 5, 6] },
            { "orderable": false, "targets": [8] }
        ],
        "buttons": [
            {
                extend: 'excelHtml5',
                action: newexportaction,
                title: 'รายงานพัสดุ',
                exportOptions: { columns: ':visible' }
            }
        ]
    });

    // Bind custom UI buttons to DataTables buttons
    $('#btnBorrowExcel').on('click', function() {
        borrowTable.button('.buttons-excel').trigger();
    });

    $('#btnInventoryExcel').on('click', function() {
        inventoryTable.button('.buttons-excel').trigger();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
