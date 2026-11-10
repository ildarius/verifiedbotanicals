<?php
declare(strict_types=1);

namespace Local\PolyShellGuard\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\TwoFactorAuth\Api\TfaSessionInterface;
use Magento\TwoFactorAuth\Model\TfaSession;

/**
 * Magento keeps the "2FA passed" flag (2fa_passed) in the PHP session and never clears it at login.
 * If a browser's admin session passed 2FA and then ended without a sign-out (timed out, or its user
 * was deleted or its session terminated), the next password login in that browser skipped 2FA, even
 * as a different admin user. Clearing the flag on every successful password login closes that gap:
 * Magento's 2FA check then asks for a code again before any admin page.
 */
class ResetTwoFactorGrantOnLogin implements ObserverInterface
{
    public function __construct(private readonly TfaSession $tfaSession)
    {
    }

    public function execute(Observer $observer): void
    {
        $this->tfaSession->unsetData(TfaSessionInterface::KEY_PASSED);
        $this->tfaSession->unsetData(TfaSession::SKIPPED_PROVIDERS_KEY);
    }
}
