<?php
$file = 'c:/xampp/htdocs/it/pages/borrow.php';
$content = file_get_contents($file);

$mapScript = '
        // Thai to English mapping for common barcode characters
        const thaiToEngMap = {
            "ๆ":"q","ไ":"w","ำ":"e","พ":"r","ะ":"t","ั":"y","ี":"u","ร":"i","น":"o","ย":"p","บ":"[","ล":"]",
            "ฟ":"a","ห":"s","ก":"d","ด":"f","เ":"g","้":"h","่":"j","า":"k","ส":"l","ว":";","ง":"\'",
            "ผ":"z","ป":"x","แ":"c","อ":"v","ิ":"b","ื":"n","ท":"m","ม":",","ใ":".","ฝ":"/",
            "๐":"0","๑":"1","๒":"2","๓":"3","๔":"4","๕":"5","๖":"6","๗":"7","๘":"8","๙":"9",
            "๘":"*","๙":"(","๐":")","จ":"0","ข":"-","ช":"="
        };

        $("#scanInput").on("input", function() {
            let val = $(this).val();
            let newVal = "";
            for (let i = 0; i < val.length; i++) {
                let char = val[i];
                newVal += thaiToEngMap[char] || char;
            }
            if (val !== newVal) {
                $(this).val(newVal);
            }
        });
';

// Insert before the existing scanInput logic
$content = str_replace('$("#scanInput").on("input", function() {', $mapScript . '        $("#scanInput").on("input", function() {', $content);

file_put_contents($file, $content);
echo "Success";
?>
