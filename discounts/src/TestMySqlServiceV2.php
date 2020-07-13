<?php


namespace App;

use Symfony\Component\Uid\Uuid;
use PDO;

class TestMySqlServiceV2
{
    protected \PDO $mysqlClient;

    public function __construct()
    {
        $this->mysqlClient = new \PDO(
            "mysql:host=mysql;dbname=test_discounts;port=3306",
            'root',
            'root',
            [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'"]
        );
        $this->mysqlClient->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->mysqlClient->exec("SET SESSION group_concat_max_len = 5000;");
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
            
            DELETE from discount;
            DROP TABLE IF EXISTS `discount`;
            CREATE TABLE `discount` (
              `discount_id` BINARY(16) NOT NULL,
              `company_id` INT,
              `name` VARCHAR(255) NOT NULL,
              `type` ENUM('marketplace', 'basket', 'product'),
              `bonus` JSON DEFAULT NULL,
              `product_min_price_inclusive` DECIMAL(8,2) DEFAULT NULL,
              `product_min_price_exclusive` DECIMAL(8,2) DEFAULT NULL,
              `product_max_price_inclusive` DECIMAL(8,2) DEFAULT NULL,
              `product_max_price_exclusive` DECIMAL(8,2) DEFAULT NULL,
              `start_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `end_date` DATETIME DEFAULT NULL,
              PRIMARY KEY (`discount_id`),
              INDEX (discount_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            
            DROP TABLE IF EXISTS `discount_user_id`;
            CREATE TABLE `discount_user_id` (
              `condition_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,  
              `discount_id` BINARY(16) NOT NULL,
              `user_id` binary(16) DEFAULT NULL,
              `group_id` binary(16) DEFAULT NULL,
              PRIMARY KEY (`condition_id`),
              UNIQUE (discount_id, user_id, group_id),
              INDEX (user_id, group_id),
              FOREIGN KEY (discount_id) REFERENCES discount (discount_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                        
            DROP TABLE IF EXISTS `discount_product_id`;
            CREATE TABLE `discount_product_id` (
              `condition_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, 
              `discount_id` BINARY(16) NOT NULL,
              `product_id` INT UNSIGNED DEFAULT NULL,
              `category_id` INT UNSIGNED DEFAULT NULL,
              PRIMARY KEY (`condition_id`),
              UNIQUE (discount_id, product_id, category_id),
              INDEX (product_id, category_id),
              FOREIGN KEY (discount_id) REFERENCES discount (discount_id) ON DELETE CASCADE
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
            CREATE PROCEDURE `get_discounts3`(IN products JSON, OUT discountsOutput JSON)
             BEGIN
                DECLARE counter INT DEFAULT 0;
                DECLARE companyId INT DEFAULT 0;
                DECLARE userId INT DEFAULT 0;
                DECLARE groupId INT DEFAULT 0;
                DECLARE categoryId INT DEFAULT 0;
                DECLARE productId INT DEFAULT 0;
                DECLARE price DECIMAL(8,2) DEFAULT 0;       
                
                DECLARE tempOutput1 TEXT DEFAULT '';       
                DECLARE tempOutput2 TEXT DEFAULT '';         
              
                WHILE counter < JSON_LENGTH(products) DO
                     -- Extract data from the JSON
                     SET companyId = JSON_EXTRACT(products, CONCAT('$[', counter, '].companyId'));
                     SET userId = JSON_EXTRACT(products, CONCAT('$[', counter, '].userId'));
                     SET groupId = JSON_EXTRACT(products, CONCAT('$[', counter, '].groupId'));
                     SET categoryId = JSON_EXTRACT(products, CONCAT('$[', counter, '].categoryId'));
                     SET productId = JSON_EXTRACT(products, CONCAT('$[', counter, '].productId'));
                     SET price = JSON_EXTRACT(products, CONCAT('$[', counter, '].price'));
                     
                     -- Test if we want to filter by user/group
                     IF userId IS NULL AND groupId IS NULL THEN
                        SELECT GROUP_CONCAT(
                            DISTINCT d.bonus
                        ) INTO tempOutput1
                        FROM discount d
                        JOIN discount_product_id dpi ON d.discount_id = dpi.discount_id 
                            AND 
                                (dpi.product_id = productId OR dpi.product_id IS NULL) AND (dpi.category_id = categoryId OR dpi.category_id IS NULL)
                         WHERE type = 'product'
                           AND d.company_id = companyId
                           AND d.start_date >= UTC_TIMESTAMP
                           AND (d.end_date IS NULL OR d.end_date <= UTC_TIMESTAMP)
                           AND (
                                (price >= product_min_price_inclusive OR product_min_price_inclusive IS NULL) AND 
                                (price > product_min_price_exclusive OR product_min_price_exclusive IS NULL) AND
                                (price <= product_max_price_inclusive OR product_max_price_inclusive IS NULL) AND 
                                (price < product_max_price_exclusive OR product_max_price_exclusive IS NULL)
                           );  
                     ELSE
                        SELECT GROUP_CONCAT(
                            DISTINCT d.bonus
                        ) INTO tempOutput1
                        FROM discount d
                        JOIN discount_user_id dui ON d.discount_id = dui.discount_id 
                            AND
                                (dui.user_id = userId OR dui.user_id IS NULL) AND (dui.group_id = groupId OR dui.group_id IS NULL)
                        JOIN discount_product_id dpi ON d.discount_id = dpi.discount_id 
                            AND 
                                (dpi.product_id = productId OR dpi.product_id IS NULL) AND (dpi.category_id = categoryId OR dpi.category_id IS NULL)
                         WHERE type = 'product'
                           AND d.company_id = companyId
                           AND UTC_TIMESTAMP >= d.start_date 
                           AND (d.end_date IS NULL OR UTC_TIMESTAMP <= d.end_date)
                           AND (
                                (product_min_price_inclusive IS NULL OR price >= product_min_price_inclusive) AND 
                                (product_min_price_exclusive IS NULL OR price > product_min_price_exclusive) AND
                                (product_max_price_inclusive IS NULL OR price <= product_max_price_inclusive) AND 
                                (product_max_price_exclusive IS NULL OR price < product_max_price_exclusive)
                           ) LIMIT 0,1; 
                     END IF;
      
                     SET counter = counter + 1;
                     SET tempOutput2 = CONCAT(
                         IFNULL(tempOutput2, ''),
                         '[',
                          productId,
                         ',',
                         '[',IFNULL(tempOutput1, ''),']',
                         ']',
                         ','
                     );
                END WHILE;
                
                SET discountsOutput = CONCAT('[', TRIM(TRAILING ',' FROM tempOutput2), ']');
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
            $discountId = Uuid::v4();
            $this->mysqlClient->exec("INSERT INTO discount(
                     discount_id,
                     name, 
                     type,
                     company_id,
                     bonus, 
                     product_min_price_inclusive, 
                     product_max_price_inclusive
                ) VALUES (
                     UUID_TO_BIN('$discountId'),
                     'userId_productId_condition_$i', 
                     'product', 
                     5, 
                     '".json_encode(['value' => rand(1000,2500)/100, 'type' => 'percentage'])."',
                     ".rand(1,20).",  
                     ".rand(21,30)."        
                )");

            $this->mysqlClient->exec("INSERT INTO discount_user_id(
                     discount_id, 
                     user_id
                ) VALUES (
                    UUID_TO_BIN('$discountId'),
                     $i+1    
                )");

            $this->mysqlClient->exec("INSERT INTO discount_product_id(
                     discount_id, 
                     product_id
                ) VALUES (
                    UUID_TO_BIN('$discountId'),
                     $i+1   
                )");
        }

        /**
         * Group ID + Category ID condition
         */
        for ($i = 5000; $i < $number; $i++) {
            $discountId = Uuid::v4();

            $this->mysqlClient->exec("INSERT INTO discount(
                     discount_id,
                     name, 
                     type, 
                     company_id,
                     bonus, 
                     product_min_price_inclusive, 
                     product_max_price_inclusive
                ) VALUES (
                     UUID_TO_BIN('$discountId'),
                     'userId_productId_condition_$i', 
                     'product', 
                      5,
                     '".json_encode(['value' => rand(1000,2500)/100, 'type' => 'percentage'])."',
                     ".rand(1,20).", 
                     ".rand(21,30)."        
                )");

            $this->mysqlClient->exec("INSERT INTO discount_user_id(
                     discount_id, 
                     group_id
                ) VALUES (
                    UUID_TO_BIN('$discountId'),
                     ($i%200)+($i%3)        
                )");

            $this->mysqlClient->exec("INSERT INTO discount_product_id(
                     discount_id, 
                     category_id
                ) VALUES (
                    UUID_TO_BIN('$discountId'),
                     ($i%20)+($i%2)   
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
        for ($i = 0; $i <= 100; $i++) {
            $products[] = [
                'userId' => $i,
                'groupId' => ($i%30)+2,
                'productId' => $i,
                'categoryId' => ($i%50)+2,
                'price' => 15,
                'companyId' => 5
            ];
        }


        $products = json_encode($products);
        $discountQuery = $this->mysqlClient->prepare("CALL get_discounts3(:products, @discounts)");
        $discountQuery->bindParam(':products', $products, PDO::PARAM_STR);
        $discountQuery->execute();

        //print_r($discountQuery);
        //print_r($this->mysqlClient->errorInfo());


        $discountQuery->closeCursor();
        $discountQuery2 = $this->mysqlClient->query("SELECT @discounts AS discounts")->fetchAll();

        //print_r($discountQuery2);
        //print_r($this->mysqlClient->errorInfo());

        //return $discountQuery->fetchAll();
        //print_r($products);
        return json_decode($discountQuery2[0][0]);
    }
}