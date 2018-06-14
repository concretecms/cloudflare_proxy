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
     * @return string[]
     */
    public function getCloudfareEndpoints()
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
     * @param string|\IPLib\Address\AddressInterface|\IPLib\Range\RangeInterface|mixed $ip
     *
     * @throws \Exception
     *
     * @return string
     */
    public function normalizeIP($ip)
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

    /**
     * Get the currently configured IPs.
     *
     * @return string
     */
    public function getConfiguredIPs()
    {
        $result = $this->config->get('concrete.security.trusted_proxies.ips');

        return is_array($result) ? $result : [];
    }

    /**
     * Set the currently configured IPs.
     *
     * @param string[] $ips
     * @param array $newIPs
     */
    public function setConfiguredIPs(array $newIPs)
    {
        $this->config->set('concrete.security.trusted_proxies.ips', $newIPs);
        $this->config->save('concrete.security.trusted_proxies.ips', $newIPs);
    }

    /**
     * Get the list of custom IPs.
     *
     * @return string[]
     */
    public function getCustomIPs()
    {
        $result = [];
        $custom = $this->config->get('cloudflare_proxy::ips.custom');
        if (is_array($custom)) {
            foreach ($custom as $ip) {
                $ip = (string) $ip;
                if ($ip !== '') {
                    $result[] = $this->normalizeIP($ip);
                }
            }
        }

        return $result;
    }

    /**
     * Get the IPs from a url service.
     *
     * @param string[] $urls
     *
     * @return string[]
     */
    public function getCloudflareIPs(array $urls)
    {
        $ips = [];
        foreach ($urls as $url) {
            $url = (string) $url;
            if ($url !== '') {
                if (class_exists(Client::class)) {
                    $client = $this->app->make(Client::class);
                    $client->setUri($url);
                    $response = $client->send();
                    if (!$response->isOk()) {
                        throw new Exception(t('Failed to fetch IPs from %s: %s', $url, $response->getStatusCode()));
                    }
                    $contents = $response->getBody();
                } else {
                    $contents = file_get_contents($url);
                    if ($contents === false) {
                        throw new Exception(t('Failed to fetch IPs from %s.', $url));
                    }
                }
                if (!$contents) {
                    throw new Exception(t('Empty data fetched from %s', $url));
                }
                $matches = null;
                if (!preg_match_all('/\S+/', $contents, $matches)) {
                    throw new Exception(t('No IPs fetched from %s', $url));
                }
                foreach ($matches[0] as $ip) {
                    $ips[] = $this->normalizeIP($ip);
                }
            }
        }

        return $ips;
    }
}
