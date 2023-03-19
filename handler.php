<?php 
   #MAIN handler
   ini_set('error_reporting', E_ALL);
   ini_set('display_errors', 1);
   ini_set('display_startup_errors', 1);

   header('Access-Control-Allow-Origin: *');

   require_once('config/config.php');
   require_once('system.php');
   require_once('secret.php');

   #this code part defines from where the message was sent 
   $mes = json_decode(file_get_contents('php://input'), true);
   if(isset($mes['message']['chat']['id'])){
   #send request to chat`s handler
   if($mes['message']['chat']['id'] < 0){
      include 'chat/index.php';
   } else {
      include 'direct/index.php';
   } #callback case
   } else if(isset($mes['callback_query']['message']['chat']['id'])){
      if($mes['callback_query']['message']['chat']['id'] < 0){
         echo "okey";
         include 'chat/index.php';
      } else {
         include 'direct/index.php';
      }
   }
?>