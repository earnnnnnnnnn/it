<?php
require_once '../includes/auth.php';

if (!in_array($_SESSION['user_role'] ?? 'USER', ['ADMIN', 'SUPERADMIN'])) {
    header("Location: ../dashboard.php");
    exit();
}

require_once '../config/db.php';

$page_title = 'ค้นหาครุภัณฑ์ (SSR)';

require_once '../includes/header.php';
?>

<!-- DataTables CSS (Standard) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<!-- Select2 for searchable dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="row g-4">
    <div class="col-12">
        <!-- Simple Search Bar -->
        <div class="card p-3 border shadow-sm mb-4">
            <div class="row g-3 mb-3">
                <div class="col-md-8 col-12">
                    <div class="input-group custom-search-group">
                        <span class="input-group-text bg-white text-muted border-end-0">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="customSearchInput" class="form-control border-start-0 border-end-0 shadow-none" placeholder="พิมพ์เพื่อค้นหาทุกอย่าง..." autofocus>
                        <button class="btn btn-outline-secondary bg-white border-start-0 text-muted" type="button" id="btnClearSearch">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4 col-12">
                    <div class="input-group custom-search-group">
                        <span class="input-group-text bg-white text-muted border-end-0">
                            <i class="fas fa-sort-amount-down"></i>
                        </span>
                        <select id="dateSort" class="form-select border-start-0 shadow-none text-secondary">
                            <option value="desc">วันที่: ใหม่ไปเก่า</option>
                            <option value="asc">วันที่: เก่าไปใหม่</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small fw-medium"><i class="fas fa-info-circle text-primary me-1"></i> ค้นหาจาก:</span>
                <span class="badge search-tag">ชื่อสินค้า/ยี่ห้อ/รุ่น</span>
                <span class="badge search-tag">เลขครุภัณฑ์ (Asset)</span>
                <span class="badge search-tag">Serial Number (S/N)</span>
                <span class="badge search-tag">แผนก/อาคาร/ชั้น</span>
                <span class="badge search-tag">ชื่อผู้เบิก/ผู้อนุมัติ</span>
                <span class="badge search-tag">สถานะสินค้า</span>
                <span class="badge search-tag">ราคา</span>
                <span class="badge search-tag">วันที่เบิก</span>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="ssrTable" class="table table-sm table-striped table-hover align-middle mb-0" style="width:100%; font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>สินค้า</th>
                                <th>เลขครุภัณฑ์ / Serial</th>
                                <th class="text-center">สถานะ</th>
                                <th>ผู้ถือครอง / สถานที่</th>
                                <th class="text-end">ราคา</th>
                                <th>วันที่เบิก</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data handled by DataTables Server-side -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="historyModalLabel">
                    <i class="fas fa-history text-primary me-2"></i>ประวัติการเบิกใช้งาน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3" id="historyModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Borrowing Modal -->
<div class="modal fade" id="editBorrowModal" tabindex="-1" aria-labelledby="editBorrowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
            <div class="modal-header border-0 px-4 pt-4 pb-2">
                <div>
                    <h5 class="modal-title fw-bold" id="editBorrowModalLabel">
                        <i class="fas fa-pen-to-square text-primary me-2"></i>แก้ไขรายการเบิก
                    </h5>
                    <div class="text-muted small mt-1" id="editBorrowSubtitle"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div id="editBorrowLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <div class="text-muted small mt-2">กำลังโหลดข้อมูล...</div>
                </div>
                <form id="editBorrowForm" class="d-none">
                    <input type="hidden" id="edit_borrow_id" name="id">
                    
                    <!-- Product Info (Read-only) -->
                    <div class="bg-light rounded-3 p-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; min-width: 48px;">
                                <i class="fas fa-box fa-lg"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark" id="edit_product_name"></div>
                                <div class="text-muted small" id="edit_product_detail"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Borrower -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                <i class="fas fa-user me-1 text-primary"></i> ผู้เบิก <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="edit_borrower_id" name="borrower_id" required>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                <i class="fas fa-barcode me-1 text-primary"></i> เลขครุภัณฑ์
                            </label>
                            <input type="text" class="form-control" id="edit_asset_number" name="asset_number" placeholder="เช่น 7440-001-0001-60-0096">
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">
                                <i class="fas fa-building me-1 text-primary"></i> อาคาร
                            </label>
                            <select class="form-select" id="edit_building" name="building">
                                <option value="">-- เลือกอาคาร --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">
                                <i class="fas fa-layer-group me-1 text-primary"></i> ชั้น
                            </label>
                            <select class="form-select" id="edit_floor" name="floor">
                                <option value="">-- เลือกชั้น --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">
                                <i class="fas fa-users me-1 text-primary"></i> แผนก
                            </label>
                            <select class="form-select" id="edit_department" name="department">
                                <option value="">-- เลือกแผนก --</option>
                            </select>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">
                            <i class="fas fa-comment-dots me-1 text-primary"></i> เหตุผลการเบิก
                        </label>
                        <select class="form-select" id="edit_reason" name="reason">
                            <option value="">-- เลือกเหตุผล --</option>
                        </select>
                    </div>

                    <!-- Dates -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                <i class="fas fa-calendar-plus me-1 text-success"></i> วันที่เบิก
                            </label>
                            <input type="datetime-local" class="form-control" id="edit_borrowed_at" name="borrowed_at">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                <i class="fas fa-calendar-check me-1 text-info"></i> วันที่คืน
                            </label>
                            <input type="datetime-local" class="form-control" id="edit_returned_at" name="returned_at">
                            <div class="form-text text-muted small">เว้นว่างไว้หากยังไม่ได้คืน</div>
                        </div>
                    </div>

                    <!-- Condition Image -->
                    <div id="edit_image_container" class="d-none mb-3">
                        <label class="form-label small fw-bold text-muted">
                            <i class="fas fa-camera me-1 text-primary"></i> รูปถ่ายสภาพ
                        </label>
                        <div>
                            <a id="edit_image_link" href="#" target="_blank">
                                <img id="edit_image_preview" src="" class="rounded-3 border shadow-sm" style="max-width: 200px; max-height: 150px; object-fit: cover;">
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-2">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" id="btnSaveEdit">
                    <i class="fas fa-save me-1"></i> บันทึกการแก้ไข
                </button>
            </div>
        </div>
    </div>
