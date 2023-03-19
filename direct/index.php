<?php
    #MESSAGE FROM PRIVATE CHAT
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    header('Access-Control-Allow-Origin: *');
    
    require_once('direct/validationUser.php');
    require_once('direct/poll.php');

    #create bot object to send messages and stuff to user
    $bot = new Bot($env['bot_token']);

    #sort out what type of message it is
    $is_callback = 0;
    $is_photo = 0;

    if(isset($mes['callback_query'])){
        #callback case
        $is_callback = 1;
        $mesText = $mes['callback_query']['data'];

    } else if(isset($mes['message']['photo'])) {
        #if message - photo then save its unique identifier
        $is_photo = 1;
        $mesText = $mes['message']['photo'][0]['file_id'];
    } else {
        #otherwise check if user is searching for smt or nor
        $mes = $mes['message'];

        $user = new UserDB($mes['chat']['id'], $pdo);
        $is_searching = $user->getValues(['is_searching'], 'chat_users');

        if(isset($is_searching[0])){

            if($is_searching[0]['is_searching'] == 1){
                $mesText = "search";
                $searchInfo = $mes['text'];

            } else {
                $mesText = $mes['text'];
            }
        } else {

            $mesText = $mes['text'];
        }
    };

    switch($mesText){
        case '/start':
            #in case of message = start, we greet user
            try{
                #add user to common users database

                $user = new UserDB($mes['chat']['id'], $pdo);

                $toWrite = validateFields(['telegram_id' => $mes['from']['id'], "first_name" => $mes['from']['first_name'], "last_name" => $mes['from']['last_name'], "username" => $mes['from']['username']]);

                $user->insertValues(['telegram_id', 'first_name', 'last_name', 'username'], $toWrite, 'users');

                #send greeting to user in telegram
                $textToSend = "[ ](img url)greeting to user in private chat";

                $keyboard = makeInlineKeyboard([["text" => "пройти опрос", "callback_data" => "/test"]]);
                $res = $bot->sendMessage(['chat_id' => $mes['chat']['id'], 'text' => $textToSend, 'reply_markup' => $keyboard]);

                if(json_decode($res, true)['ok'] == true){
                    $resText = ['ok' => 'true', 'message' => $res];
                    echo json_encode($resText);
                } else
                    $resText = ['ok' => 'false', 'error' => $res];
                    echo json_encode($resText);

                break;

            } catch(PDOException $e){

                $res = ['ok' => 'false', 'error' => $e];
                echo json_encode($res);
                break;

            } catch (Exception $e){

                $res = ['ok' => 'false', 'error' => 'could not add user'];
                echo json_encode($res);
                break;

            };
        #in case of searching we send the prepared response
        case "search":
            $keyboard = makeInlineKeyboard([['text' => "перейти", 'url' => "here we make query depending on the user`s search"]]);
            $bot->sendMessage(['chat_id' => $mes['chat']['id'], "text" => "ваш запрос *$searchInfo* сформирован", "reply_markup" => $keyboard]);
            $user->updateValues(['is_searching' => 0], 'chat_users');
            break;
        
        case '/getlink':
            #if user want to get back in chat, we check if he is truly not in group 
            $user = new UserDB($mes['chat']['id'], $pdo);
            $isConfirmed = $user->getValues(['is_confirmed'], 'users')[0];
            $userStatus = $user->getValues(['status'], 'chat_users');

            if($isConfirmed['is_confirmed'] == 1 && isset($userStatus[0])){
                if($userStatus[0]['status'] == "left"){
                    #if true we send him new link to chat
                    $groupId = $user->getValues(['group_id'], 'chat_users')[0];
                    $inviteLink = $bot->createInviteLink(['chat_id' => intval($groupId['group_id']), 'expire_date' => time() + 600, "members_limit" => 1]); 
                    $textToSend = "Ваша ссылка -";
                    $bot->sendMessage(['chat_id' => $mes['chat']['id'], 'text' => $textToSend . $inviteLink]);
                    $user->updateValues(['status' => 'got link'], 'chat_users');
                }
            }
            break;
        default:
            #otherwise thought-provorking logic to handle validation or send an other queston to user
            $is_callback == 1 ? ((explode('$#%', $mesText)[0] == '/rejected' || explode('$#%', $mesText)[0] ==  '/approved') ? handleValidationResponse($mesText, $pdo, $bot) : sendQuestion($mes['callback_query']['from']['id'], $bot, $mesText, $pdo)) : ($is_photo == 0 ? sendQuestion($mes['from']['id'], $bot, $mesText, $pdo) : sendQuestion($mes['message']['from']['id'], $bot, $mesText, $pdo));
    }
?>
