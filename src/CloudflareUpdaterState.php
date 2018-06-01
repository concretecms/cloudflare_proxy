<?php

namespace Concrete5\Cloudflare;

final class CloudflareUpdaterState
{
    /**
     * @var string[]
     */
    protected $oldIPs = [];

    /**
     * @var string[]
     */
    protected $newIPs = [];

    /**
     * @param string[] $oldIPs
     * @param string[] $newIPs
     */
    public function __construct(array $oldIPs, array $newIPs)
    {
        $this->oldIPs = $oldIPs;
        $this->newIPs = $newIPs;
    }

    /**
     * @return string[]
     */
    public function getOldIPs()
    {
        return $this->oldIPs;
    }

    /**
     * @return string[]
     */
    public function getNewIPs()
    {
        return $this->newIPs;
    }

    /**
     * @return string[]
     */
    public function getAddedIPs()
    {
        return array_values(array_diff($this->newIPs, $this->oldIPs));
    }

    /**
     * @return string[]
     */
    public function getRemovedIPs()
    {
        return array_values(array_diff($this->oldIPs, $this->newIPs));
    }
}
