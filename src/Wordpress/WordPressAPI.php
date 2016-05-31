<?php
namespace CF\WordPress;

class WordPressAPI implements IntegrationAPIInterface 
{
    /**
     * @param $domain_name
     * @return mixed
     */
    public function getDNSRecords($domain_name) { return null }

    /**
     * @param $domain_name
     * @param DNSRecord $DNSRecord
     * @return mixed
     */
    public function addDNSRecord($domain_name, DNSRecord $DNSRecord) { return null }

    /**
     * @param $domain_name
     * @param DNSRecord $DNSRecord
     * @return mixed
     */
    public function editDNSRecord($domain_name, DNSRecord $DNSRecord) { return null }

    /**
     * @param $domain_name
     * @param DNSRecord $DNSRecord
     * @return mixed
     */
    public function removeDNSRecord($domain_name, DNSRecord $DNSRecord) { return null }

    /**
     * @return mixed
     */
    public function getHostAPIKey() { return null }

    /**
     * @param null $userId
     * @return mixed
     */
    public function getDomainList($userId = null) { return null }

    /**
     * @return mixed
     */
    public function getUserId() 
    {
        return $dataStore->getCloudFlareEmail();
    }
}


}