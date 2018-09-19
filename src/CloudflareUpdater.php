<?php

namespace Concrete5\Cloudflare;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Http\Client\Client;
use Exception;
use IPLib\Address\AddressInterface;
use IPLib\Factory;
use IPLib\Range\Pattern;
use IPLib\Range\RangeInterface;

final class CloudflareUpdater
{
    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    private $config;

    /**
     * @var \Concrete\Core\Application\Application
     */
    private $app;

    /**
     * @param \Concrete\Core\Config\Repository\Repository $config
     * @param \Concrete\Core\Application\Application $app
     */
    public function __construct(Repository $config, Application $app)
    {
        $this->config = $config;
        $this->app = $app;
    }

    /**
     * Fetch the Cloudflare IPs from the Cloudflare endpoints.
     *
     * @return string[]
     */
    public function fetchNewCloudflareIPs()
    {
        $result = [];
        foreach ($this->getCloudflareEndpoints() as $cloudflareEndpoint) {
            $result = array_merge($result, $this->fetchNewCloudflareIPFromEndpoint($cloudflareEndpoint));
        }

        return array_values(array_unique($result));
    }

    /**
     * Get the whole list of trusted IPs.
     *
     * @return string[]
     */
    public function getTrustedIPs()
    {
        return $this->normalizeIPList($this->config->get('concrete.security.trusted_proxies.ips'));
    }

    /**
     * Set the whole list of trusted IPs.
     *
     * @param string[] $ips
     *
     * @return $this
     */
    public function setTrustedIPs(array $ips)
    {
        $this->config->set('concrete.security.trusted_proxies.ips', $ips);
        $this->config->save('concrete.security.trusted_proxies.ips', $ips);

        return $this;
    }

    /**
     * Get the previously fetched Cloudflare IPs.
     *
     * @return string[]
     */
    public function getPreviousCloudflareIPs()
    {
        return $this->normalizeIPList($this->config->get('cloudflare_proxy::ips.cloudflare.previous'));
    }

    /**
     * Get the previously fetched Cloudflare IPs.
     *
     * @param string[]|mixed
     * @param mixed $ips
     *
     * @return $this
     */
    public function setPreviousCloudflareIPs($ips)
    {
        $ips = $this->normalizeIPList($ips);
        $this->config->set('cloudflare_proxy::ips.cloudflare.previous', $ips);
        $this->config->save('cloudflare_proxy::ips.cloudflare.previous', $ips);

        return $this;
    }

    /**
     * Get the IPs from a Cloudflare endpoint.
     *
     * @param string $cloudflareEndpoint
     *
     * @return string[]
     */
    public function fetchNewCloudflareIPFromEndpoint($cloudflareEndpoint)
    {
        if (class_exists(Client::class)) {
            $client = $this->app->make(Client::class);
            $client->setUri($cloudflareEndpoint);
            $response = $client->send();
            if (!$response->isOk()) {
                throw new Exception(t('Failed to fetch IPs from %s: %s', $cloudflareEndpoint, $response->getStatusCode()));
            }
            $contents = $response->getBody();
        } else {
            $contents = file_get_contents($cloudflareEndpoint);
            if ($contents === false) {
                throw new Exception(t('Failed to fetch IPs from %s.', $cloudflareEndpoint));
            }
        }
        $result = [];
        $contents = trim($contents);
        if ($contents !== '') {
            $matches = null;
            if (preg_match_all('/\S+/', $contents, $matches)) {
                $result = $this->normalizeIPList($matches[0], true);
            }
        }
        if ($result === []) {
            throw new Exception(t('No IPs fetched from %s.', $cloudflareEndpoint));
        }

        return $result;
    }

    /**
     * Get the list of URLs providing the list of Cloudflare IPs.
     *
     * @return string[]
     */
    public function getCloudflareEndpoints()
    {
        $result = [];
        $endpoints = $this->config->get('cloudflare_proxy::endpoints');
        if (is_array($endpoints)) {
            foreach ($endpoints as $endpoint) {
                if (is_string($endpoint) && $endpoint !== '') {
                    $result[] = $endpoint;
                }
            }
        }

        return $result;
    }

    /**
     * Normalize a list of IP addresses.
     *
     * @param string[]|mixed $ips
     * @param bool $throw throw exceptions in case of errors
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function normalizeIPList($ips, $throw = false)
    {
        $result = [];
        if (is_array($ips)) {
            foreach ($ips as $ip) {
                if ($throw) {
                    $ip = $this->normalizeIP($ip);
                } else {
                    try {
                        $ip = $this->normalizeIP($ip);
                    } catch (Exception $x) {
                        continue;
                    }
                }
                if (!in_array($ip, $result, true)) {
                    $result[] = $ip;
                }
            }
        }

        return $result;
    }

    /**
     * Normalize an IP address.
     *
     * @param string|\IPLib\Address\AddressInterface|\IPLib\Range\RangeInterface|mixed $ip
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function normalizeIP($ip)
    {
        if ($ip instanceof AddressInterface) {
            $result = (string) $ip;
        } elseif ($ip instanceof RangeInterface) {
            if ($ip instanceof Pattern) {
                throw new Exception(t('Pattern IP ranges are not supported.'));
            }
            $result = (string) $ip;
        } elseif (is_string($ip) || (is_object($ip) && is_callable([$ip, '__toString']))) {
            $range = Factory::rangeFromString((string) $ip);
            if ($range === null) {
                throw new Exception(t('%s is not a valid IP range', (string) $ip));
            }
            if ($range instanceof Pattern) {
                throw new Exception(t('Pattern IP ranges are not supported.'));
            }
            $result = (string) $range;
        } else {
            throw new Exception(t('Invalid IP address specified.'));
        }

        return $result;
    }
}
