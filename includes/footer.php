    </div> <!-- End Main Content -->

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Global logic for barcode scanning
        let barcodeBuffer = "";
        let lastKeyTime = Date.now();

        $(document).on('keypress', function(e) {
            // Ignore if typing in an input, textarea or select
            if ($(e.target).is('input, textarea, select, .ts-control input')) {
                return;
            }

            const currentTime = Date.now();
            
            // Check if keyboard is in Thai (Common issue in TH)
            if (e.key.match(/[ก-ฮะ-์]/)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'สแกนผิดพลาด',
                    text: 'กรุณาเปลี่ยนภาษาคีย์บอร์ดเป็นภาษาอังกฤษก่อนสแกน',
                    timer: 2000,
                    showConfirmButton: false
                });
                barcodeBuffer = "";
                return;
            }

            if (currentTime - lastKeyTime > 100) {
                barcodeBuffer = "";
            }

            if (e.key === 'Enter') {
                if (barcodeBuffer.length > 2) {
                    // Trigger custom event for pages to listen to
                    $(document).trigger('barcodeScanned', [barcodeBuffer]);
                    barcodeBuffer = "";
                }
            } else {
                barcodeBuffer += e.key;
            }
            lastKeyTime = currentTime;
        });
    </script>
</body>
</html>
