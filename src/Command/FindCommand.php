<?php

namespace DrupalIssueFinder\Command;

use DrupalIssueFinder\DataBase;
use DrupalIssueFinder\DrupalOrg\Request;
use DrupalIssueFinder\Settings;
use DrupalIssueFinder\UtilsTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;



/**
 * Finds issue to work on.
 */
class FindCommand extends Command
{
  use UtilsTrait;
  private const PROJECT_NID = 3060;

  // Issue statuses
  protected const ISSUE_STATUS_ACTIVE = "1";
  protected const ISSUE_STATUS_FIXED = "2";
  protected const ISSUE_STATUS_CLOSED_DUPLICATE = "3";
  protected const ISSUE_STATUS_POSTPONED = "4";
  protected const ISSUE_STATUS_CLOSED_WONT_FIX = "5";
  protected const ISSUE_STATUS_CLOSED_WORKS_AS_DESIGNED = "6";
  protected const ISSUE_STATUS_CLOSED_FIXED = "7";
  protected const ISSUE_STATUS_NEEDS_REVIEW = "8";
  protected const ISSUE_STATUS_NEEDS_WORK = "13";
  protected const ISSUE_STATUS_RTBC = "14";
  protected const ISSUE_STATUS_PATCH_TO_BE_PORTED = "15";
  protected const ISSUE_STATUS_POSTPONED_MAINTAINER_NEEDS_MORE_INFO = "16";
  protected const ISSUE_STATUS_CLOSED_OUTDATED = "17";
  protected const ISSUE_STATUS_CLOSED_CANNOT_REPRODUCE = "18";

  /**
   * @var \DrupalIssueFinder\DataBase
   */
  protected $db;

  /**
   * The start time of the current run.
   * @var int
   */
  protected $runTime;

  protected $previousRunTime;

  public function __construct(string $tester)
  {
    $this->test = $tester;

    parent::__construct();
    $this->db = new DataBase();
  }

  /**
   * @var \Symfony\Component\Console\Style\StyleInterface
   */
  protected $style;

  private const NAME = 'begin';
  protected static $defaultName = 'find:find';
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->runTime = time();
    $this->previousRunTime = $this->getPreviousRunTime();
    $this->style = new SymfonyStyle($input, $output);
    /*$s->info(
      $this->test
    );*/
    $this->getRTBCIssues();
    $this->getPreviousRTBCIssues();
      //parent::execute($input, $output);
      //$this->style->text("what");
    return self::SUCCESS;

  }

  /**
   * Update all known RTBC issues.
   */
  private function getRTBCIssues() {
    $issues = $this->getIssuesByStatus(static::ISSUE_STATUS_RTBC);
    $count = count($issues);
    $this->writeIssues($issues);
    $this->style->text("Found $count RTBC issues");
  }

  /**
   * Write issues to the database.
   *
   * @param array $issues
   */
  private function writeIssues(array $issues) {
    foreach ($issues as $issue) {
      $issue = (array) $issue;
      $issue['retrieve_time'] = $this->runTime;
      $insert = $this->db->insertInto('issues', $issue)->execute();
      if ($insert === FALSE) {
        throw new \Exception("could not insert");
      }

    }
  }

  /**
   * Gets the project record.
   *
   * @return null|array
   * @throws \Envms\FluentPDO\Exception
   */
  private function getIssueRecord(int $nid) {
    $query = $this->db->from('issues')
      ->where('nid', $nid);
    if ($query->count() === 0) {
      return NULL;
    }
    return DataBase::castColumns($query->fetchAll())[0];
  }

  private function getPreviousRunTime() {
    $query = $this->db->from('issues')
      ->limit('1')
      ->order('retrieve_time DESC');
    if ($query->count() === 0) {
      return NULL;
    }
    return (int) $query->fetch('retrieve_time');
  }

  private function getPreviousRTBCIssues() {
    $query = $this->db->from('issues')
      ->where('retrieve_time !=', $this->runTime)
      ->groupBy('nid');
    $issues = $query->fetchAll();
    $open_issues = [];
    foreach ($issues as $issue) {
      $nid = $issue['nid'];
      $node = static::getNode($nid);
      $closed_statuses = [static::ISSUE_STATUS_CLOSED_FIXED, static::ISSUE_STATUS_CLOSED_OUTDATED, static::ISSUE_STATUS_FIXED];
      if (in_array($node->field_issue_status, $closed_statuses)) {
        // @todo Should we have archive flag
        $delete = $this->db->deleteFrom('issues')
          ->where('nid', $nid)
          ->execute();
        if ($delete === FALSE) {
          throw new \Exception("could not delete");
        }
      }
      else {
        $open_issues[$nid] = $issue;
      }

    }
    $this->writeIssues($open_issues);
    $open_nids = array_keys($open_issues);
    $query = $this->db->from('issues')
      ->where('retrieve_time', $this->previousRunTime)
      ->where('field_issue_status', static::ISSUE_STATUS_RTBC)
      ->where('nid', $open_nids);
    $last_rtbc = $query->fetchAll();
    foreach ($last_rtbc as $previously_rtbc_issue) {
      $this->style->text($previously_rtbc_issue['nid'] . " was previous RTBC now " . $previously_rtbc_issue['field_issue_status']);
    }

  }

  /**
   * Get a single node from the drupal.org REST API.
   *
   * @param int $nid
   *   The node id.
   *
   * @return \stdClass|null
   *   The node as returned by the drupal.org API.
   */
  protected static function getNode(int $nid) {
    if ($node = Request::getSingle("/api-d7/node.json?nid=$nid")) {
      return $node;
    }
    static::logError("Could not retrieve node $nid");
    return NULL;
  }
  /**
   * @param string $status
   *
   * @return \stdClass[]
   */
  private function getIssuesByStatus(string $status): array {
    $issues = Request::getAllPages(
      "/api-d7/node.json?type=project_issue&field_project=" . static::PROJECT_NID . "&field_issue_status=$status&field_issue_version=" . Settings::getSetting('version'),
      ['title', 'nid', 'field_issue_status']
    );
    return $issues;
  }

}
