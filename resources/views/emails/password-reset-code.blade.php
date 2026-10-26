<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif;">
    <h2>Password Reset Code</h2>
    <p>Use this code to reset your password:</p>
    <p style="font-size: 28px; letter-spacing: 6px; font-weight: bold;">{{ $code }}</p>
    <p>This code expires in {{ $minutes }} minutes. If you didn't request it, ignore this email.</p>
</body>
</html>