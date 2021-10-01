<?php

namespace DrupalIssueFinder;

use DrupalIssueFinder\DrupalOrg\Request;

/**
 * Misc utilities.
 */
trait UtilsTrait {

  /**
   * Logs an error an optionally rethrows an exception.
   *
   * @param $message
   * @param \Exception|null $exception
   * @param bool $throw
   *
   * @throws \Exception
   */
  protected static function logError($message, \Exception $exception = NULL, $throw = FALSE) {
    if ($exception !== NULL) {
      $message .= ': ' . $exception->getMessage();
    }
    static::debug($message);
    error_log($message . "\n", 3, static::getBaseDir() . '/errors.txt');
    if ($throw) {
      if (!$exception) {
        $exception = new \Exception($message);
      }
      throw $exception;
    }
  }

  /**
   * Gets the site URL.
   *
   * @return string
   */
  protected static function getSiteUrl() {
    static $site_url = NULL;
    if (empty($site_url)) {
      if ($settings_url = Settings::getSetting('site_url')) {
        $site_url = $settings_url;
      }
      else {
        $site_url = 'https://www.drupal.org';
      }
    }
    return $site_url;
  }
  /**
   * Gets the current base directory.
   *
   * @return string
   */
  protected static function getBaseDir() {
    [$scriptPath] = get_included_files();
    return dirname($scriptPath);
  }

  /**
   * Output a debug message if in test mode.
   *
   * @param $string
   * @param string $status
   */
  protected static function debug($string, $status = 'info') {
    if (!Settings::isTesting()) {
      return;
    }
    if ($status === 'info') {
      $string = "ℹ️  $string";
    }
    elseif ($status === 'warning') {
      $string = "⚠️  $string";
    }
    else {
      $string = "❓  $string";
    }
    print "$string\n";
  }

  /**
   * Gets a file from drupal.org
   *
   * @param $path
   *
   * @return false|string
   */
  protected static function getDrupalOrgFile($path) {
    $headers = [
      "User-Agent: " . Settings::getRequiredSetting('user_agent'),
    ];
    if ($basic_auth = Settings::getSetting('basic_auth')) {
      $auth = base64_encode($basic_auth['name'] . ':' . $basic_auth['pass']);
      $headers[] = "Authorization: Basic $auth";
    }
    $context = stream_context_create([
      "http" => [
        "header" => $headers,
      ]
    ]);
    
    return file_get_contents($path, false, $context );
  }

}
