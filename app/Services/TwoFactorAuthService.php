<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorAuthService
{
    private const RECOVERY_CODES_COUNT = 8;
    private const RECOVERY_CODE_LENGTH = 10;
    private const QR_SIZE = 300;

    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function generateQrCode(string $email, string $secret): string
    {
        $company = config('app.name', 'Laravel');
        $qrCodeUrl = $this->google2fa->getQRCodeUrl($company, $email, $secret);

        $renderer = new ImageRenderer(
            new RendererStyle(self::QR_SIZE),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrCode = $writer->writeString($qrCodeUrl);

        return 'data:image/svg+xml;base64,' . base64_encode($qrCode);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = bin2hex(random_bytes(10));
        }
        return $codes;
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (!$user->recovery_codes) {
            return false;
        }

        $recoveryCodes = json_decode($user->recovery_codes, true);
        $position = array_search($code, $recoveryCodes);

        if ($position === false) {
            return false;
        }

        // Remove used recovery code
        unset($recoveryCodes[$position]);
        $user->recovery_codes = json_encode(array_values($recoveryCodes));
        $user->save();

        return true;
    }

    public function enable2FA(User $user): array
    {
        $secret = $this->generateSecretKey();
        $qrCode = $this->generateQrCode($user->email, $secret);
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->two_factor_enabled = true;
        $user->save();

        return [
            'secret' => $secret,
            'qr_code' => $qrCode,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Disable 2FA for the user.
     */
    public function disable2FA(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_enabled = false;
        $user->two_factor_confirmed_at = null;
        $user->save();
    }

    /**
     * Check if 2FA is enabled for the user.
     */
    public function isEnabled(User $user): bool
    {
        return $user->two_factor_enabled && $user->two_factor_confirmed_at !== null;
    }
}
