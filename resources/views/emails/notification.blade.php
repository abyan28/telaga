<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#f1f5f9; padding:24px; color:#1e293b;">
    {{-- Template email notifikasi TELAGA AL KAUTSAR (PRD §7.13) --}}
    <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:16px; overflow:hidden; border:1px solid #e2e8f0;">
        <div style="background:#0284c7; color:#fff; padding:20px 24px; font-weight:bold; font-size:18px;">
            TELAGA AL KAUTSAR
        </div>
        <div style="padding:24px;">
            <h2 style="margin:0 0 12px; font-size:16px; color:#0f172a;">{{ $judul }}</h2>
            <p style="margin:0; font-size:14px; line-height:1.6; color:#475569;">{{ $pesan }}</p>
        </div>
        <div style="padding:16px 24px; background:#f8fafc; font-size:11px; color:#94a3b8; border-top:1px solid #e2e8f0;">
            Email ini dikirim otomatis oleh sistem TELAGA AL KAUTSAR. Mohon tidak membalas email ini.
        </div>
    </div>
</body>
</html>
