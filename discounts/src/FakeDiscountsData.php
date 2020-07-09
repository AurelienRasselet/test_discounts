<?php


namespace App;

use MongoDB\BSON\ObjectId;
use MongoDB\Client;

class FakeDiscountsData
{
    public const ATTRIBUTES = [
        'price' => ['0', '1', '5', '10', '20', '30', '50', '100', '120', '150', '200', '300'],
        'id_product' => ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
    ];
}