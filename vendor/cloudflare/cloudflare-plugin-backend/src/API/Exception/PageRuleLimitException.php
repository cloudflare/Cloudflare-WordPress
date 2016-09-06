<?php

namespace CF\API\Exception;

class PageRuleLimitException extends CloudFlareException
{
    protected $message = "Oops, looks like you've used all available page rules, purchase more page rules from cloudflare.com/a/page-rules";
}
