<?php

namespace App\Command;

use App\TestMongoService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestMongoCommand extends Command
{
    protected static $defaultName = 'test-mongo';
    /**
     * @var TestMongoService
     */
    private $testMongoService;

    public function __construct(TestMongoService $testMongoService, string $name = null)
    {
        parent::__construct($name);
        $this->testMongoService = $testMongoService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->testMongoService->emptyCollection();
        $this->testMongoService->creationObjects();
        $this->testMongoService->updateConditions();
        $this->testMongoService->parseDiscounts(['color' => 'red', 'category' => '2']);

        return 0;
    }
}
