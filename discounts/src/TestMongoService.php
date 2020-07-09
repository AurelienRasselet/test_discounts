<?php


namespace App;

use MongoDB;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;

class TestMongoService
{
    protected array $mongoIds = ['5a2493c33c95a1281836eb6a', '5a2493c33c95a1281836eb6b', '5a2493c33c95a1281836eb6c'];

    /** @var Client */
    protected Client $mongoClient;

    public function __construct()
    {
        // En pratique déclarer le client en tant que service et utiliser l'injection
        $this->mongoClient = new Client('mongodb://root:root@mongo');
    }

    public function emptyCollection(): void
    {
        $this->mongoClient->test->discount->drop();
        $this->mongoClient->test->product->drop();
    }

    public function createProduct(float $price, float $cat): ObjectId
    {
        return $this->mongoClient->test->product->insertOne(
            [
                'category' => $cat,
                'price' => $price
            ]
        )->getInsertedId();
    }

    public function createCondition(int $id, float $price, float $cat, float $value): void
    {
        $this->mongoClient->test->discount->insertOne(
            [
                'value' => $value,
                'target' => 'product',
                'start' => time(),
                'end'  => time() + 1800,
                'conditions' => [
                    [
                        'target' => '_id',
                        'value' => $id,
                        'conditions' => [
                            [
                                'target' => 'category',
                                'value' => $cat,
                                'conditions' => [
                                    [
                                        'target' => 'price',
                                        'value' => $price
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    public function createComplexCondition(int $id, float $price, float $cat, float $value): void
    {
        $this->mongoClient->test->discount->insertOne(
            [
                'value' => $value,
                'target' => 'product',
                'start' => time(),
                'end'  => time() + 1800,
                'conditions' => [
                    [
                        'target' => '_id',
                        'value' => $id,
                        'conditions' => [
                            [
                                'target' => 'category',
                                'value' => $cat,
                                'conditions' => [
                                    [
                                        'target' => 'price',
                                        'value' => $price
                                    ]
                                ],
                            ],
                            [
                                'target' => 'category',
                                'value' => $cat - 1,
                                'conditions' => [
                                    [
                                        'target' => 'price',
                                        'value' => $price - 1
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'target' => '_id',
                        'value' => $id
                    ],
                    [
                        'target' => '_id',
                        'value' => $id
                    ],
                ]
            ]
        );
    }

    public function getProductsDiscounts($nbProducts): void
    {
        $products = $this->mongoClient->test->product->find();
        $discounts = [];
        $countProducts = 0;
        foreach ($products as $product) {
            $discounts = array_merge(
                $discounts,
                $this->parseDiscounts(['_id'=> $product->_id, 'category' => $product->category, 'price' => $product->price])
            );
            $countProducts++;

            if ($countProducts >= $nbProducts) {
                break;
            }
        }

        echo 'found ' . count($discounts) . ' discounts for ' . $countProducts . ' products';
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