<?php

namespace DrupalIssueFinder\DrupalOrg;

use DrupalIssueFinder\Settings;
use DrupalIssueFinder\UtilsTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\Loader;

/**
 * Utility class for drupal.org REST requests.
 */
class Request extends \Httpful\Request
{

    use UtilsTrait;

  /**
   * Gets list of all items across all pages.
   *
   * @param string $uri
   * @param array $fields
   *  Fields to include in items. If empty all fields are returned.
   *
   * @return \stdClass[]
   *   The items.
   */
    public static function getAllPages($uri, array $fields = [])
    {
        $uri .= '&limit=50';
        if (strpos($uri, 'direction') === false) {
            $uri .= '&direction=asc';
        }
        $page = 0;
        $contents = [];
        while (1) {
            $paged_uri = "$uri&page=$page";
            $response = self::getResponse($paged_uri, $fields);
            if ($response->code === 404) {
                break;
            } elseif ($response->code === 200) {
                $contents = array_merge($contents, $response->body->list);
            } else {
                throw new \UnexpectedValueException("uri=$paged_uri status code: {$response->code}");
            }
            $page++;

        }
        return $contents;
    }

  /**
   * Gets a single item
   * @param $uri
   * @param array $fields
   *  Fields to include in items. If empty all fields are returned.
   *
   * @return \stdClass|null
   * @throws \Httpful\Exception\ConnectionErrorException
   */
    public static function getSingle($uri, array $fields = [])
    {
        $response = static::getResponse($uri, $fields);
        if ($response->code === 200) {
            if (isset($response->body->list[0])) {
                return $response->body->list[0];
            }
        }
        return null;
    }


  /**
   * Gets the response.
   *
   * @param string $paged_uri
   *
   * @param array $fields
   *  Fields to include in items. If empty all fields are returned.
   *
   * @return \Httpful\Response
   * @throws \Httpful\Exception\ConnectionErrorException
   */
    protected static function getResponse(string $paged_uri, array $fields = []): \Httpful\Response
    {
        $response = static::get($paged_uri)->send();
        if ($response->code === 200) {
            if (!isset($response->body->list)) {
                throw new \UnexpectedValueException("no list uri=$paged_uri status code: {$response->code}");
            }
            if ($fields) {
                foreach ($response->body->list as $key => $item) {
                    $new_item = new \StdClass();
                    foreach ($fields as $field) {
                        $new_item->{$field} = $item->{$field};
                    }
                    $response->body->list[$key] = $new_item;
                }
            }
        }
        return $response;
    }

    public static function get($uri, $mime = null)
    {
        $uri = static::getSiteUrl() . $uri;
        $request = parent::get($uri, $mime);
        if ($basic_auth = Settings::getSetting('basic_auth')) {
            $request =  $request->authenticateWithBasic($basic_auth['name'], $basic_auth['pass']);
        }
        return $request;
    }
}
