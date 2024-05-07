<?php
declare(strict_types=1);

namespace Shoptet\Catalog\Parameter;

class MassCodeIdsActionParameter
{
    private bool $multipage;
    /** @var int[]  */
    private array $massCodeId;

    /**
     * @param int[] $massCodeId
     */
    public function __construct(bool $multipage = false, array $massCodeId = [])
    {
        $this->multipage = $multipage;
        $this->massCodeId = $massCodeId;
    }

    public function isMultipage(): bool
    {
        return $this->multipage;
    }

    /**
     * @return int[]
     */
    public function getMassCodeId(): array
    {
        return $this->massCodeId;
    }
}
