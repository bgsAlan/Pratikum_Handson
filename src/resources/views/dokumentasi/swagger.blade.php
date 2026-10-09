<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Dokumentasi API — Barokah Mart</title>

    {{-- Versi dikunci agar tampilan sama di mesin seluruh anggota tim. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.32.15/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.32.15/swagger-ui-bundle.js"></script>
    <script>
        window.addEventListener('load', function () {
            window.ui = SwaggerUIBundle({
                url: @json(route('docs.spesifikasi')),    // kontrak yang dibaca
                dom_id: '#swagger-ui',
                deepLinking: true,               // URL berubah per endpoint, bisa dibagikan
                docExpansion: 'list',            // tampilkan daftar endpoint, bukan isinya
                filter: true,                  // kotak pencarian endpoint
                displayRequestDuration: true,    // durasi tampil setelah "Execute"
                persistAuthorization: false,     // kunci API tidak disimpan di peramban
            });
        });
    </script>
</body>
</html>