<?php
declare(strict_types=1);

namespace Local\PartnerProgram\Observer;

use Local\PartnerProgram\Model\PageCacheCleaner;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CleanPartnerPageCache implements ObserverInterface
{
    public function __construct(private readonly PageCacheCleaner $cleaner)
    {
    }

    public function execute(Observer $observer): void
    {
        $this->cleaner->clean();
    }
}
