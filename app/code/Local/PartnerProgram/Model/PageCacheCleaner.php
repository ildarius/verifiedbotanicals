<?php
declare(strict_types=1);

namespace Local\PartnerProgram\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;

class PageCacheCleaner
{
    public function __construct(
        private readonly EventManager $eventManager,
        private readonly CacheInterface $cache,
        private readonly PageIdentity $pageIdentity
    ) {
    }

    public function clean(): void
    {
        $this->cache->clean([PartnerProgramData::CACHE_TAG]);
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->pageIdentity]);
    }
}
