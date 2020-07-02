#!/bin/bash
## DESCRIPTION
# This is a script to easily test WordPress Plugin Code on your own server
# Example usage: ./script/sync.sh root domain.com
#$1 = username
#$2 = hostname

## TROUBLESHOOT
# If you have issues using hostname it's probably because the hostname
# has cloudflare IP. In that case you'll need to use IP address instead of
# hostname

PLUGINPATH="/var/www/html/wordpress/wp-content/plugins/cloudflare"
USRHOSTFLDR="$1@$2:$PLUGINPATH"

rsync -azuvP --delete ./ "$USRHOSTFLDR"
