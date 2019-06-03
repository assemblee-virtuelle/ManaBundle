<?php

/**
* This file is part of the ManaBundle, a WebFinger library for Symfony
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*
* @package  ManaBundle
* @subpackage XRD
* @author   Michel Cadennes <michel.cadennes@assemblee-virtuelle.org>
* @license  https://opensource.org/licenses/GPL-3.0 GNU General Public License v3
* @link https://github.com/assemblee-virtuelle/ManaBundle/tree/master/XRD/README.md
* @version 0.1.0
*/

namespace App\WebFinger;

use GuzzleHttp\Client;
use GuzzleHttp\Client\ItemInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\AdapterInterface;

// use App\Error\Error;
use App\Element\WebFingerReaction;

/**
 * PHP WebFinger client. Performs discovery and returns a result.
 *
 * Fetches the well-known WebFinger URI
 * https://example.org/.well-known/webfinger?resource=acct:user@example.org
 *
 * If that fails, the account's host's .well-known/host-meta file is fetched,
 * then the file indicated by the "lrdd" type, as specificed by RFC 6415.
 *
 * <code>
 * require_once 'Net/WebFinger.php';
 * $wf = new Net_WebFinger();
 * $react = $wf->finger('user@example.org');
 * echo 'OpenID: ' . $react->openid . "\n";
 * </code>
 *
 * @category Networking
 * @package  Net_WebFinger
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL
 * @link     http://pear.php.net/package/Net_WebFinger
 */
class WebFinger
{
    /**
     * Retry with HTTP if the HTTPS webfinger request fails.
     * This is not allowed by the webfinger specification, but may be
     * helpful during development.
     *
     * @var boolean
     */
    public $fallbackToHttp = false;

    /**
     * HTTP client to use.
     *
     * @var Client
     */
    protected $httpClient;

    /**
     * Cache object to use (PEAR Cache package).
     *
     * @var Cache
     */
    protected $cache;


    public function __construct(Client $httpClient, AdapterInterface $cache)
    {
      $this->httpClient = $httpClient;
      $this->cache = $cache;
    }

    /**
     * Finger a email address like identifier - get information about it.
     *
     * If an error occurs, you find it in the reaction's $error property.
     *
     * @param string $url Identification URL. Full URLs and schema-less ones
     *                    supported. When the schema is missing, "acct:" is used.
     *
     * @return WebFingerReaction Reaction object
     *
     * @see Net_WebFinger_Reaction::$error
     */
    public function finger($url)
    {
        $res = $this->getIdentifierAndHost($url);
        if ($res instanceof WebFingerReaction) {
            return $res;
        }

        list($identifier, $host) = $res;
        $react = $this->loadWebfinger($identifier, $host);

        if ($react->error === null) {
            //FIXME: only fallback if URL does not exist, not on general error
            // like broken XML/JSON
            return $react;
        }

        //fall back to host-meta and LRDD file if webfinger URL does not exist
        $hostMeta = $this->loadHostMeta($host);
        if ($hostMeta->error) {
            return $hostMeta;
        }

        $react = $this->loadLrdd($identifier, $host, $hostMeta);
        if ($react->error
            && $react->error->getCode() == Net_WebFinger_Error::NO_LRDD
        ) {
            $react->error = new Net_WebFinger_Error(
                'No webfinger data found',
                Net_WebFinger_Error::NOTHING,
                $react->error
            );
        }

        return $react;
    }

    /**
     * Convert a single URL string to an identifier and its host.
     * Automatically adds acct: if it's missing.
     *
     * @param string $url Some URL, with or without scheme
     *
     * @return WebFingerReaction|array Error reaction or
     *                                      array with identifier and host as
     *                                      values
     */
    protected function getIdentifierAndHost($url)
    {
        if (!preg_match('/^([a-zA-Z+]+):/', $url, $match)) {
            $identifier = 'acct:' . $url;
            $scheme = 'acct';
        } else {
            $identifier = $url;
            $scheme = $match[1];
        }

        $host = null;

        switch ($scheme) {
          case 'acct':
          case 'mailto':
          case 'xmpp':
              if (strpos($identifier, '@') !== false) {
                  $host = substr($identifier, strpos($identifier, '@') + 1);
              }
              break;
          default:
              $host = parse_url($identifier, PHP_URL_HOST);
              break;
          }

        if (empty($host)) {
            $react = new WebFingerReaction();
            $react->url = $identifier;
            $react->error = new WebFingerError(
                'Identifier not supported',
                WebFingerError::NOT_SUPPORTED,
                $react->error
            );

            return $react;
        }

        return [$identifier, $host];
    }

