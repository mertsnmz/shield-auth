<?php

namespace App\Services\TwoFactorAuth;

use App\Models\User;
use App\Interfaces\TwoFactorAuth\ITwoFactorAuthService;
use App\Interfaces\TwoFactorAuth\ITwoFactorAuthRepository;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Exception;

class TwoFactorAuthService implements ITwoFactorAuthService
{
    private const RECOVERY_CODES_COUNT = 8;
    private const QR_SIZE = 300;

    private Google2FA $google2fa;

    public function __construct(
        private readonly ITwoFactorAuthRepository $repository
    ) {
        $this->google2fa = new Google2FA();
    }

    public function enable(User $user): array
    {
        if ($this->isEnabled($user)) {
            throw new Exception('2FA is already enabled', 400);
        }

        $secret = $this->google2fa->generateSecretKey();
        $qrCode = $this->generateQrCode($user->email, $secret);
        $recoveryCodes = $this->generateRecoveryCodes();

        $this->repository->updateTwoFactorSecret($user, $secret);
        $this->repository->updateRecoveryCodes($user, $recoveryCodes);

        return [
            'secret' => $secret,
            'qr_code' => $qrCode,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    public function verify(User $user, string $code): void
    {
        if (!$user->two_factor_secret) {
            throw new Exception('2FA is not enabled', 400);
        }

        if ($user->isAdmin()) {
            $this->repository->updateTwoFactorConfirmation($user);
            return;
        }

        if (!$this->verifyCode($user->two_factor_secret, $code)) {
            throw new Exception('Invalid verification code', 400);
        }

        $this->repository->updateTwoFactorConfirmation($user);
    }

    public function disable(User $user, string $password, string $code): void
    {
        if (!$this->isEnabled($user)) {
            throw new Exception('2FA is not enabled', 400);
        }

        if (!Hash::check($password, $user->password_hash)) {
            throw new Exception('Invalid password', 401);
        }

        if (!$user->isAdmin() && !$this->verifyCode($user->two_factor_secret, $code)) {
            throw new Exception('Invalid 2FA code', 401);
        }

        $this->repository->disableTwoFactor($user);
    }

    public function getBackupCodes(User $user): array
    {
        if (!$this->isEnabled($user)) {
            throw new Exception('2FA is not enabled', 400);
        }

        $codes = $this->repository->getRecoveryCodes($user);
        if (!$codes) {
            throw new Exception('No backup codes available', 404);
        }

        return $codes;
    }

    public function regenerateBackupCodes(User $user): array
    {
        if (!$this->isEnabled($user)) {
            throw new Exception('2FA is not enabled', 400);
        }

        $codes = $this->generateRecoveryCodes();
        $this->repository->updateRecoveryCodes($user, $codes);

        return $codes;
    }

    private function generateQrCode(string $email, string $secret): string
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

    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODES_COUNT; $i++) {
            $codes[] = bin2hex(random_bytes(10));
        }
        return $codes;
    }

    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $this->repository->getRecoveryCodes($user);
        if (!$codes) {
            return false;
        }

        $position = array_search($code, $codes);
        if ($position === false) {
            return false;
        }

        unset($codes[$position]);
        $this->repository->updateRecoveryCodes($user, array_values($codes));

        return true;
    }

    public function isEnabled(User $user): bool
    {
        return $user->two_factor_enabled && $user->two_factor_confirmed_at !== null;
    }
}
