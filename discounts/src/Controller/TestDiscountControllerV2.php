<?php

namespace App\Controller;

use App\TestMongoService;
use App\TestMySqlService;
use App\TestMySqlServiceV2;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TestDiscountControllerV2 extends AbstractController
{
    /**
     * @var TestMySqlServiceV2
     */
    private TestMySqlServiceV2 $testMySqlService;

    public function __construct(TestMySqlServiceV2 $testMySqlService)
    {
        $this->testMySqlService = $testMySqlService;
    }

    /**
     * @Route("/v2/resetDb/{nbDiscount}", name="reset_db_v2")
     */
    public function resetDb(int $nbDiscount)
    {
        $this->testMySqlService->createTables();
        $this->testMySqlService->createConditions($nbDiscount);

        return $this->json([
            'message' => 'DB was reset'
        ]);
    }

    /**
     * @Route("/v2/createProcedure", name="create_procedure")
     */
    public function createProcedure()
    {
        $this->testMySqlService->createProcedure();

        return $this->json([
            'message' => 'Procedure created'
        ]);
    }


    /**
     * @Route("/v2/getDiscounts", name="get_discounts_v2")
     */
    public function getPromoMysql()
    {
        $result = $this->testMySqlService->getProductDiscounts();

        return $this->json($result);
    }
}
