<?php

namespace CF\WordPress;

class Utils
{
    /**
     * @param $haystack
     * @param $needle
     *
     * @return bool
     */
    public static function endsWith($haystack, $needle)
    {
        return $needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    public static function isSubdomainOf($subDomainName, $domainName)
    {
        if (empty($subDomainName) || empty($domainName)) {
            return false;
        }

        // Check if strpos is a positive integer
        $dotPosition = strpos($subDomainName, $domainName) - 1;
        if ($dotPosition === -1) {
            return false;
        }

        return self::endsWith($subDomainName, $domainName) &&
                $subDomainName !== $domainName &&
                $subDomainName[$dotPosition] == '.';
    }

    public static function getRegistrableDomain($domainName)
    {
        // Remove characters up to the first "." character.
        // For example:
        // blog.domain.com -> domain.com
        // does not work with multiple subdomain
        // sub1.sub2.domain.com -> sub2.domain.com
        return preg_replace('/^[^.]*.\s*/', '', $domainName);
    }
}
