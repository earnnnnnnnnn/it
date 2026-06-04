<?php
$file = 'c:/xampp/htdocs/it/pages/settings.php';
$content = file_get_contents($file);
$logic = '
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get("tab");
        if (tab) {
            let target = "";
            if (tab === "products") target = "#tabProducts";
            else if (tab === "users") target = "#tabUsers";
            else if (tab === "borrow") target = "#tabBorrowReasons";
            else if (tab === "import") target = "#tabImportReasons";
            
            if (target) {
                const triggerEl = document.querySelector("button[data-bs-target=\'" + target + "\']");
                if (triggerEl) {
                    const tabTrigger = new bootstrap.Tab(triggerEl);
                    tabTrigger.show();
                }
            }
        }
    });
</script>';
if (strpos($content, 'urlParams.get("tab")') === false) {
    $content = str_replace('</script>', $logic, $content);
    file_put_contents($file, $content);
    echo "Success";
} else {
    echo "Already exists";
}
?>
