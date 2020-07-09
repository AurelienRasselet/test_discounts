<?php


namespace App;

use MongoDB;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use PDO;

class TestMySqlServiceV2
{
    protected \PDO $mysqlClient;

    public function __construct()
    {
        $this->mysqlClient = new \PDO("mysql:host=mysql;dbname=test_discounts;port=3306", 'root', 'root');
    }

    public function emptyDb(): void
    {
        $this->mysqlClient->exec('DELETE FROM PRODUCTS');
        $this->mysqlClient->exec('DELETE FROM CONDITIONS');
        $this->mysqlClient->exec('DELETE FROM discount');
    }

    public function createTables(): void
    {
        $this->mysqlClient->exec("DROP TABLE IF EXISTS `CONDITIONS`;
            CREATE TABLE `CONDITIONS` (
              `ID` int(11) NOT NULL AUTO_INCREMENT,
              `TARGET` varchar(100) DEFAULT NULL,
              `VALUE` varchar(100) DEFAULT NULL,
              `PARENT_ID` int(11) DEFAULT NULL,
              `ID_DISCOUNT` int(11) DEFAULT NULL,
              `LAST` tinyint(1) DEFAULT 0,
              PRIMARY KEY (`ID`),
              KEY `PARENT_ID` (`PARENT_ID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
            
            
            DROP TABLE IF EXISTS `DISCOUNTS`;
            CREATE TABLE `DISCOUNTS` (
              `id_discount` int(11) NOT NULL AUTO_INCREMENT,
              `value` int(11) DEFAULT NULL,
              PRIMARY KEY (`id_discount`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
            
            
            DROP TABLE IF EXISTS `PRODUCTS`;
            CREATE TABLE `PRODUCTS` (
              `ID` int(11) NOT NULL AUTO_INCREMENT,
              `CATEGORY` int(11) DEFAULT NULL,
              `PRICE` int(11) DEFAULT NULL,
              PRIMARY KEY (`ID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
            
            
            DROP TABLE IF EXISTS `discount`;
            CREATE TABLE `discount` (
              `discount_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(255) NOT NULL,
              `type` ENUM('percentage', 'value'),
              `value` DECIMAL(8,2) DEFAULT NULL,
              `user_ids` JSON DEFAULT NULL,
              `group_ids` JSON DEFAULT NULL,
              `product_ids` JSON DEFAULT NULL,
              `category_ids` JSON DEFAULT NULL,
              `minimal_price` DECIMAL(8,2) DEFAULT NULL,
              `minimal_price_strict` BOOLEAN DEFAULT 0,
              `maximal_price` DECIMAL(8,2) DEFAULT NULL,
              `maximal_price_strict` BOOLEAN DEFAULT 0,
              PRIMARY KEY (`discount_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            
            DROP TABLE IF EXISTS `discount_user_id`;
            CREATE TABLE `discount_user_id` (
              `condition_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,  
              `discount_id` INT UNSIGNED NOT NULL,
              `user_id` INT UNSIGNED DEFAULT NULL,
              `group_id` INT UNSIGNED DEFAULT NULL,
              PRIMARY KEY (`condition_id`),
              UNIQUE (discount_id, user_id, group_id),
              INDEX (discount_id),
              INDEX (user_id, group_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                        
            DROP TABLE IF EXISTS `discount_product_id`;
            CREATE TABLE `discount_product_id` (
                `condition_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, 
              `discount_id` INT UNSIGNED NOT NULL,
              `product_id` INT UNSIGNED DEFAULT NULL,
              `category_id` INT UNSIGNED DEFAULT NULL,
              PRIMARY KEY (`condition_id`),
              UNIQUE (discount_id, product_id, category_id),
              INDEX (discount_id),
              INDEX (product_id, category_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            ");


        print_r($this->mysqlClient->errorInfo());
    }

    public function createProcedure(): void
    {
        $this->mysqlClient->exec('DROP PROCEDURE IF EXISTS `get_discounts`');
        $this->mysqlClient->exec("
            CREATE PROCEDURE `get_discounts`( In id varchar(255))
             BEGIN
                DECLARE counter INT DEFAULT 0;
                
                WHILE counter <= 100 DO
                     SET counter = counter + 1;

                     SELECT discount_id, name, type, value 
                     FROM discount 
                     WHERE ((user_ids IS NULL OR JSON_CONTAINS(user_ids, '1')) AND (group_ids IS NULL OR JSON_CONTAINS(group_ids, '1'))) 
                     AND ((product_ids IS NULL OR JSON_CONTAINS(product_ids, '0')) AND (category_ids IS NULL OR JSON_CONTAINS(category_ids, '1'))) 
                     AND ((minimal_price IS NULL OR minimal_price <= 15) AND (maximal_price IS NULL OR maximal_price >= 15));
                END WHILE;
             END"
        );

        $this->mysqlClient->exec('DROP PROCEDURE IF EXISTS `get_discounts2`');
        $this->mysqlClient->exec("
            CREATE PROCEDURE `get_discounts2`( In id varchar(255))
             BEGIN
                DECLARE counter INT DEFAULT 0;
                
                WHILE counter <= 100 DO
                     SET counter = counter + 1;

                     SELECT discount_id, name, type, value 
                     FROM discount 
                     WHERE ((user_ids IS NULL OR user_ids LIKE '%1%') AND (group_ids IS NULL OR group_ids LIKE '%1%'))
                     AND ((product_ids IS NULL OR product_ids LIKE '%1%') AND (category_ids IS NULL OR category_ids LIKE '%1%')) 
                     AND ((minimal_price IS NULL OR minimal_price <= 15) AND (maximal_price IS NULL OR maximal_price >= 15));
                END WHILE;
             END"
        );
        print_r($this->mysqlClient->errorInfo());

        $this->mysqlClient->exec('DROP PROCEDURE IF EXISTS `get_discounts3`');
        $this->mysqlClient->exec("
            CREATE PROCEDURE `get_discounts3`(in products JSON, out discountsOutput TEXT)
             BEGIN
                DECLARE counter INT DEFAULT 0;
                DECLARE userId INT DEFAULT 0;
                DECLARE groupId INT DEFAULT 0;
                DECLARE categoryId INT DEFAULT 0;
                DECLARE productId INT DEFAULT 0;
                DECLARE price DECIMAL(8,2) DEFAULT 0;       
                
                DECLARE test TEXT DEFAULT '';             
              
                WHILE counter < JSON_LENGTH(products) DO
                     SET userId = JSON_EXTRACT(products, CONCAT('$[', counter, '].userId'));
                     SET groupId = JSON_EXTRACT(products, CONCAT('$[', counter, '].groupId'));
                     SET categoryId = JSON_EXTRACT(products, CONCAT('$[', counter, '].categoryId'));
                     SET productId = JSON_EXTRACT(products, CONCAT('$[', counter, '].productId'));
                     SET price = JSON_EXTRACT(products, CONCAT('$[', counter, '].price'));
                     
                     SELECT GROUP_CONCAT(
                        d.discount_id
                     ) INTO test
                     FROM discount d
                     JOIN discount_user_id dui ON d.discount_id = dui.discount_id 
                        AND ((dui.group_id IS NULL AND dui.group_id IS NULL) OR ((dui.user_id = userId OR dui.user_id IS NULL) AND (dui.group_id = groupId OR dui.group_id IS NULL)))
                     JOIN discount_product_id dpi ON d.discount_id = dpi.discount_id 
                        AND ((dpi.product_id = productId OR dpi.product_id IS NULL) AND (dpi.category_id = categoryId OR dpi.category_id IS NULL));
                        
                     SET counter = counter + 1;
                     SET discountsOutput = CONCAT(
                         IFNULL(discountsOutput, ''),
                         '[',
                          productId,
                         ',',
                         '[',test,']',
                         ']',
                         ','
                     );
                END WHILE;
             END"
        );
        print_r($this->mysqlClient->errorInfo());
    }

    public function createConditions(int $number): void
    {
        $this->mysqlClient->beginTransaction();

        /**
         * User ID + Product ID condition
         */
        for ($i = 0; $i < $number / 2; $i++) {
            $this->mysqlClient->exec("INSERT INTO discount(
                     name, 
                     type, 
                     value, 
                     user_ids, 
                     product_ids, 
                     minimal_price, 
                     minimal_price_strict,
                     maximal_price,
                     maximal_price_strict
                ) VALUES (
                     'userId_productId_condition_$i', 
                     'percentage', 
                     ".rand(1,25).", 
                     '".json_encode([1,2,3,4,5])."', 
                     '".json_encode([$i])."',
                     ".rand(1,20).", 
                     0, 
                     ".rand(21,30).", 
                     1          
                )");

            $discountId = $this->mysqlClient->lastInsertId();

            $this->mysqlClient->exec("INSERT INTO discount_user_id(
                     discount_id, 
                     user_id
                ) VALUES (
                    $discountId, 
                     $i       
                )");

            $this->mysqlClient->exec("INSERT INTO discount_product_id(
                     discount_id, 
                     product_id
                ) VALUES (
                    $discountId, 
                     $i      
                )");
        }

        /**
         * Group ID + Category ID condition
         */
        for ($i = 5000; $i < $number; $i++) {
            $this->mysqlClient->exec("INSERT INTO discount(
                     name, 
                     type, 
                     value, 
                     group_ids, 
                     category_ids, 
                     minimal_price, 
                     minimal_price_strict,
                     maximal_price,
                     maximal_price_strict
                ) VALUES (
                     'userId_productId_condition_$i', 
                     'percentage', 
                     ".rand(1,25).", 
                     '".json_encode([1,2,3])."', 
                     '".json_encode([($i%3)+1])."',
                     ".rand(1,20).", 
                     0, 
                     ".rand(21,30).", 
                     1          
                )");
           $discountId = $this->mysqlClient->lastInsertId();

            $this->mysqlClient->exec("INSERT INTO discount_user_id(
                     discount_id, 
                     group_id
                ) VALUES (
                    $discountId, 
                     ($i%200)+1        
                )");
            $this->mysqlClient->exec("INSERT INTO discount_user_id(
                     discount_id, 
                     group_id
                ) VALUES (
                    $discountId, 
                     ($i%200)+2       
                )");
            $this->mysqlClient->exec("INSERT INTO discount_user_id(
                     discount_id, 
                     group_id
                ) VALUES (
                    $discountId, 
                     ($i%200)+3       
                )");


            $this->mysqlClient->exec("INSERT INTO discount_product_id(
                     discount_id, 
                     category_id
                ) VALUES (
                    $discountId, 
                     ($i%20)+1      
                )");
            $this->mysqlClient->exec("INSERT INTO discount_product_id(
                     discount_id, 
                     category_id
                ) VALUES (
                    $discountId, 
                     ($i%20)+2      
                )");
            $this->mysqlClient->exec("INSERT INTO discount_product_id(
                     discount_id, 
                     category_id
                ) VALUES (
                    $discountId, 
                     ($i%20)+3      
                )");
        }

        $this->mysqlClient->commit();
    }

    public function getProductDiscounts(): array
    {
        /*$sql = '';
        $count = 0;
        while ($count < count($productIds)) {
            if ($count == 0) {
                $sql = "
                    SELECT discount_id, name, type, value
                    FROM discount 
                    WHERE ((user_ids IS NULL OR JSON_CONTAINS(user_ids, '".$userId."'))
                        AND (group_ids IS NULL OR JSON_CONTAINS(group_ids, '".$groupId."')))
                    AND ((product_ids IS NULL OR JSON_CONTAINS(product_ids, '".$productIds[$count]."'))
                        AND (category_ids IS NULL OR JSON_CONTAINS(category_ids, '".$categoryIds[$count]."')))
                    AND ((minimal_price IS NULL OR minimal_price <= ".$prices[$count].")
                        AND (maximal_price IS NULL OR maximal_price >= ".$prices[$count]."))
                ";
            } else {
                $sql .= "UNION  
                    SELECT discount_id, name, type, value
                    FROM discount 
                    WHERE ((user_ids IS NULL OR JSON_CONTAINS(user_ids, '".$userId."'))
                        AND (group_ids IS NULL OR JSON_CONTAINS(group_ids, '".$groupId."')))
                    AND ((product_ids IS NULL OR JSON_CONTAINS(product_ids, '".$productIds[$count]."'))
                        AND (category_ids IS NULL OR JSON_CONTAINS(category_ids, '".$categoryIds[$count]."')))
                    AND ((minimal_price IS NULL OR minimal_price <= ".$prices[$count].")
                        AND (maximal_price IS NULL OR maximal_price >= ".$prices[$count]."))
                ";
            }
            $count++;
        }

        $discountQuery = $this->mysqlClient
            ->prepare($sql);
        $discountQuery->execute(
            [
                //':user_id' => $userId,
                //':group_id' => $groupId,
                //':product_id' => $productId,
                //':category_id' => $categoryId,
                //':price' => $price
            ]
        );
*/

        $products = [];
        for ($i = 1; $i <= 100; $i++) {
            $products[] = [
                'userId' => $i,
                'groupId' => ($i%30)+2,
                'productId' => $i,
                'categoryId' => ($i%30)+2,
                'price' => 15
            ];
        }


        $products = json_encode($products);
        $discountQuery = $this->mysqlClient->prepare("CALL get_discounts3(:products, @discounts)");
        $discountQuery->bindParam(':products', $products, PDO::PARAM_STR);
        $discountQuery->execute();

        //print_r($discountQuery->fetchAll());


        $discountQuery->closeCursor();
        $discountQuery2 = $this->mysqlClient->query("SELECT @discounts AS discounts")->fetchAll();


        //print_r($discountQuery2);
        //print_r($this->mysqlClient->errorInfo());

        //return $discountQuery->fetchAll();
        //print_r($products);
        return json_decode('['.rtrim($discountQuery2[0][0], ',').']');
    }
}