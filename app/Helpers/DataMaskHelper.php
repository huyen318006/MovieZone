<?php

namespace App\Helpers;

/**
 * S1-08: Data Masking Helper
 *
 * Mask thông tin nhạy cảm của khách hàng (BR03).
 * Staff chỉ nhìn thấy thông tin ở mức cần thiết.
 *
 * VD:
 *   maskPhone('0912345678')    → '091***5678'
 *   maskEmail('khach@gmail.com') → 'kha***@gmail.com'
 */
class DataMaskHelper
{
    /**
     * Mask số điện thoại: giữ 3 ký tự đầu + 4 ký tự cuối.
     * VD: 0912345678 → 091***5678
     */
    public static function maskPhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $length = mb_strlen($phone);
        if ($length <= 7) {
            // SĐT quá ngắn, mask phần giữa
            return mb_substr($phone, 0, 2) . '***' . mb_substr($phone, -2);
        }

        return mb_substr($phone, 0, 3) . '***' . mb_substr($phone, -4);
    }

    /**
     * Mask email: giữ 3 ký tự đầu trước @ + domain đầy đủ.
     * VD: khach@gmail.com → kha***@gmail.com
     */
    public static function maskEmail(?string $email): ?string
    {
        if (empty($email)) {
            return null;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***';
        }

        $local = $parts[0];
        $domain = $parts[1];

        $localLength = mb_strlen($local);
        if ($localLength <= 3) {
            $maskedLocal = mb_substr($local, 0, 1) . '***';
        } else {
            $maskedLocal = mb_substr($local, 0, 3) . '***';
        }

        return $maskedLocal . '@' . $domain;
    }

    /**
     * Mask tên: giữ nguyên (không mask theo yêu cầu SRS §5.5 Section 1).
     */
    public static function maskName(?string $name): ?string
    {
        return $name;
    }
}
