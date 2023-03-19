<?php
    #USER VALIDATION  
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    header('Access-Control-Allow-Origin: *');
    
    #this function take the responsobility of the sending user`s appeal to admin
    function sendValidationToAdmin($bot, $user){
        
        include 'secret.php';

        #generate the good-looking appeal
        $middleUser = $user->getValues(['*'], 'middle_users')[0];
                  
        #some answers which will be written in database
        $id = $middleUser['telegram_id'];
        $name = $middleUser['name'];
        $phone =  $middleUser['phone'];
        $job = $middleUser['job'];
        $city = $middleUser['city'];
        $smm = $middleUser['smm'];
        $img = $middleUser['img'];
        #..... 

        $keyboard = makeInlineKeyboard([['text' => "отказать в доступе", "callback_data" => "/rejected$#%$id"], ["text" => "одобрить", "callback_data" => "/approved$#%$id"]]);

        $textToSend = "(text of appliccation that will be sent to admin)";
       
        $bot->sendPhoto(['chat_id' => $env['admin_id'], 'photo' => $img]);
        $bot->sendMessage(['chat_id' => $env['admin_id'], 'text' => $textToSend, 'reply_markup' => $keyboard]);
        //57194698
        $user->updateValues(['is_passed' => 1], 'users');

    };

    #this function handle validation response
    function handleValidationResponse($mes, $pdo, $bot){

        include 'secret.php';

        $res = explode("$#%", $mes)[0];
        $id = explode("$#%", $mes)[1];
        $user = new UserDb($id, $pdo);
        #case - user approved
        if($res == '/approved'){
            #generate link to group
            $groupId = $user->getValues(['group_id'], 'middle_users')[0];
            
            $inviteLink = $bot->createInviteLink(['chat_id' => intval($groupId['group_id']), 'expire_date' => time() + 600, "members_limit" => 1]); 
            $textToSend = "Поздравляем!, Вы успешно завершили опрос, присоеденитесь к чату по этой ссылке (срок действия пригласительной ссылки - 10 минут)\n $inviteLink";
            
            $bot->sendMessage(['chat_id' => $id, 'text' => $textToSend]);
    
            $user->updateValues(['is_confirmed' => 1], 'users');
            
            $bot->sendMessage(['chat_id' => $env['admin_id'], 'text' => "одобренно"]);

        } else {
            #else ban user fot 2 weeks
            $user->delete('middle_users');
            $user->updateValues(['last_time_test' => time(), 'ban_reason' => 'admin', 'test_round' => 0, 'is_passed' => 0], 'users');

            $textToSend = "К сожалению администратор не одобрил вашу заявку по тем или иным причинам, мы вынуждены заблокировать вас в системе на 2 недели";
            $bot->sendMessage(['chat_id' => $id, 'text' => $textToSend]);

            $bot->sendMessage(['chat_id' => $env['admin_id'], 'text' => "пользователь заблокирован на 2 недели"]);

        }
    }
?>
