<?php
    #MAIN WORKERS

    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    header('Access-Control-Allow-Origin: *');
    require_once('config/config.php');

     #bot class (main function - sending some type of message)
    class Bot {
        private $botUrl;

        public function __construct($token){
            #set up bot token
            $this->botUrl = "https://api.telegram.org/bot$token";
        }

        #send default message
        public function sendMessage($params){
            $curl = $this->setUpCurl('sendMessage', $params);
            $res = curl_exec($curl);
            return $res;    
        }

        #generate link to certain chat
        public function createInviteLink($params){
            $curl = $this->setUpCurl('createChatInviteLink', $params);
            $res = curl_exec($curl);
            return json_decode($res, true)["result"]["invite_link"];    
        }

        #send photo 
        public function sendPhoto($params){
            $curl = $this->setUpCurl('sendPhoto', $params);
            $res = curl_exec($curl);
            return json_decode($res, true);
        }

        private function setUpCurl($method, $params){
            $methodUrl = "$this->botUrl/$method";
            $params = array_merge($params, ['parse_mode' => 'markdown']);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $methodUrl, 
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($params)
            ));
            return $curl;
        }
    };

    #database worker
    class UserDb {

        private $id;
        private $pdo;

        function __construct($id, $pdo){
            #set up user to work with and db connection
            $this->id = $id;
            $this->pdo = $pdo;
        }

        #default method to get values from certain table
        public function getValues($fields, $table){

            $sql = "SELECT";
            $fieldsLen = count($fields);

            foreach($fields as $field){
                if(array_search($field, $fields) == $fieldsLen - 1){
                    if($field == "*"){
                        $sql = $sql . " $field";
                    } else {
                        $sql = $sql . " `$field`";
                    }
                } else {
                    $sql = $sql . " `$field`,";
                }
            };

            $sql = $sql . " FROM `$table` WHERE `telegram_id` = $this->id";
            try {
                $res = $this->pdo->query($sql);

                $data = [];

                while($row = $res->fetch()){
                    $data[] = $row;
                };

                return $data;

            } catch (PDOException $e){
                
                echo $e;
                return 0;

            };
        }

        #method to delete user from certain table
        public function delete($table){
            try {

                $sql = "DELETE FROM `$table` WHERE `telegram_id` = $this->id";
                $this->pdo->query($sql);

            } catch (PDOException $e) {

                echo $e;
                return $e;

            };
        }
        
        #default method to insert data in certain table
        public function insertValues($fields, $values, $table){

            $sql = "INSERT INTO `$table`(";
            $fieldsLen = count($fields);

            foreach($fields as $field){
                if(array_search($field, $fields) == $fieldsLen - 1){
                    $sql = $sql . " `$field`";
                } else {
                    $sql = $sql . " `$field`,";
                };
            };

            $sql = $sql . ") VALUES(";
            $valuesLen = count($values);
            foreach($values as $value){
                if(array_search($value, $values) == $valuesLen - 1){
                    $sql = $sql . " '$value'";
                } else {
                    $sql = $sql . " '$value',";
                }
            };

            $sql = $sql . ")";
            
            echo $sql;
            try {

                $this->pdo->query($sql);

            } catch (PDOException $e){

                echo $e;
                return 0;

            };

        }

        #default method to update data in certain table and fields
        public function updateValues($arr, $table){
            $sql = "UPDATE `$table` SET";
            $arrLen = count($arr);
            $i = 0;
            foreach($arr as $key => $value){
                $i++;
                if($arrLen == $i){
                    echo $value;
                    $sql = $sql . " `$key` = '$value'";
                } else {
                    $sql = $sql . " `$key` = '$value',";
                };
            };
            
            $sql = $sql . " WHERE `telegram_id` = $this->id";

            try {

                $this->pdo->query($sql);

            } catch (PDOException $e){

                echo $e;
                return 0;

            };
        }
    };

    ##some beneficial stuff 

    #making inline keyboard that will be attached to message
    function makeInlineKeyboard($arr){
        return json_encode(['inline_keyboard' => [$arr]]);                                     
    };

    #validate empty fields
    function validateFields($arr) {
        $res = [];
        foreach($arr as $key => $value){
            if($value == null){
                $arr[$key] = "empty$key";
            };
            array_push($res, $arr[$key]);
        };
        return $res;
    };
?>