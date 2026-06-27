<?php

namespace App\Services;

/**
 * UC-STAFF-01: QR Code Service.
 *
 * Tạo và validate QR content cho vé.
 * Format: MZ|{ticket_code}|{checksum_12_chars}
 *
 * Checksum = 12 ký tự đầu của HMAC-SHA256(ticket_code, secret).
 * Secret lưu trong .env (QR_SECRET).
 */
class QRCodeService
{
    private string $secret;

    public function __construct()
    {
        $this->secret = config('app.qr_secret', env('QR_SECRET', 'moviezone-default-qr-secret-key'));
    }

    /**
     * Tạo QR content cho ticket hoặc booking.
     *
     * @param string $code VD: TK-20260627-001 hoặc BK...
     * @return string VD: MZ|TK-20260627-001|a1b2c3d4e5f6
     */
    public function generateQRContent(string $code): string
    {
        $checksum = $this->computeChecksum($code);
        return "MZ|{$code}|{$checksum}";
    }

    /**
     * Validate QR content và trả về code nếu hợp lệ.
     *
     * @param string $qrContent Nội dung QR đã decode
     * @return array{valid: bool, type: ?string, code: ?string, error: ?string}
     */
    public function validateQR(string $qrContent): array
    {
        $qrContent = trim($qrContent);

        // Parse format: MZ|code|checksum
        $parts = explode('|', $qrContent);

        if (count($parts) !== 3) {
            return [
                'valid' => false,
                'type' => null,
                'code' => null,
                'error' => 'INVALID_QR_FORMAT',
            ];
        }

        [$prefix, $code, $checksum] = $parts;

        // Check prefix
        if ($prefix !== 'MZ') {
            return [
                'valid' => false,
                'type' => null,
                'code' => null,
                'error' => 'INVALID_QR_FORMAT',
            ];
        }

        // Check code format and determine type
        $type = null;
        if (preg_match('/^TK-\d{8}-\d{3,}$/', $code)) {
            $type = 'ticket';
        } elseif (preg_match('/^BK[A-Z0-9]+$/', $code)) {
            $type = 'booking';
        } else {
            return [
                'valid' => false,
                'type' => null,
                'code' => null,
                'error' => 'INVALID_QR_FORMAT',
            ];
        }

        // Verify HMAC checksum (timing-safe)
        $expectedChecksum = $this->computeChecksum($code);
        if (!hash_equals($expectedChecksum, $checksum)) {
            return [
                'valid' => false,
                'type' => null,
                'code' => null,
                'error' => 'QR_TAMPERED',
            ];
        }

        return [
            'valid' => true,
            'type' => $type,
            'code' => $code,
            'error' => null,
        ];
    }

    /**
     * Tính HMAC-SHA256 checksum (12 ký tự đầu).
     */
    private function computeChecksum(string $code): string
    {
        return substr(hash_hmac('sha256', $code, $this->secret), 0, 12);
    }
}
