<?php
    # MESSAGE FROM CHAT
    
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    header('Access-Control-Allow-Origin: *');
    
    #decomposing task 
    $bot = new Bot($env['bot_token']);

    #case: added user
    if(isset($mes['message']['new_chat_participant'])){
        #check if this user not in group yet, then add him to database table chat_users and greet in chat
        $id = $mes['message']['new_chat_participant']['id'];
        $user = new UserDb($id, $pdo);
        
        $isAlreadyInChat = $user->getValues(['telegram_id'], 'chat_users');
        
        if(!isset($isAlreadyInChat[0])){

            $userTgInfo = $user->getValues(['first_name', 'last_name', 'username'], 'users')[0];
            $middleUser = $user->getValues(['*'], 'middle_users')[0];

            $name = $middleUser['name'];
            $smm = $middleUser['smm'];
            $groupId = $middleUser['group_id'];
            $img = $middleUser['img'];
            $job = $middleUser['job'];
            $nick = $middleUser['forum_nick'];
            
            $textToSend = "(greeting text)";

            if($job != "skip(#hash#)5"){
                $textToSend = $textToSend . ",\n📍Сфера деятельности - $job";
            }
            if($nick != "skip(#hash#)8"){
                $textToSend = $textToSend . ",\n📍Ник на форуме - $nick";
            }

            $bot->sendPhoto(['chat_id' => $mes['message']['chat']['id'], 'photo' => $img]);

            $keyboard = json_encode(['inline_keyboard' => [/*some buttons with info or stuff (searching on the website, kind of group rules, the closest event)*/]]);
            $bot->sendMessage(['chat_id' => $mes['message']['chat']['id'], "text" => $textToSend, 'reply_markup'=> $keyboard]);

            $user->insertValues(['name', 'telegram_id', 'first_name', 'last_name', 'username', 'phone', 'vehicle', 'vehicle_number', 'job', 'group_id', 'smm', 'forum_nick', 'img', 'status'], [$middleUser['name'], $id, $userTgInfo['first_name'], $userTgInfo['last_name'], $userTgInfo['username'], $middleUser['phone'], $middleUser['vehicle'], $middleUser['vehicle_number'], $middleUser['job'], $groupId, $middleUser['smm'], $middleUser['forum_nick'], $middleUser['img'], 'member'], 'chat_users');
            $user->delete('middle_users');

            sendUrls($id, "Для лучшей коммуникации в чате, рекомендуем вам ознакомиться с правилами общения в чате по *ссылке ниже*, а также с полезными командами, которые доступны по вводу палочки */* в чате", "правила", "(link to rules)", $bot);
        } else {
            #otherwise just update his status to member
            $user->updateValues(['status' => 'member'], 'chat_users');
        }
    #case: user left
    } else if(isset($mes['message']['left_chat_participant'])){
        #check if this user was blocked by admin or he`ve just lived the group
        $mes = $mes['message'];
        $id = $mes['left_chat_participant']['id'];
        $user = new UserDb($id, $pdo);

        if(isAdmin($mes['from']['id'], $pdo)){
            #in first case send him the notification of ban
            $user->updateValues(['ban_reason' => "ban in chat"], 'users');
            $user->delete('chat_users');    
            $bot->sendMessage(['chat_id' => $id, 'text' => 'вы были забанены *администратором* без возможности дальнейшего входа в группу']);
        } else {
            # in second case send him the notification that he`ve left the chat
            $user->updateValues(['status' => 'left'], 'chat_users');
            $bot->sendMessage(['chat_id' => $id, 'text' => 'вы вышли из группы, чтобы зайти обратно напишите команду /getlink']);
        }

    } else {
        #the rest of queries about some stuff buttons in group
        if(isset($mes['callback_query'])){

            $command = $mes['callback_query']['data'];
            $chat_id = $mes['callback_query']['message']['chat']['id'];
            $user_id = $mes['callback_query']['from']['id'];

        } else if (isset($mes['message']['entities'][0]['type'])) {

            $command = parseBotCommand($mes['message']['text']);
            $chat_id = $mes['message']['chat']['id'];
            $user_id = $mes['message']['from']['id'];

        }
        #some exsamples of buttons
        switch($command){
            case '/rules':
                sendUrls($chat_id, "Ссылка на правила сообщества", "перейти", "link to rules", $bot);
                break;
            case '/registration':
                sendUrls($user_id, "ссылка на регистрацию", "перейти", "link to registration on website", $bot);
                break;
            case '/partners':
                sendUrls($chat_id, "Ссылка на партнёров", "перейти", "link to chat`s partners", $bot);
                break;
            case '/search':
                #search logic
                $user = new UserDb($user_id, $pdo);
                $bot->sendMessage(["chat_id" => $user_id, 'text' => "что вы ищете?"]);
                $user->updateValues(["is_searching" => 1], 'chat_users');
                break;
            case '/closevent':
                $res = $pdo->query("SELECT * FROM `events` WHERE `is_on` = 1");
                $eventData = [];
                while($row = $res->fetch()){
                    $eventData[] = $row;
                };
                if(isset($eventData[0])){
                    $eventData = $eventData[0];
                    $title = $eventData['title'];
                    $desc = $eventData['description'];
                    $date = $eventData['date'];
                    $price = $eventData['price'];
                    $img = $eventData['img_url'];
                    $textToSend = "[ ]($img)Ближайшее событие:\n*$title* \n - $desc\n*дата проведения*: $date\n*стоимость посещения*: $price\n\n *Ждём вас на мероприятии!*";
                    sendUrls($chat_id, $textToSend, "посмотреть на форуме", $eventData['url'], $bot);
                } else {
                    $bot->sendMessage(['chat_id' => $chat_id, "text" => "На ближайшее время нет запланированных событий"]);
                }
                break;
        }
    }

    #function to parse bot command string
    function parseBotCommand($string){
        return explode("@", $string)[0];
    }

    #function to send response to info stuff  
    function sendUrls($id, $text, $board_text, $url, $bot){
        $keyboard = makeInlineKeyboard([['text' => $board_text, "url" => $url]]);
        $bot->sendMessage(["chat_id" => $id, "text" => $text, "reply_markup" => $keyboard]);
    }
    
    #function that is check if this user is admin in group or not
    function isAdmin($id, $pdo){
        $admins = [];
        $res = $pdo->query("SELECT `telegram_id` FROM `chat_users` WHERE `status` = 'admin'");
        while($row = $res->fetch()){
            $admins[] = $row;
        }
        for($i = 0; $i < count($admins); $i++){
            if($admins[$i]['telegram_id'] == $id){
                return true;
            }
        }
        return false;
    }
?>