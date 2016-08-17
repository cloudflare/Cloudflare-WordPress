<?php

namespace CF\API\Exception;

class PageRuleLimitException extends \CloudFlareException
{
    protected $message = "Oops, looks like you've already used all available page rules, upgrade cloudflare.com/plans your plan or purchase cloudflare.com/a/page-rules more page rules";
}
