<?php
declare(strict_types=1);

namespace Local\PartnerProgram\Plugin;

use Local\PartnerProgram\Model\PageCacheCleaner;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Mirasvit\Affiliate\Repository\ProgramRepository;

class CleanPageCacheOnProgramSave
{
    public function __construct(private readonly PageCacheCleaner $cleaner)
    {
    }

    public function afterSave(ProgramRepository $subject, ProgramInterface $result): ProgramInterface
    {
        $this->cleaner->clean();

        return $result;
    }
}
