<?php


namespace App;

use MongoDB;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;

class TestMySqlService
{
    protected \PDO $mysqlClient;

    public function __construct()
    {
        $this->mysqlClient = new \PDO("mysql:host=mysql;dbname=test_discounts;port=3306", 'root', 'root');
    }

    public function startTransaction(): void
    {
        $this->mysqlClient->beginTransaction();
    }

    public function endTransaction(): void
    {
        $this->mysqlClient->commit();
    }

    public function emptyCollection(): void
    {
        $this->mysqlClient->exec('DELETE FROM PRODUCTS');
        $this->mysqlClient->exec('DELETE FROM CONDITIONS');
        $this->mysqlClient->exec('DELETE FROM DISCOUNTS');


    }

    public function createProduct(float $price, float $cat, int $idProduct): int
    {
        $this->mysqlClient->exec("INSERT INTO PRODUCTS(ID, CATEGORY, PRICE) VALUES($idProduct, $cat, $price)");
        return $this->mysqlClient->lastInsertId();
    }

    public function createCondition(int $id, float $price, float $cat, float $value): void
    {
        $this->mysqlClient->exec("INSERT INTO DISCOUNTS(value) VALUES($value)");
        $idDiscount = $this->mysqlClient->lastInsertId();

        $this->mysqlClient->exec("INSERT INTO CONDITIONS(TARGET, VALUE, PARENT_ID, ID_DISCOUNT, LAST) 
                                            VALUES('PRODUCT_ID', $id, null, $idDiscount, 0)");
        $idCondition = $this->mysqlClient->lastInsertId();

        $this->mysqlClient->exec("INSERT INTO CONDITIONS(TARGET, VALUE, PARENT_ID, ID_DISCOUNT, LAST) 
                                            VALUES('CATEGORY', $cat, $idCondition, $idDiscount, 0)");
        $idCondition = $this->mysqlClient->lastInsertId();

        $this->mysqlClient->exec("INSERT INTO CONDITIONS(TARGET, VALUE, PARENT_ID, ID_DISCOUNT, LAST) 
                                            VALUES('PRICE', $price, $idCondition, $idDiscount, 1)");

    }

    public function createComplexCondition(int $id, float $price, float $cat, float $value): void
    {
        $this->mysqlClient->exec("INSERT INTO DISCOUNTS(value) VALUES($value)");
        $idDiscount = $this->mysqlClient->lastInsertId();

        $this->mysqlClient->exec("INSERT INTO CONDITIONS(TARGET, VALUE, PARENT_ID, ID_DISCOUNT, LAST) 
                                            VALUES('PRODUCT_ID', $id, null, $idDiscount, 0)");
        $idCondition = $this->mysqlClient->lastInsertId();

        $this->mysqlClient->exec("INSERT INTO CONDITIONS(TARGET, VALUE, PARENT_ID, ID_DISCOUNT, LAST) 
                                            VALUES('CATEGORY', $cat, $idCondition, $idDiscount, 0)");
        $idCondition = $this->mysqlClient->lastInsertId();

        $this->mysqlClient->exec("INSERT INTO CONDITIONS(TARGET, VALUE, PARENT_ID, ID_DISCOUNT, LAST) 
                                            VALUES('PRICE', $price, $idCondition, $idDiscount, 1)");


        $this->mysqlClient->exec("INSERT INTO CONDITIONS(TARGET, VALUE, PARENT_ID, ID_DISCOUNT, LAST) 
                                            VALUES('CATEGORY', $cat - 1, $idCondition, $idDiscount, 0)");
        $idCondition = $this->mysqlClient->lastInsertId();

        $this->mysqlClient->exec("INSERT INTO CONDITIONS(TARGET, VALUE, PARENT_ID, ID_DISCOUNT, LAST) 
                                            VALUES('PRICE', $price - 1, $idCondition, $idDiscount, 1)");

        $this->mysqlClient->exec("INSERT INTO CONDITIONS(TARGET, VALUE, PARENT_ID, ID_DISCOUNT, LAST) 
                                            VALUES('PRODUCT_ID', $id, null, $idDiscount, 0)");
        $this->mysqlClient->exec("INSERT INTO CONDITIONS(TARGET, VALUE, PARENT_ID, ID_DISCOUNT, LAST) 
                                            VALUES('PRODUCT_ID', $id, null, $idDiscount, 0)");
    }

    public function getProductDiscounts(int $idProduct): array
    {
        // Get product infos
        $productQuery = $this->mysqlClient
            ->prepare("SELECT * FROM PRODUCTS WHERE ID = :id");
        $productQuery->execute([':id' => $idProduct]);
        $product = $productQuery->fetch();

        // Get all product discounts
        $discounts = $this->mysqlClient->query(
                "WITH RECURSIVE CONDITIONS_TREE(ID, TARGET, VALUE, ID_DISCOUNT, LAST)
                AS
                (
                    SELECT ID, TARGET, VALUE, ID_DISCOUNT, LAST
                    FROM CONDITIONS
                    WHERE PARENT_ID IS NULL
                    AND ((TARGET = 'PRODUCT_ID' AND VALUE = '".$product['ID']."')
                    OR (TARGET = 'CATEGORY' AND VALUE = '".$product['CATEGORY']."')
                    OR (TARGET = 'PRICE' AND VALUE = '".$product['PRICE']."'))
                    UNION ALL
                    SELECT S.ID, S.TARGET, S.VALUE, S.ID_DISCOUNT, S.LAST
                    FROM CONDITIONS_TREE M JOIN CONDITIONS S ON M.ID=S.PARENT_ID
                    WHERE ((S.TARGET = 'PRODUCT_ID' AND S.VALUE = '".$product['ID']."')
                    OR (S.TARGET = 'CATEGORY' AND S.VALUE = '".$product['CATEGORY']."')
                    OR (S.TARGET = 'PRICE' AND S.VALUE = '".$product['PRICE']."'))
                )
                SELECT ID_DISCOUNT, VALUE FROM DISCOUNTS WHERE ID_DISCOUNT IN (SELECT ID_DISCOUNT FROM CONDITIONS_TREE WHERE LAST = 1)"
            );
            //print_r($this->mysqlClient->errorInfo());

        return $discounts->fetchAll();
    }

    public function getProductsDiscounts($nbProducts): void
    {
        $products = $this->mysqlClient->query("SELECT * FROM PRODUCTS LIMIT 0, " . $nbProducts);
        $allDiscounts = [];
        $countProducts = 0;
        foreach ($products as $product) {
            $discounts = $this->mysqlClient->query(
                "WITH RECURSIVE CONDITIONS_TREE(ID, TARGET, VALUE, ID_DISCOUNT, LAST)
                AS
                (
                    SELECT ID, TARGET, VALUE, ID_DISCOUNT, LAST
                    FROM CONDITIONS
                    WHERE PARENT_ID IS NULL
                    AND ((TARGET = 'PRODUCT_ID' AND VALUE = '".$product['ID']."')
                    OR (TARGET = 'CATEGORY' AND VALUE = '".$product['CATEGORY']."')
                    OR (TARGET = 'PRICE' AND VALUE = '".$product['PRICE']."'))
                    UNION ALL
                    SELECT S.ID, S.TARGET, S.VALUE, S.ID_DISCOUNT, S.LAST
                    FROM CONDITIONS_TREE M JOIN CONDITIONS S ON M.ID=S.PARENT_ID
                    WHERE ((S.TARGET = 'PRODUCT_ID' AND S.VALUE = '".$product['ID']."')
                    OR (S.TARGET = 'CATEGORY' AND S.VALUE = '".$product['CATEGORY']."')
                    OR (S.TARGET = 'PRICE' AND S.VALUE = '".$product['PRICE']."'))
                )
                SELECT ID_DISCOUNT, VALUE FROM DISCOUNTS WHERE ID_DISCOUNT IN (SELECT ID_DISCOUNT FROM CONDITIONS_TREE WHERE LAST = 1)"
            );
            //print_r($this->mysqlClient->errorInfo());

            $allDiscounts = array_merge(
                $discounts->fetchAll(),
                $allDiscounts
            );

            $countProducts++;
        }

       echo 'found ' . count($allDiscounts) . ' discounts for ' . $countProducts . ' products';
    }

    public function creationObjects(): void
    {
        $collection = $this->mongoClient->test->discount;

        $collection->insertMany([
            [
                '_id' => new ObjectId($this->mongoIds[0]),
                'value' => '15%',
                'target' => 'product',
                'start' => time(),
                'end'  => time() + 1800,
                'conditions' => []
            ],
            [
                '_id' => new ObjectId($this->mongoIds[1]),
                'value' => '25%',
                'target' => 'product',
                'start' => time(),
                'end'  => time() + 3600,
                'conditions' => []
            ],
            [
                '_id' => new ObjectId($this->mongoIds[2]),
                'value' => '1.75%',
                'target' => 'product',
                'start' => time(),
                'end'  => time() + 4400,
                'conditions' => []
            ],
        ]);
    }

    public function updateConditions(): void
    {
        $collection = $this->mongoClient->test->discount;

        $collection->updateOne(
            ['_id' => new ObjectId($this->mongoIds[0])],
            [
                '$set' => ['conditions' =>
                    [
                        [
                            'target' => 'category',
                            'value' => 2,
                            'conditions' => [['target' => 'color', 'value' => 'red'], ['target' => 'color', 'value' => 'blue']],
                        ],
                        [
                            'target' => 'category',
                            'value' => 1,
                        ]
                    ]
                ]
            ]
        );
        $collection->updateOne(
            ['_id' => new ObjectId($this->mongoIds[1])],
            [
                '$set' => ['conditions' =>
                    [
                        [
                            'target' => 'color',
                            'value' => 'green',
                        ],
                        [
                            'target' => 'category',
                            'value' => 2,
                        ]
                    ]
                ]
            ]
        );
        $collection->updateOne(
            ['_id' => new ObjectId($this->mongoIds[2])],
            [
                '$set' => ['conditions' =>
                    [
                        [
                            'target' => 'color',
                            'value' => 'green',
                        ],
                    ]
                ]
            ]
        );
    }

    public function parseDiscountsRecursive(array $product, Object $conditions): bool
    {
        foreach ($conditions as $condition) {

            if ($condition['value'] != $product[$condition['target']]) {
                // If the condition is not validated, we don't need to go deeper into recursivity
                // So we test the next condition
                continue;
            } else {
                if (true === isset($condition['conditions'])) {
                    // The condition is validated, but there are more conditions to test
                    // (the condition has condition(s))
                    return $this->parseDiscountsRecursive($product, $condition['conditions']);
                } else {
                    // The condition is validated, and there is no more condition = we can return true
                    return true;
                }
            }
        }

        // There is no validated condition, we return false
        return false;
    }

    // List discounts for a product
    public function parseDiscounts(array $product): array
    {
        $discounts = [];
        $collection = $this->mongoClient->test->discount;
        $cursor = $collection->find();

        foreach ($cursor as $discount) {
            $conditions = $discount['conditions'];

            if (true === $this->parseDiscountsRecursive($product, $conditions)) {
                $discounts[] =  $discount['_id'];

                // Affiche
                // Discount found: 5a2493c33c95a1281836eb6a
                // Discount found: 5a2493c33c95a1281836eb6b
            }
        }

        return $discounts;
    }
}