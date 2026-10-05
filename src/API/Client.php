<?php

namespace Cloudflare\APO\API;

use Cloudflare\APO\Integration\IntegrationInterface;
use Cloudflare\APO\WordPress\Utils;

class Client extends AbstractAPIClient
{
    public const CLIENT_API_NAME = 'CLIENT API';
    public const ENDPOINT = 'https://api.cloudflare.com/client/v4/';
    public const X_AUTH_KEY = 'X-Auth-Key';
    public const X_AUTH_EMAIL = 'X-Auth-Email';
    public const AUTHORIZATION = 'Authorization';
    public const USER_AGENT = 'User-Agent';

    /**
     * Returns true when the supplied credential is a Cloudflare Global API
     * Key (which uses the X-Auth-Email + X-Auth-Key header pair) and false
     * for everything else (treated as an API Token sent via Authorization:
     * Bearer).
     *
     * Cloudflare credential formats (see
     * https://developers.cloudflare.com/fundamentals/api/get-started/token-formats/):
     *   - Global API Key  (new):      "cfk_"  + 40 chars + checksum
     *   - User API Token  (new):      "cfut_" + 40 chars + checksum
     *   - Account API Token (new):    "cfat_" + 40 chars + checksum
     *   - Global API Key  (pre-2026): 37-45 character lowercase hex string
     *   - API Token       (pre-2026): 40-character alphanumeric string
     *
     * @param string $key
     *
     * @return bool
     */
    public static function isGlobalApiKey($key)
    {
        if (!is_string($key) || $key === '') {
            return false;
        }

        // New scannable format: explicitly prefixed.
        if (strncmp($key, 'cfk_', 4) === 0) {
            return true;
        }
        if (strncmp($key, 'cfut_', 5) === 0 || strncmp($key, 'cfat_', 5) === 0) {
            return false;
        }

        // Pre-2026 Global API Key: 37-45 lowercase hex characters.
        $len = strlen($key);
        if ($len >= 37 && $len <= 45 && preg_match('/^[0-9a-f]+$/', $key)) {
            return true;
        }

        return false;
    }

    /**
     * @param Request $request
     *
     * @return Request
     */
    public function beforeSend(Request $request)
    {
        $key = $this->data_store->getClientV4APIKey();
        $headers = array(
            self::CONTENT_TYPE_KEY => self::APPLICATION_JSON_KEY,
        );

        $composer = Utils::getComposerJson();
        $version = $composer['version'] ?? 'unknown';
        $wp_version = $GLOBALS['wp_version'] ?? 'unknown';
        $headers[self::USER_AGENT] =  'wordpress/' . $wp_version . '; cloudflare-wordpress-plugin/' . $version;

        // Cloudflare distinguishes Global API Keys (sent via X-Auth-Email +
        // X-Auth-Key) from API Tokens (sent via Authorization: Bearer) by the
        // credential format. See self::isGlobalApiKey().
        if (self::isGlobalApiKey($key)) {
            $headers[self::X_AUTH_EMAIL] = $this->data_store->getCloudFlareEmail();
            $headers[self::X_AUTH_KEY] = $key;
        } else {
            $headers[self::AUTHORIZATION] = "Bearer {$key}";
        }

        $request->setHeaders($headers);

        // Remove cfCSRFToken (a custom header) to save bandwidth
        $body = $request->getBody();
        unset($body['cfCSRFToken']);
        $request->setBody($body);

        return $request;
    }

    /**
     * @param $message
     *
     * @return array
     */
    public function createAPIError($message)
    {
        $this->logger->error($message);

        return array(
            'result' => null,
            'success' => false,
            'errors' => array(
                array(
                    'code' => '',
                    'message' => $message,
                ),
            ),
            'messages' => array(),
        );
    }

    /**
     * @param mixed $error An error exposing getMessage() and getResponse()->getBody().
     *
     * @return string
     */
    public function getErrorMessage($error)
    {
        $jsonResponse = json_decode($error->getResponse()->getBody(), true);
        $errorMessage = $error->getMessage();

        if (count($jsonResponse['errors']) > 0) {
            $errorMessage = $jsonResponse['errors'][0]['message'];
        }

        return $errorMessage;
    }

    /**
     * @param $response
     *
     * @return bool
     */
    public function responseOk($response)
    {
        return isset($response['success']) ? $response['success'] : false;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return self::ENDPOINT;
    }

    /**
     * @return string
     */
    public function getAPIClientName()
    {
        return self::CLIENT_API_NAME;
    }

    /**
     * GET /zones/:id.
     *
     * @param $zone_tag
     *
     * @return string
     */
    public function zoneGetDetails($zone_tag)
    {
        $request = new Request('GET', 'zones/' . $zone_tag, array(), array());

        return $this->callAPI($request);
    }
}
