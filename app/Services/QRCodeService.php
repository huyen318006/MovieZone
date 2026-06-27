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
     * Tạo QR content cho một ticket.
     *
     * @param string $ticketCode VD: TK-20260627-001
     * @return string VD: MZ|TK-20260627-001|a1b2c3d4e5f6
     */
    public function generateQRContent(string $ticketCode): string
    {
        $checksum = $this->computeChecksum($ticketCode);
        return "MZ|{$ticketCode}|{$checksum}";
    }

    /**
     * Validate QR content và trả về ticket_code nếu hợp lệ.
     *
     * @param string $qrContent Nội dung QR đã decode
     * @return array{valid: bool, ticket_code: ?string, error: ?string}
     */
    public function validateQR(string $qrContent): array
    {
        $qrContent = trim($qrContent);

        // Parse format: MZ|ticket_code|checksum
        $parts = explode('|', $qrContent);

        if (count($parts) !== 3) {
            return [
                'valid' => false,
                'ticket_code' => null,
                'error' => 'INVALID_QR_FORMAT',
            ];
        }

        [$prefix, $ticketCode, $checksum] = $parts;

        // Check prefix
        if ($prefix !== 'MZ') {
            return [
                'valid' => false,
                'ticket_code' => null,
                'error' => 'INVALID_QR_FORMAT',
            ];
        }

        // Check ticket code format
        if (!preg_match('/^TK-\d{8}-\d{3,}$/', $ticketCode)) {
            return [
                'valid' => false,
                'ticket_code' => null,
                'error' => 'INVALID_QR_FORMAT',
            ];
        }

        // Verify HMAC checksum (timing-safe)
        $expectedChecksum = $this->computeChecksum($ticketCode);
        if (!hash_equals($expectedChecksum, $checksum)) {
            return [
                'valid' => false,
                'ticket_code' => null,
                'error' => 'QR_TAMPERED',
            ];
        }

        return [
            'valid' => true,
            'ticket_code' => $ticketCode,
            'error' => null,
        ];
    }

    /**
     * Tính HMAC-SHA256 checksum (12 ký tự đầu).
     */
    private function computeChecksum(string $ticketCode): string
    {
        return substr(hash_hmac('sha256', $ticketCode, $this->secret), 0, 12);
    }
}