    /**
     * Loads the webfinger JRD file for a given identifier
     *
     * @param string $identifier E-mail address like identifier ("user@host")
     * @param string $host       Hostname of $identifier
     *
     * @return Net_WebFinger_Reaction Reaction object
     *
     * @see Net_WebFinger_Reaction::$error
     */
    protected function loadWebfinger($identifier, $host)
    {
        $account = $identifier;
        $userUrl = 'https://' . $host . '/.well-known/webfinger?resource='
            . urlencode($account);

        $react = $this->loadXrdCached($userUrl);

        if ($this->fallbackToHttp && $react->error !== null
            && $this->isHttps($userUrl)
        ) {
            //fall back to HTTP
            $userUrl = 'http://' . substr($userUrl, 8);
            $react = $this->loadXrdCached($userUrl);
            $react->secure = false;
        }
        if ($react->error !== null) {
            return $react;
        }

        $this->verifyDescribes($react, $account);

        return $react;
    }

    /**
     * Load the host's .well-known/host-meta XRD file.
     *
     * The XRD is stored in the reaction object's $source['host-meta'] property,
     * and any error that is encountered in its $error property.
     *
     * When the XRD file cannot be loaded, this method returns false.
     *
     * @param string $host Hostname to fetch host-meta file from
     *
     * @return WebFingerReaction Reaction object
     *
     * @see Net_WebFinger_Reaction::$error
     */
    protected function loadHostMeta($host)
    {
        /**
         * HTTPS is secure.
         * xrd->describes() may not be used because the host-meta should not
         * have a subject at all: http://tools.ietf.org/html/rfc6415#section-3.1
         * > The document SHOULD NOT include a "Subject" element, as at this
         * > time no URI is available to identify hosts.
         * > The use of the "Alias" element in host-meta is undefined and
         * > NOT RECOMMENDED.
         */
        $react = new NetWebFingerReaction();
        $react->secure = true;

        $react = $this->loadXrdCached('https://' . $host . '/.well-known/host-meta');
        if (!$react->error) {
            return $react;
        }

        $react = $this->loadXrdCached(
            'http://' . $host . '/.well-known/host-meta'
        );
        //no https, so not secure
        $react->secure = false;

        if (!$react->error) {
            return $react;
        }

        $react->error = new WebFingerError(
            'No .well-known/host-meta file found on ' . $host,
            WebFingerError::NO_HOSTMETA,
            $react->error
        );

        return $react;
    }

    /**
     * Loads the user XRD file for a given identifier
     *
     * The XRD is stored in the reaction object's $userXrd property,
     * any error is stored in its $error property.
     *
     * @param string $identifier E-mail address like identifier ("user@host")
     * @param string $host       Hostname of $identifier
     * @param object $hostMeta   host-meta XRD object
     *
     * @return Net_WebFinger_Reaction Reaction object
     *
     * @see Net_WebFinger_Reaction::$error
     */
    protected function loadLrdd($identifier, $host, XML_XRD $hostMeta)
    {
        $link = $hostMeta->get('lrdd', 'application/xrd+xml');
        if ($link === null || !$link->template) {
            $react = new Net_WebFinger_Reaction();
            $react->error = new Net_WebFinger_Error(
                'No lrdd link in host-meta for ' . $host,
                Net_WebFinger_Error::NO_LRDD_LINK
            );
            $this->mergeHostMeta($react, $hostMeta);

            return $react;
        }

        $account = $identifier;
        $userUrl = str_replace('{uri}', urlencode($account), $link->template);

        $react = $this->loadXrdCached($userUrl);
        if ($react->error && $this->isHttps($userUrl)) {
            //fall back to HTTP
            $userUrl = 'http://' . substr($userUrl, 8);
            $react = $this->loadXrdCached($userUrl);
        }
        if ($react->error) {
            $react->error = new Net_WebFinger_Error(
                'LRDD file not found',
                Net_WebFinger_Error::NO_LRDD,
                $react->error
            );
            $this->mergeHostMeta($react, $hostMeta);

            return $react;
        }

        if (!$this->isHttps($userUrl)) {
            $react->secure = false;
        }
        $this->verifyDescribes($react, $account);

        $this->mergeHostMeta($react, $hostMeta);

        return $react;
    }

    /**
     * Merges some properties from the hostMeta file into the reaction object
     *
     * @param object $react    Target reaction object
     * @param object $hostMeta Source hostMeta object
     *
     * @return void
     */
    protected function mergeHostMeta(
        Net_WebFinger_Reaction $react,
        Net_WebFinger_Reaction $hostMeta
    )
    {
        foreach ($hostMeta->links as $link) {
            if ($link->rel == 'http://specs.openid.net/auth/2.0/provider') {
                $react->links[] = $link;
            }
        }
        $react->secure = $react->secure && $hostMeta->secure;
    }

