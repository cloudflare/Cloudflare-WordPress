<?php

namespace CF\WordPress;

use CF\Integration\IntegrationAPIInterface;
use CF\DNSRecord;

class WordPressAPI implements IntegrationAPIInterface
{
    const API_NONCE = 'cloudflare-db-api-nonce';

    private $dataStore;

    /**
     * @param $dataStore
     */
    public function __construct(DataStore $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    /**
     * @param $domain_name
     *
     * @return mixed
     */
    public function getDNSRecords($domain_name)
    {
        return;
    }

    /**
     * @param $domain_name
     * @param DNSRecord $DNSRecord
     *
     * @return mixed
     */
    public function addDNSRecord($domain_name, DNSRecord $DNSRecord)
    {
        return;
    }

    /**
     * @param $domain_name
     * @param DNSRecord $DNSRecord
     *
     * @return mixed
     */
    public function editDNSRecord($domain_name, DNSRecord $DNSRecord)
    {
        return;
    }

    /**
     * @param $domain_name
     * @param DNSRecord $DNSRecord
     *
     * @return mixed
     */
    public function removeDNSRecord($domain_name, DNSRecord $DNSRecord)
    {
        return;
    }

    /**
     * @return mixed
     */
    public function getHostAPIKey()
    {
        return;
    }

    /**
     * @param domain name
     *
     * @return string
     */
    private function formatDomain($domainName)
    {
        // Remove instances which are before the domain name:
        // * http
        // * https
        // * www
        // * user:pass@
        preg_match_all('/^(?:https?:\/\/)?(?:[^@\/\n]+@)?(?:www\.)?([^:\/\n]+)/im', $domainName, $matches);
        $formattedDomain = $matches[1][0];

        return $formattedDomain;
    }

    /**
     * @param null $userId
     *
     * @return mixed
     */
    public function getDomainList($userId = null)
    {
        $domainName = $_SERVER['SERVER_NAME'];

        return array($this->formatDomain($domainName));
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->dataStore->getCloudFlareEmail();
    }
}
