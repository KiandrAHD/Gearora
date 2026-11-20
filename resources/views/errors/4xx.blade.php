@php
    // Fallback untuk seluruh 4xx yang belum punya view spesifik (403, 419, 429, dst.).
    // Status HTTP tetap benar; hanya tampilan yang diseragamkan.
    $code = $code ?? ($exception?->getStatusCode() ?? 400);
    $title = $title ?? 'Permintaan ditolak';
    $message = $message ?? 'Permintaan ini tidak dapat diproses. Periksa kembali akses atau kembali ke halaman sebelumnya.';
@endphp
@include('errors.minimal')