    /**
     * Verifies that the reaction object is about the given account URL.
     * Sets the error property in the reaction object.
     *
     * @param object $react   Reaction object to check
     * @param string $account acct: URL that the reaction should be about
     *
     * @return void
     */
    protected function verifyDescribes(WebFingerReaction $react, $account)
    {
        if (!$react->describes($account)) {
            $react->error = new Net_WebFinger_Error(
                'Webfinger file is not about "' . $account . '"'
                . ' but "' . $react->subject . '"',
                Net_WebFinger_Error::DESCRIBE
            );
            //additional hint that something is wrong
            $react->secure = false;
        }
    }

    /**
     * Check whether the URL is an HTTPS URL.
     *
     * @param string $url URL to check
     *
     * @return boolean True if it's a HTTPS url
     */
    protected function isHttps($url)
    {
        return substr($url, 0, 8) == 'https://';
    }

    /**
     * Load an XRD file and caches it.
     *
     * @param string $url URL to fetch
     *
     * @return Net_WebFinger_Reaction Reaction object with XRD data
     *
     * @see loadXrd()
     */
    protected function loadXrdCached($url)
    {
        if (!$this->cache) {
            return $this->loadXrd($url);
        }

        //FIXME: make $host secure, remove / and so
        $beta = 1.0;
        $cacheId = 'webfinger-cache-' . str_replace(
            array('/', ':'), '-.-', $url
        );
        $cachedValue = $this->cache->get($cacheId, function (ItemInterface $item) {
          $item->expiresAfter(3600);
          $computedValue =  $this->loadXrd($url);
          return $computedValue;
        }, $beta);

        return $cachedValue;
    }

    /**
     * Loads the XRD file from the given URL.
     * Sets $react->error when loading fails
     *
     * @param string $url URL to fetch
     *
     * @return boolean True if loading data succeeded, false if not
     */
    protected function loadXrd($url)
    {
        try {
            $react = new WebFingerReaction();
            $react->url = $url;
            $react->error = null;
            $react->load(
              (($this->httpClient !== null) ? $this->httpClientRequest($url) : $this->fileRequest($url)),
              null,
              XRD::XRD_STRING_SOURCE
            );
        } catch (\Exception $e) {
            $react->error = $e;
        }

        return $react;
    }

    /**
     * Tris to grab an XRD resource from a given URI
     * Uses an HTTP service upon which the wepfinger depends
     *
     * @param  string $url Taget URI
     * @return string
     *
     * @throws WebFingerError
     *
     * @example clientRequest(tchevengour@mamot.fr)
     */
    public function httpClientRequest(string $url)
    {
      $headers = [
        'user-agent' => 'PEAR Net_WebFinger',
        'accept' => 'application/jrd+json, application/xrd+xml;q=0.9',
      ];

      try {
        $response = $this->httpClient->request('GET', $url, $headers);
      } catch (GuzzleHttp\Exception\ClientException $e) {
        throw new \Exception(
          'Error loading XRD file: ' . $response->getStatusCode()
          . ' ' . $response->getReasonPhrase(),
          WebFingerError::NOT_FOUND
        );
      }

      return $response->getBody()->getContents();

    }

    /**
     * Tris to grab an XRD resource from a given URI
     * Uses PHP facilities to reach files through networks with HTTP protocol
     *
     * @param  string $url Taget URI
     * @return string
     *
     * @throws WebFingerError
     *
     * @example clientRequest(tchevengour@mamot.fr)
     */
    public function fileRequest(string $url)
    {
      $context = stream_context_create(
          array(
              'http' => array(
                  'user-agent' => 'PEAR Net_WebFinger',
                  'header' => 'accept: application/jrd+json, application/xrd+xml;q=0.9',
                  'follow_location' => true,
                  'max_redirects' => 20
              )
          )
      );
      $content = @file_get_contents($url, false, $context);
      if ($content === false) {
          $msg = 'Error loading XRD file';
          if (isset($http_response_header)) {
              $status = null;
              //we need this because there will be several HTTP/..
              // status lines when redirection is going on.
              foreach ($http_response_header as $header) {
                  if (substr($header, 0, 5) == 'HTTP/') {
                      $status = $header;
                  }
              }
              $msg .= ': ' . $status;
          };
          throw new WebFingerError(
              $msg, WebFingerError::NOT_FOUND,
              new WebFingerError(
                  'file_get_contents on ' . $url
              )
          );
      }
      return $content;
    }
}
