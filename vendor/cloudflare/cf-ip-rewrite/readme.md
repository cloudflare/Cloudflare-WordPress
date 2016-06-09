# CloudFlare PHP IP Rewriting 

This module makes it easy for developers to add rewrite CloudFlare IP Addresses for actual end-user IP Addresses at the application layer. It is recommended to either install mod_cloudflare for Apache or use nginx rewrite rules (https://support.cloudflare.com/hc/en-us/articles/200170706-Does-CloudFlare-have-an-IP-module-for-Nginx-) if possible.

For those cases, where the IP can not be guaranteed to be rewritten by one of these alternate means, this module can be used to rewrite the IP address.

### How it works
    
    $is_cf = CloudFlare\IpRewrite::isCloudFlare();
    $original_ip = CloudFlare\IpRewrite::getOriginalIP();
    $rewritten_ip = CloudFlare\IpRewrite::getRewrittenIP();
    
The class exposes three methods for interaction. A call to any of these methods will trigger the rewriting, storing the rewritten values for any subsequent calls. If the IP is rewritten, `$_SERVER["REMOTE_ADDR"]` will also be updated to reflect the end-user's IP address.

`CloudFlare\IpRewrite::isCloudFlare();` returns `true` if the `CF_CONNECTING_IP` header is present in the request. If the rewrite happened at the server level, this function will still return true, even though it did not rewrite the IP address.

`CloudFlare\IpRewrite::getOriginalIP()` returns the original ip address from `$_SERVER["REMOTE_ADDR"]`, before this module alters it.

`CloudFlare\IpRewrite::getRewrittenIP()` returns the rewritten ip address, if a rewrite occurs, otherwise it will return false.

### Best practice

The best course of action is to call `CloudFlare\IpRewrite::isCloudFlare();` to guarantee that a rewrite has occured. From that point forward, `$_SERVER["REMOTE_ADDR"]` can be used to retrieve the end-users IP address (whether the call came through CloudFlare or not).

`CloudFlare\IpRewrite::getOriginalIP()` and `CloudFlare\IpRewrite::getRewrittenIP()` should be used if you need visibility into the rewrite that has occured.

### Testing this module

This module comes with a set of tests that can be run using phpunit. To run the tests, run `composer install` on the package and then one of the following commands:

#### Basic Tests

    phpunit -c phpunit.xml.dist
    
#### With code coverage report in `coverage` folder

    phpunit -c phpunit.xml.dist --coverage-html coverage

