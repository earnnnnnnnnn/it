<?php
$file = 'c:/xampp/htdocs/it/includes/header.php';
$content = file_get_contents($file);
$logic = '
            // Sidebar Accordion Persistence
            const collapseSettings = $("#collapseSettings");
            const toggleSettings = $("a[href=\'#collapseSettings\']");
            const isSettingsPage = ' . json_encode(basename($_SERVER["PHP_SELF"]) == "settings.php") . ';
            const wasExpanded = localStorage.getItem("sidebarSettingsExpanded") === "true";

            if (wasExpanded) {
                collapseSettings.addClass("show");
                toggleSettings.attr("aria-expanded", "true");
            }

            collapseSettings.on("shown.bs.collapse", function () {
                localStorage.setItem("sidebarSettingsExpanded", "true");
            });
            collapseSettings.on("hidden.bs.collapse", function () {
                localStorage.setItem("sidebarSettingsExpanded", "false");
            });
        });
        </script>';

// Search for the end of document ready
$target = "        });\n        </script>";
if (strpos($content, $target) === false) {
    $target = "        });\r\n        </script>";
}

if (strpos($content, 'sidebarSettingsExpanded') === false) {
    $content = str_replace($target, $logic, $content);
    file_put_contents($file, $content);
    echo "Success";
} else {
    echo "Already exists";
}
?>
