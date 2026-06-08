<?php

declare(strict_types=1);

namespace Local\InteracETransfer\Block\Info;

use Local\InteracETransfer\Service\InstructionDataBuilder;
use Magento\Payment\Block\Info;

class InteracETransfer extends Info
{
    protected $_template = 'Local_InteracETransfer::info/interac-etransfer.phtml';

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        private readonly InstructionDataBuilder $instructionDataBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array<string,string|int>
     */
    public function getInstructionData(): array
    {
        return $this->instructionDataBuilder->build($this->getInfo()->getOrder());
    }
}
