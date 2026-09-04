<?php
/**
 * Google reCAPTCHA v2 verification helper
 * วางไฟล์นี้ที่ app/helpers/recaptcha.php
 * แล้วเรียกใช้ผ่านฟังก์ชัน verifyRecaptcha() ใน register.php
 */

// TODO: แก้เป็น Secret Key ของคุณ (ได้จาก https://www.google.com/recaptcha/admin)
// แนะนำให้เก็บใน .env หรือ config file แทนการ hardcode ตรงนี้
if (!defined('RECAPTCHA_SECRET_KEY')) {
    define('RECAPTCHA_SECRET_KEY', 'YOUR_SECRET_KEY_HERE');
}

/**
 * ตรวจสอบ token ที่ได้จาก g-recaptcha-response กับ Google API
 *
 * @param string|null $token ค่าที่ได้จาก $_POST['g-recaptcha-response']
 * @return bool true ถ้าผ่าน, false ถ้าไม่ผ่าน/ไม่ได้ติ๊ก/token ปลอม
 */
function verifyRecaptcha(?string $token): bool
{
    if (empty($token)) {
        return false;
    }

    $payload = http_build_query([
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('reCAPTCHA verify request failed: ' . $curlErr);
        return false;
    }

    $result = json_decode($response, true);
    return !empty($result['success']);
}
