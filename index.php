<?php
if (!defined('ABSPATH')) { // Exit if accessed directly
    exit;
}

$config = new CF\Integration\DefaultConfig(file_get_contents('config.js', true));
$logger = new CF\Integration\DefaultLogger($config->getValue('debug'));
$dataStore = new CF\WordPress\DataStore($logger);
$wordpressAPI = new CF\WordPress\WordPressAPI($dataStore);

wp_register_style('cf-corecss', plugins_url('stylesheets/cf.core.css', __FILE__));
wp_enqueue_style('cf-corecss');
wp_register_style('cf-componentscss', plugins_url('stylesheets/components.css', __FILE__));
wp_enqueue_style('cf-componentscss');
wp_register_style('cf-hackscss', plugins_url('stylesheets/hacks.css', __FILE__));
wp_enqueue_style('cf-hackscss');
wp_enqueue_script('cf-compiledjs', plugins_url('compiled.js', __FILE__), null, true);
?>
<div id="root" class="cloudflare-partners site-wrapper"></div>
<script>
var absoluteUrlBase = '<?=plugins_url('/cloudflare/');?>';

cfCSRFToken = '<?= wp_create_nonce(\CF\WordPress\WordPressAPI::API_NONCE) ?>';
localStorage.cfEmail = '<?=$dataStore->getCloudFlareEmail();?>';
/*
 * A callback for cf-util-http to proxy all calls to our backend
 *
 * @param {Object} [opts]
 * @param {String} [opts.method] - GET/POST/PUT/PATCH/DELETE
 * @param {String} [opts.url]
 * @param {Object} [opts.parameters]
 * @param {Object} [opts.headers]
 * @param {Object} [opts.body]
 * @param {Function} [opts.onSuccess]
 * @param {Function} [opts.onError]
 */
function RestProxyCallback(opts) {
    //only proxy external REST calls
    if(opts.url.lastIndexOf("http", 0) === 0) {
        if(!opts.parameters) {
            opts.parameters = {};
        }
        
        // WordPress Ajax Action
        opts.parameters['action'] = 'cloudflare_proxy';

        if(opts.method.toUpperCase() !== "GET") {
            if(!opts.body) {
                opts.body = {};
            }

            opts.body['cfCSRFToken'] = cfCSRFToken;
            opts.body['proxyURL'] = opts.url;
        } else {
            var clientAPIURL = '<?= \CF\API\Client::ENDPOINT ?>';
            var pluginAPIURL = '<?= \CF\API\Plugin::ENDPOINT ?>';

            // If opts url begins with clientAPIURL or pluginAPIURL
            // Remove the api url and assign the rest to proxyURL
            if (opts.url.substring(0, clientAPIURL.length) === clientAPIURL) {
                opts.parameters['proxyURL'] = opts.url.substring(clientAPIURL.
                    length);
                opts.parameters['proxyURLType'] = 'CLIENT';
            } else if (opts.url.substring(0, pluginAPIURL.length) === pluginAPIURL) {
                opts.parameters['proxyURL'] = opts.url.substring(pluginAPIURL.length);
                opts.parameters['proxyURLType'] = 'PLUGIN';
            }
        }

        // WordPress Ajax Global
        opts.url = ajaxurl; 
    } else {
        opts.url = absoluteUrlBase + opts.url;
    }
}
</script>
