<?
/*
wp_register_style( 'prefix-style', plugins_url('style.css', __FILE__) );
    wp_enqueue_style( 'prefix-style' );
    */
wp_register_style( 'cf-corecss', plugins_url('stylesheets/cf.core.css', __FILE__));
wp_enqueue_style('cf-corecss');
wp_register_style( 'cf-componentscss', plugins_url('cloudflare/stylesheets/components.css', __FILE__));
wp_enqueue_style('cf-componentcss');
wp_register_style( 'cf-hackscss', plugins_url('cloudflare/stylesheets/hacks.css', __FILE__));
wp_enqueue_style('cf-hackscss');
wp_enqueue_script( 'cf-compiledjs', plugins_url( 'compiled.js' , __FILE__ ), null, true);
echo plugins_url('cloudflare/stylesheets/hacks.css');
?>
<div id="root" class="cloudflare-partners site-wrapper"></div>
<script>
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
        if(opts.method.toUpperCase() !== "GET") {
            if(!opts.body) {
                opts.body = {};
            }
            opts.body['cfCSRFToken'] = cfCSRFToken;
            opts.body['proxyURL'] = opts.url;
        } else {
            if(!opts.parameters) {
                opts.parameters = {};
            }
            opts.parameters['proxyURL'] = opts.url;
        }

        opts.url = "./proxy.live.php";
    } else {
    	opts.url = 'http://localhost:8888/wordpress/wp-content/plugins/cloudflare/' + opts.url;
    }
}
</script>
