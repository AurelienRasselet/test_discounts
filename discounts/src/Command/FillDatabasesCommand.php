<?php

namespace App\Command;

use App\TestMongoService;
use App\TestMySqlService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FillDatabasesCommand extends Command
{
    protected static $defaultName = 'fill-databases';
    private TestMongoService $testMongoService;
    private TestMySqlService $testMySqlService;

    public function __construct(TestMongoService $testMongoService, TestMySqlService $testMySqlService, string $name = null)
    {
        parent::__construct($name);
        $this->testMongoService = $testMongoService;
        $this->testMySqlService = $testMySqlService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('nbProduct', InputArgument::OPTIONAL, 'Argument description')
            ->addArgument('nbProductBasket', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * SIMPLE CONDITIONS
         */
        //$this->testMongoService->emptyCollection();
        $this->testMySqlService->emptyCollection();
        $this->testMySqlService->startTransaction();

        $nbProduct = $input->getArgument('nbProduct');
        for ($i = 0; $i < $nbProduct; $i++) {
            $price = rand(0, 10000);
            $cat = rand(1, 100);

            $discount = rand(1, 25);
            //$idProduct = $this->testMongoService->createProduct($price, $cat);
            //$this->testMongoService->createCondition($idProduct, $price, $cat, $discount);

            $idProduct = $this->testMySqlService->createProduct($price, $cat);
            $this->testMySqlService->createCondition($idProduct, $price, $cat, $discount);
        }
        $this->testMySqlService->endTransaction();

        // MONGODB
        /*$start_time = microtime(TRUE);
        $this->testMongoService->getProductsDiscounts($input->getArgument('nbProductBasket'));
        $end_time = microtime(TRUE);
        echo ' in ' . ($end_time - $start_time) . ' seconds for simple MONGODB' . PHP_EOL;*/

        // MYSQL
        $start_time = microtime(TRUE);
        $this->testMySqlService->getProductsDiscounts($input->getArgument('nbProductBasket'));
        $end_time = microtime(TRUE);
        echo ' in ' . ($end_time - $start_time) . ' seconds for simple MYSQL' . PHP_EOL;

        /**
         * COMPLEXES CONDITIONS
         */
        /*$this->testMongoService->emptyCollection();
        $this->testMySqlService->emptyCollection();
        $this->testMySqlService->startTransaction();

        $nbProduct = $input->getArgument('nbProduct');
        for ($i = 0; $i < $nbProduct; $i++) {
            $price = rand(0, 10000);
            $cat = rand(1, 100);

            $discount = rand(1, 25);
            $idProduct = $this->testMongoService->createProduct($price, $cat);
            $this->testMongoService->createComplexCondition($idProduct, $price, $cat, $discount);

            $idProduct = $this->testMySqlService->createProduct($price, $cat);
            $this->testMySqlService->createComplexCondition($idProduct, $price, $cat, $discount);
        }
        $this->testMySqlService->endTransaction();

        // MONGODB
        $start_time = microtime(TRUE);
        $this->testMongoService->getProductsDiscounts($input->getArgument('nbProductBasket'));
        $end_time = microtime(TRUE);
        echo ' in ' . ($end_time - $start_time) . ' seconds for complex MONGODB' . PHP_EOL;

        // MYSQL
        $start_time = microtime(TRUE);
        $this->testMySqlService->getProductsDiscounts($input->getArgument('nbProductBasket'));
        $end_time = microtime(TRUE);
        echo ' in ' . ($end_time - $start_time) . ' seconds for complex MYSQL' . PHP_EOL;
*/
        return 0;
    }
}
