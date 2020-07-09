<?php

namespace App\Controller;

use App\TestMongoService;
use App\TestMySqlService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TestDiscountController extends AbstractController
{
    /**
     * @var TestMongoService
     */
    private TestMongoService $testMongoService;
    /**
     * @var TestMySqlService
     */
    private TestMySqlService $testMySqlService;

    public function __construct(TestMongoService $testMongoService, TestMySqlService $testMySqlService)
    {
        $this->testMongoService = $testMongoService;
        $this->testMySqlService = $testMySqlService;
    }

    /**
     * @Route("/resetDb/{complex}/{nbProduct}", name="reset_db")
     */
    public function resetDb(bool $complex, int $nbProduct)
    {
        /**
         * SIMPLE CONDITIONS
         */
        $this->testMongoService->emptyCollection();
        $this->testMySqlService->emptyCollection();
        $this->testMySqlService->startTransaction();

        for ($i = 0; $i < $nbProduct; $i++) {
            $price = rand(0, 10000);
            $cat = rand(1, 100);
            $discount = rand(1, 25);

            $idProduct = $i+1;

            $this->testMySqlService->createProduct($price, $cat, $i+1);
            $this->testMongoService->createProduct($price, $cat);

            if (0 == $complex) {
                $this->testMySqlService->createCondition($idProduct, $price, $cat, $discount);
                $this->testMongoService->createCondition($idProduct, $price, $cat, $discount);
            } else {
                $this->testMySqlService->createComplexCondition($idProduct, $price, $cat, $discount);
                $this->testMongoService->createComplexCondition($idProduct, $price, $cat, $discount);
            }

        }
        $this->testMySqlService->endTransaction();

        return $this->json([
            'message' => 'DB was reset'
        ]);
    }


    /**
     * @Route("/getDiscounts/{idProduct}", name="test_mongo")
     */
    public function getPromoMysql(int $idProduct)
    {
        $result = $this->testMySqlService->getProductDiscounts($idProduct);

        return $this->json([
            'message' => 'found ' . count($result) . ' discounts',
            'details' => $result
        ]);
    }
}
