<?php

namespace DrupalIssueFinder\Command;

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

  public function __construct(string $tester)
  {
    $this->test = $tester;

    parent::__construct();
  }

  /**
   * @var \Symfony\Component\Console\Style\StyleInterface
   */
  protected $style;

  private const NAME = 'begin';
  protected static $defaultName = 'find:find';
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $s = new SymfonyStyle($input, $output);
    $s->info(
      $this->test
    );
      //parent::execute($input, $output);
      //$this->style->text("what");
    return self::SUCCESS;

  }
}
