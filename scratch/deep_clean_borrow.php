<?php
$file = 'c:/xampp/htdocs/it/pages/borrow.php';
$content = file_get_contents($file);

// Find the last valid HTML before the first script tag
$htmlEndPos = strpos($content, '<script>');
if ($htmlEndPos === false) {
    die("Script tag not found");
}

$head = substr($content, 0, $htmlEndPos);

$cleanJS = '<script>
    $(document).ready(function() {
        const selectedSerials = new Map();

        // Search logic in right container
        $("#rightSearchInput").on("input", function() {
            const query = $(this).val();
            if (query.length < 2) {
                $("#rightSearchResult").empty();
                $("#rightSearchResultContainer").hide();
                return;
            }

            $.ajax({
                url: "ajax_search_available.php",
                method: "GET",
                data: { q: query },
                success: function(res) {
                    const result = JSON.parse(res);
                    $("#rightSearchResult").empty();
                    if (result.length > 0) {
                        $("#rightSearchResultContainer").show();
                    } else {
                        $("#rightSearchResultContainer").hide();
                    }
                    
                    result.forEach(item => {
                        const isAdded = selectedSerials.has(item.serial_code);
                        const isAvailable = item.status === "available";
                        
                        let statusHtml = "";
                        if (isAvailable) {
                            statusHtml = \'<span class="badge bg-success-subtle text-success">ว่าง</span>\';
                        } else if (item.status === "borrowed") {
                            statusHtml = `<div class="badge bg-danger-subtle text-danger mb-1" style="font-size: 9px;">ถูกเบิกโดย</div>
                                          <div class="fw-bold text-danger small" style="font-size: 10px;">${item.borrower_name || "ไม่ทราบชื่อ"}</div>`;
                        } else {
                            statusHtml = `<span class="badge bg-warning-subtle text-warning">${item.status}</span>`;
                        }

                        $("#rightSearchResult").append(`
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="../assets/images/${item.image}" class="rounded border" width="35" height="35" style="object-fit: cover;" onerror="this.src=\'../assets/images/default_product.png\'">
                                        <div style="line-height: 1.2;">
                                            <div class="fw-bold small">${item.name}</div>
                                            <div class="text-muted" style="font-size: 10px;">${item.brand} ${item.model}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="small"><code>${item.serial_code}</code></td>
                                <td class="fw-bold text-primary small">฿${parseFloat(item.price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                <td class="small">${statusHtml}</td>
                                <td class="text-end">
                                    ${isAvailable ? `
                                        <button class="btn btn-sm btn-outline-primary add-from-search" 
                                                data-code="${item.serial_code}" ${isAdded ? "disabled" : ""}>
                                            ${isAdded ? \'<i class="fas fa-check"></i>\' : \'<i class="fas fa-plus"></i>\'}
                                        </button>
                                    ` : ""}
                                </td>
                            </tr>
                        `);
                    });
                }
            });
        });

        $(document).on("click", ".add-from-search", function() {
            const code = $(this).data("code");
            lookupSerial(code);
            $(this).prop("disabled", true).html(\'<i class="fas fa-check"></i>\').removeClass("btn-outline-primary").addClass("btn-secondary");
        });

        function updateUI() {
            let total = 0;
            selectedSerials.forEach(item => {
                total += parseFloat(item.price || 0);
            });

            if (selectedSerials.size > 0) {
                $("#emptyRow").hide();
                $("#btnSubmitBorrow").prop("disabled", false);
            } else {
                $("#emptyRow").show();
                $("#btnSubmitBorrow").prop("disabled", true);
            }
            $("#itemCount").text(selectedSerials.size + " รายการ");
            $("#totalValue").text("฿" + total.toLocaleString(undefined, {minimumFractionDigits: 2}));
        }

        $(document).on("barcodeScanned", function(e, code) {
            lookupSerial(code);
        });

        let scanTimeout = null;
        $("#scanInput").on("input", function() {
            clearTimeout(scanTimeout);
            const inputEl = $(this);
            scanTimeout = setTimeout(function() {
                const code = inputEl.val().trim();
                if (code.length > 2) {
                    lookupSerial(code);
                    inputEl.val("").focus();
                }
            }, 200);
        });

        $("#scanInput").on("keydown", function(e) {
            if (e.which === 13) {
                e.preventDefault();
                clearTimeout(scanTimeout);
                const code = $(this).val().trim();
                if (code.length > 2) {
                    lookupSerial(code);
                    $(this).val("").focus();
                }
            }
        });

        function lookupSerial(code) {
            if (selectedSerials.has(code)) {
                Swal.fire({ icon: "info", title: "เลือกแล้ว", text: "รหัสนี้อยู่ในรายการเบิกแล้ว", timer: 1000, showConfirmButton: false });
                return;
            }
            $.ajax({
                url: "ajax_lookup_serial.php",
                method: "GET",
                data: { code: code },
                dataType: "json",
                success: function(res) {
                    if (res.success) {
                        if (res.data.status !== "available") {
                            Swal.fire({ icon: "error", title: "เบิกไม่ได้", text: "สินค้าสถานะ: " + (res.data.status === "borrowed" ? "ถูกเบิกไปแล้ว" : res.data.status) });
                            return;
                        }
                        selectedSerials.set(code, res.data);
                        const row = $(`
                            <tr id="row-${code}">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="../assets/images/${res.data.image}" class="rounded border" width="40" height="40" style="object-fit: cover;" onerror="this.src=\'../assets/images/default_product.png\'">
                                        <div>
                                            <div class="fw-bold text-dark">${res.data.name}</div>
                                            <div class="text-muted small">${res.data.brand} ${res.data.model}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><code>${code}</code></td>
                                <td class="fw-bold text-primary">฿${parseFloat(res.data.price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                <td><span class="badge bg-light text-dark border">${res.data.category}</span></td>
                                <td>
                                    <button class="btn btn-sm text-danger remove-item" data-code="${code}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                        $("#borrowList").append(row);
                        updateUI();
                    } else {
                        Swal.fire({ icon: "error", title: "ไม่พบรหัส", text: "ไม่พบ Serial " + code + " ในระบบ" });
                    }
                }
            });
        }

        $(document).on("click", ".remove-item", function() {
            const code = $(this).data("code");
            selectedSerials.delete(code);
            $(`#row-${code}`).remove();
            updateUI();
        });

        $("#btnSubmitBorrow").click(function() {
            const borrower_id = $("#borrower_id").val().trim();
            const asset_number = $("#asset_number").val().trim();
            const building = $("#building").val().trim();
            const floor = $("#floor").val().trim();
            const department = $("#department").val().trim();
            let reason = $("#reasonSelect").val();

            if (!borrower_id || !asset_number || !building || !floor || !department || !reason) {
                Swal.fire({ icon: "warning", title: "ข้อมูลไม่ครบ", text: "กรุณากรอกข้อมูลให้ครบทุกช่องที่มีเครื่องหมาย *" });
                return;
            }

            if (reason === "อื่นๆ") {
                reason = $("#otherReason").val().trim();
                if (!reason) {
                    Swal.fire({ icon: "warning", title: "ข้อมูลไม่ครบ", text: "กรุณาระบุเหตุผลอื่นๆ" });
                    return;
                }
            }

            const data = {
                borrower_id: borrower_id,
                asset_number: asset_number,
                building: building,
                floor: floor,
                department: department,
                reason: reason,
                serials: Array.from(selectedSerials.keys())
            };

            $.ajax({
                url: "ajax_process_borrow.php",
                method: "POST",
                data: JSON.stringify(data),
                contentType: "application/json",
                success: function(res) {
                    const result = JSON.parse(res);
                    if (result.success) {
                        Swal.fire({ icon: "success", title: "เบิกสำเร็จ", text: "บันทึกข้อมูลการเบิกแล้ว" }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({ icon: "error", title: "ผิดพลาด", text: result.message });
                    }
                }
            });
        });
    });

    function viewHistory() {
        const val = document.getElementById("asset_number").value.trim();
        if (!val) {
            Swal.fire({ icon: "warning", title: "กรุณากรอกข้อมูลค้นหา", timer: 1000, showConfirmButton: false });
            return;
        }

        $.ajax({
            url: "ajax_item_history.php",
            method: "GET",
            data: { q: val },
            success: function(res) {
                const data = JSON.parse(res);
                if (data.error) {
                    Swal.fire({ icon: "error", title: "ไม่พบข้อมูล", text: data.error });
                    return;
                }

                $("#historyTimeline").empty();
                
                if (data.type === "item") {
                    const item = data.item;
                    $("#historyItemHeader").html(`
                        <img src="../assets/images/${item.image}" class="rounded border shadow-sm" width="50" height="50" style="object-fit: cover;" onerror="this.src=\'../assets/images/default_product.png\'">
                        <div>
                            <div class="fw-bold text-primary">ประวัติของ: ${item.name}</div>
                            <div class="text-muted small">${item.brand} ${item.model} | Serial: ${item.serial_code}</div>
                        </div>
                    `);

                    data.history.forEach(h => {
                        $("#historyTimeline").append(`
                            <div class="list-group-item px-0 py-3 border-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="../assets/images/${h.user_image}" class="rounded-circle border" width="30" height="30" style="object-fit: cover;" onerror="this.src=\'../assets/images/default_user.png\'">
                                        <span class="fw-bold small">${h.firstname} ${h.lastname}</span>
                                    </div>
                                    <span class="badge bg-light text-dark fw-normal" style="font-size: 10px;">${h.borrow_date}</span>
                                </div>
                                <div class="small text-muted ps-5">เหตุผล: ${h.reason}</div>
                            </div>
                        `);
                    });
                } else if (data.type === "user") {
                    const user = data.user;
                    $("#historyItemHeader").html(`
                        <img src="../assets/images/${user.image}" class="rounded-circle border shadow-sm" width="50" height="50" style="object-fit: cover;" onerror="this.src=\'../assets/images/default_user.png\'">
                        <div>
                            <div class="fw-bold text-success">ประวัติของ: ${user.firstname} ${user.lastname}</div>
                            <div class="text-muted small">@${user.username} (ค้นพบจากรายชื่อผู้เบิก)</div>
                        </div>
                    `);

                    data.history.forEach(h => {
                        $("#historyTimeline").append(`
                            <div class="list-group-item px-0 py-3 border-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="../assets/images/${h.product_image}" class="rounded border" width="30" height="30" style="object-fit: cover;" onerror="this.src=\'../assets/images/default_product.png\'">
                                        <span class="fw-bold small">${h.product_name}</span>
                                    </div>
                                    <span class="badge bg-light text-dark fw-normal" style="font-size: 10px;">${h.borrow_date}</span>
                                </div>
                                <div class="small text-muted ps-5">${h.brand} ${h.model} | เหตุผล: ${h.reason}</div>
                            </div>
                        `);
                    });
                }

                if (data.history.length === 0) {
                    $("#historyTimeline").append(\'<div class="text-center py-4 text-muted">ยังไม่เคยมีประวัติการเบิก</div>\');
                }

                $("#itemHistoryCard").show();
                $("html, body").animate({
                    scrollTop: $("#itemHistoryCard").offset().top - 100
                }, 500);
            }
        });
    }

    function prefillSearch() {
        const val = document.getElementById("asset_number").value;
        if (val) {
            $("#rightSearchInput").val(val).trigger("input");
        }
    }

    function formatAssetNumber(input) {
        let val = input.value.replace(/[^0-9-]/g, "");
        if (val.length > 4 && val[4] !== "-") val = val.slice(0, 4) + "-" + val.slice(4);
        if (val.length > 8 && val[8] !== "-") val = val.slice(0, 8) + "-" + val.slice(8);
        input.value = val;
    }
</script>
<?php require_once \'../includes/footer.php\'; ?>';

file_put_contents($file, $head . $cleanJS);
echo "Success";
?>
