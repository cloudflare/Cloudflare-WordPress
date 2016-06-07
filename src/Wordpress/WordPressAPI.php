<?php

namespace CF\WordPress;

use CF\Integration\IntegrationAPIInterface;
use CF\DNSRecord;

class WordPressAPI implements IntegrationAPIInterface
{
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
     * @param null $userId
     *
     * @return mixed
     */
    public function getDomainList($userId = null)
    {
        return array($_SERVER['SERVER_NAME']);
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->dataStore->getCloudFlareEmail();
    }
}
