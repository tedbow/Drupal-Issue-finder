<?php

namespace DrupalIssueFinder;

use Envms\FluentPDO\Query;
use Envms\FluentPDO\Structure;

/**
 * Class to manage the SQLite Database.
 */
class DataBase extends Query {
  use UtilsTrait;
  private const FILE_LOCATION = 'db/rector.db';

  /**
   * Gets the database file location.
   * @return string
   *   The database file location. The file location is based on the site URL so
   *   connecting to the dev.drupal.org will use a different database file.
   */
  private static function getDatabaseLocation() {
      $site_url = static::getSiteUrl();
      $base_name = str_replace('https://', '', $site_url);
      return "db/$base_name.db";
  }
  /**
   * {@inheritdoc}
   */
  public $exceptionOnError = TRUE;

  public function __construct(\PDO $pdo = NULL, Structure $structure = NULL) {
    if (!$pdo) {
      $pdo = new \PDO('sqlite:' . static::getDatabaseLocation());
    }
    parent::__construct($pdo, $structure);
    $this->convertRead = TRUE;
  }

  /**
   * Casts DB columns to INTs.
   *
   * @param $fetchAll
   *   The result of a \Envms\FluentPDO\Queries\Select::fetchAll() call.
   *
   * @return array|bool|mixed
   */
  public static function castColumns($fetchAll) {
    $known_ints = [
      'nid',
      'rector_issue',
    ];
    if (is_bool($fetchAll)) {
      return $fetchAll;
    }
    elseif (is_array($fetchAll)) {
      foreach ($fetchAll as &$row) {
        foreach ($row as $key => $value) {
          if (in_array($key, $known_ints) && $value !== NULL) {
            $row[$key] = (int) $value;
          }
        }
      }
    }
    return $fetchAll;
  }

  /**
   * Creates all table necessary for this application.
   *
   * @throws \Exception
   */
  public static function createTables() {
    if (file_exists(static::getDatabaseLocation())) {
      throw new \Exception("db file already exists");
    }
    $sql = <<<SQL
BEGIN TRANSACTION;

CREATE TABLE IF NOT EXISTS "issues" (
	"nid"	INTEGER NOT NULL,
	"title"	TEXT NOT NULL,
	"field_issue_status"	INTEGER NOT NULL,
	"last_commenter_uid"	INTEGER,
	"retrieve_time"	INTEGER NOT NULL
);
COMMIT;
SQL;
    try {
      $pdo = new \PDO('sqlite:' . static::getDatabaseLocation());
      $pdo->setAttribute(\PDO::ATTR_ERRMODE,
        \PDO::ERRMODE_EXCEPTION);
      $pdo->exec($sql);
    }
    catch (\PDOException $exception) {
      self::logError('Could not create table', $exception, TRUE);
    }

  }


}
