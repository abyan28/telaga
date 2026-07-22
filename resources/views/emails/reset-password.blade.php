<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;padding:40px 20px;">
<div style="max-width:440px;margin:0 auto;background:#fff;border-radius:16px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
    <h2 style="color:#0f172a;font-size:18px;margin:0 0 8px;">Atur Ulang Kata Sandi</h2>
    <p style="color:#64748b;font-size:14px;margin:0 0 24px;line-height:1.6;">Anda menerima email ini karena ada permintaan atur ulang kata sandi untuk akun Anda di TELAGA AL KAUTSAR. Klik tombol di bawah untuk membuat kata sandi baru. Link berlaku 60 menit.</p>
    <div style="text-align:center;margin:0 0 24px;">
        <a href="{{ url('reset-password/'.$token.'?email='.urlencode($email)) }}" style="display:inline-block;background:#0284c7;color:#fff;font-weight:700;font-size:14px;text-decoration:none;padding:14px 28px;border-radius:12px;">Atur Ulang Kata Sandi</a>
    </div>
    <p style="color:#94a3b8;font-size:12px;margin:0;line-height:1.6;">Abaikan email ini jika Anda tidak meminta atur ulang kata sandi. Kata sandi Anda tidak akan berubah.</p>
</div>
</body>
</html>