</div>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    let dataTable;

    function showHistory(serialId) {
        // Reset modal body to loading state
        $('#historyModalBody').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">กำลังโหลด...</span>
                </div>
            </div>
        `);
        
        // Show modal
        const myModal = new bootstrap.Modal(document.getElementById('historyModal'));
        myModal.show();
        
        // Fetch history content
        $.get('ajax_get_history.php', { id: serialId }, function(data) {
            $('#historyModalBody').html(data);
        }).fail(function() {
            $('#historyModalBody').html('<div class="alert alert-danger m-3">ไม่สามารถโหลดข้อมูลประวัติได้</div>');
        });
    }

    // --- Edit Borrowing ---
    function openEditModal(borrowId) {
        const modal = new bootstrap.Modal(document.getElementById('editBorrowModal'));
        $('#editBorrowLoading').removeClass('d-none');
        $('#editBorrowForm').addClass('d-none');
        modal.show();

        $.ajax({
            url: 'ajax_manage_borrow.php',
            method: 'GET',
            data: { action: 'get', id: borrowId },
            dataType: 'json',
            success: function(res) {
                if (!res.success) {
                    Swal.fire('ผิดพลาด', res.message, 'error');
                    modal.hide();
                    return;
                }

                const d = res.data;
                const opts = res.options;

                // Set product info
                $('#edit_borrow_id').val(d.id);
                $('#edit_product_name').text(d.product_name);
                $('#edit_product_detail').text((d.brand || '') + ' ' + (d.model || '') + ' — S/N: ' + d.serial_code);
                $('#editBorrowSubtitle').text('รหัสเบิก #' + d.id + ' • S/N: ' + d.serial_code);

                // Populate borrower select
                let borrowerHtml = '<option value="">-- เลือกผู้เบิก --</option>';
                opts.users.forEach(function(u) {
                    const selected = (u.id == d.borrower_id) ? 'selected' : '';
                    borrowerHtml += '<option value="'+u.id+'" '+selected+'>'+u.full_name+'</option>';
                });
                $('#edit_borrower_id').html(borrowerHtml);

                // Populate building select
                let buildingHtml = '<option value="">-- เลือกอาคาร --</option>';
                opts.buildings.forEach(function(b) {
                    const selected = (b === d.building) ? 'selected' : '';
                    buildingHtml += '<option value="'+b+'" '+selected+'>'+b+'</option>';
                });
                $('#edit_building').html(buildingHtml);

                // Populate floor select
                let floorHtml = '<option value="">-- เลือกชั้น --</option>';
                opts.floors.forEach(function(f) {
                    const selected = (f === d.floor) ? 'selected' : '';
                    floorHtml += '<option value="'+f+'" '+selected+'>'+f+'</option>';
                });
                $('#edit_floor').html(floorHtml);

                // Populate department select
                let deptHtml = '<option value="">-- เลือกแผนก --</option>';
                opts.departments.forEach(function(dp) {
                    const selected = (dp === d.department) ? 'selected' : '';
                    deptHtml += '<option value="'+dp+'" '+selected+'>'+dp+'</option>';
                });
                $('#edit_department').html(deptHtml);

                // Populate reason select
                let reasonHtml = '<option value="">-- เลือกเหตุผล --</option>';
                opts.reasons.forEach(function(r) {
                    const selected = (r === d.reason) ? 'selected' : '';
                    reasonHtml += '<option value="'+r+'" '+selected+'>'+r+'</option>';
                });
                // If current reason isn't in the list, add it
                if (d.reason && !opts.reasons.includes(d.reason)) {
                    reasonHtml += '<option value="'+d.reason+'" selected>'+d.reason+'</option>';
                }
                $('#edit_reason').html(reasonHtml);

                // Set other fields
                $('#edit_asset_number').val(d.asset_number || '');
                
                if (d.borrowed_at) {
                    // Convert to datetime-local format
                    const ba = new Date(d.borrowed_at);
                    $('#edit_borrowed_at').val(formatDatetimeLocal(ba));
                }
                if (d.returned_at) {
                    const ra = new Date(d.returned_at);
                    $('#edit_returned_at').val(formatDatetimeLocal(ra));
                } else {
                    $('#edit_returned_at').val('');
                }

                // Show condition image if exists
                if (d.image) {
                    $('#edit_image_container').removeClass('d-none');
                    $('#edit_image_preview').attr('src', '../assets/images/' + d.image);
                    $('#edit_image_link').attr('href', '../assets/images/' + d.image);
                } else {
                    $('#edit_image_container').addClass('d-none');
                }

                // Init Select2 on borrower
                $('#edit_borrower_id').select2({
                    dropdownParent: $('#editBorrowModal'),
                    placeholder: '-- เลือกผู้เบิก --',
                    allowClear: true,
                    language: {
                        noResults: function() { return 'ไม่พบข้อมูล'; },
                        searching: function() { return 'กำลังค้นหา...'; }
                    }
                });

                // Show form, hide loading
                $('#editBorrowLoading').addClass('d-none');
                $('#editBorrowForm').removeClass('d-none');
            },
            error: function() {
                Swal.fire('ผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
                modal.hide();
            }
        });
    }

    function formatDatetimeLocal(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth()+1).padStart(2,'0');
        const d = String(date.getDate()).padStart(2,'0');
        const h = String(date.getHours()).padStart(2,'0');
        const min = String(date.getMinutes()).padStart(2,'0');
        return y+'-'+m+'-'+d+'T'+h+':'+min;
    }

    // --- Delete Borrowing ---
    function deleteBorrow(borrowId) {
        Swal.fire({
            title: 'ยืนยันการยกเลิก/ลบ?',
            html: '<div class="text-muted">รายการเบิกนี้จะถูกยกเลิกและลบออกจากระบบ<br>และสถานะสินค้าจะถูกเปลี่ยนกลับเป็น <span class="badge bg-success-subtle text-success">พร้อมใช้งาน</span></div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-trash-can me-1"></i> ยกเลิกรายการ',
            cancelButtonText: 'ปิด',
            reverseButtons: true,
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'ajax_manage_borrow.php',
                    method: 'POST',
                    data: { action: 'delete', id: borrowId },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'ยกเลิกสำเร็จ!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            dataTable.ajax.reload(null, false);
                        } else {
                            Swal.fire('ผิดพลาด', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        dataTable = $('#ssrTable').DataTable({
            "processing": true,
            "serverSide": true,
            "dom": 'lrtip', // Hide default search (f)
            "ajax": {
                "url": "ajax_search_server_side.php",
                "type": "POST"
            },
            "columns": [
                { "orderable": true },
                { "orderable": true },
                { "orderable": true, "className": "text-center" },
                { "orderable": true },
                { "orderable": true, "className": "text-end" },
                { "orderable": true }
            ],
            "language": {
                "lengthMenu": "แสดง _MENU_ รายการ",
                "zeroRecords": "ไม่พบข้อมูลที่ค้นหา",
                "info": "แสดงหน้าที่ _PAGE_ จากทั้งหมด _PAGES_ หน้า (รวม _TOTAL_ รายการ)",
                "infoEmpty": "ไม่มีข้อมูล",
                "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
                "paginate": {
                    "first": "หน้าแรก",
                    "last": "หน้าสุดท้าย",
                    "next": "ถัดไป",
                    "previous": "ก่อนหน้า"
                }
            },
            "pageLength": 10,
            "order": [[ 5, "desc" ]] // Sort by date desc
        });

        let searchTimeout;

        // Real-time search on input (with 300ms debounce)
        $('#customSearchInput').on('input', function() {
            const val = $(this).val();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                dataTable.search(val).draw();
            }, 300);
        });

        // Search on Enter key (instantly)
        $('#customSearchInput').on('keypress', function(e) {
            if(e.which == 13) {
                clearTimeout(searchTimeout);
                const val = $(this).val();
                dataTable.search(val).draw();
            }
        });

        // Clear search input
        $('#btnClearSearch').on('click', function() {
            clearTimeout(searchTimeout);
            $('#customSearchInput').val('').focus();
            dataTable.search('').draw();
        });

        // Date Sort Dropdown
        $('#dateSort').on('change', function() {
            const dir = $(this).val();
            dataTable.order([5, dir]).draw();
        });

        // Sync dropdown if table header is clicked
        dataTable.on('order.dt', function() {
            const order = dataTable.order();
            if (order && order.length > 0 && order[0][0] === 5) {
                $('#dateSort').val(order[0][1]);
            }
        });

        // Listen to global barcode scanning event
        $(document).on('barcodeScanned', function(e, barcode) {
            clearTimeout(searchTimeout);
            $('#customSearchInput').val(barcode);
            dataTable.search(barcode).draw();
        });

        // --- Edit button click ---
        $(document).on('click', '.btn-edit-borrow', function() {
            const id = $(this).data('id');
            openEditModal(id);
        });

        // --- Delete button click ---
        $(document).on('click', '.btn-delete-borrow', function() {
            const id = $(this).data('id');
            deleteBorrow(id);
        });

        // --- Save edit ---
        $('#btnSaveEdit').on('click', function() {
            const form = $('#editBorrowForm');
            const borrowerId = $('#edit_borrower_id').val();

            if (!borrowerId) {
                Swal.fire('กรุณาเลือกผู้เบิก', '', 'warning');
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...');

            $.ajax({
                url: 'ajax_manage_borrow.php',
                method: 'POST',
                data: form.serialize() + '&action=update',
                dataType: 'json',
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> บันทึกการแก้ไข');
                    if (res.success) {
                        bootstrap.Modal.getInstance(document.getElementById('editBorrowModal')).hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'บันทึกสำเร็จ!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        dataTable.ajax.reload(null, false);
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> บันทึกการแก้ไข');
                    Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                }
            });
        });

        // Cleanup Select2 when modal hides
        $('#editBorrowModal').on('hidden.bs.modal', function() {
            if ($('#edit_borrower_id').hasClass('select2-hidden-accessible')) {
                $('#edit_borrower_id').select2('destroy');
            }
        });
    });
</script>

<style>
    /* Ensure the table looks normal and clean */
    .table.dataTable {
        margin-top: 10px !important;
        margin-bottom: 10px !important;
    }
    .dataTables_info {
        font-size: 0.85rem;
        color: #6c757d;
        padding-top: 15px !important;
    }
    .dataTables_paginate {
        padding-top: 10px !important;
    }
    code {
        font-size: 0.75rem;
    }
    /* Unified input group styling */
    .custom-search-group {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: all 0.2s ease-in-out;
    }
    .custom-search-group .input-group-text,
    .custom-search-group .form-control,
    .custom-search-group .form-select,
    .custom-search-group .btn {
        border-color: #e2e8f0;
    }
    .custom-search-group:focus-within {
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        border-radius: 8px;
    }
    .custom-search-group:focus-within .input-group-text,
    .custom-search-group:focus-within .form-control,
    .custom-search-group:focus-within .form-select,
    .custom-search-group:focus-within .btn {
        border-color: #86b7fe;
    }
    .custom-search-group .input-group-text:first-child {
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
    }
    .custom-search-group .btn:last-child,
    .custom-search-group .form-select:last-child {
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    /* Search Tag style (Blue-colored indicators) */
    .search-tag {
        background-color: rgba(13, 110, 253, 0.08);
        color: #0d6efd;
        border: 1px solid rgba(13, 110, 253, 0.15);
        font-weight: 500;
        font-size: 0.8rem;
        padding: 6px 12px;
        border-radius: 50px;
        transition: all 0.2s ease-in-out;
        cursor: default;
    }
    .search-tag:hover {
        background-color: rgba(13, 110, 253, 0.15);
        transform: translateY(-1px);
    }
    /* Action buttons hover */
    .btn-edit-borrow, .btn-delete-borrow {
        transition: all 0.2s ease;
    }
    .btn-edit-borrow:hover {
        background-color: #0d6efd !important;
        color: white !important;
        transform: scale(1.1);
    }
    .btn-delete-borrow:hover {
        background-color: #ef4444 !important;
        color: white !important;
        transform: scale(1.1);
    }
    /* Select2 inside modal */
    #editBorrowModal .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 4px 8px;
    }
    #editBorrowModal .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px;
        font-size: 0.9rem;
    }
    #editBorrowModal .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    #editBorrowModal .select2-dropdown {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    #editBorrowModal .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 12px;
    }
    #editBorrowModal .select2-results__option {
        padding: 8px 12px;
        font-size: 0.9rem;
    }
    #editBorrowModal .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: #4361ee;
        border-radius: 6px;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
