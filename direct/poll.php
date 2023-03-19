
<?php
    # MAIN LOGIC ABOUT POLL IN PRIVATE CHAT
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);

    header('Access-Control-Allow-Origin: *');
    
    require_once('direct/validationUser.php');
    

    function sendQuestion($Id, $bot, $mes, $pdo){
        
        include 'secret.php';

        $user = new UserDb($Id, $pdo);
        
        $data = $user->getValues(['test_round'], 'users');

        $testInfo = $user->getValues(['is_passed', 'is_confirmed'], 'users')[0];
        $is_passed = $testInfo['is_passed'];
        $is_confirmed = $testInfo['is_confirmed'];

        #firstly check if user have already passed test

        if($is_passed == 1 and $is_confirmed == 0){ 

            $textToSend = "Привет, вы уже проходили тест, дождитесь одобрения заявки от администратора)";
            $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend]);
            return false; 

        } else if($is_confirmed == 1){

            $textToSend = "Привет, вы уже проходили тест, бот создан только для добавления новых участников";
            $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend]);
            return false; 

        } else if($data == 0){

            $textToSend = "Попробуйте ещё раз";
            $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend]);
            return false;

        } else {
            #if not find out poll`s round number
            $round = $data[0]['test_round'];
            switch($round){
                case 0:
                    #first que
                    $banInfo = $user->getValues(['last_time_test'], 'users')[0];
                    $banReason = $banInfo['ban_reason'];
                    $lastTimeTested = $banInfo['last_time_test'];
                    #cheking ban
                    if($banReason == "ban in chat"){

                        $textToSend = "Вы были заблокированы в чате на неопределённый срок";
                        $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend]);
                        break;

                    #if banned and it`ve passed 2 weeks send user first question
                    } else if(time() - $lastTimeTested > 1209600){

                        $textToSend = "вопрос *1* из *9*:\n - (first question)";
                        $keyboard = makeInlineKeyboard([["text" => "да", "callback_data" => 1], ["text" => "нет", "callback_data" => 2]]);
                        $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend, 'reply_markup' => $keyboard]);

                        $user->updateValues(['test_round' => 1], 'users');

                        break;

                    } else {
                        #in ban case send him awaring message
                        if($banReason == 'admin'){

                            $textToSend = "Ваша блокировка ещё не истекла, вас заблокировал администратор, дождитесь её окончания и проходите тест снова)";
                        } else {

                            $textToSend = "(text if user was banned by wrong answer on 1st question)";
                        }
                        $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend]);

                        break;
                    };
                case 1:
                    # 2 question (user`s name)
                    if($mes != "1"){
                        #if user didn`t pass the 1st question ban him for 2 weeks
                        $textToSend = "(text to let user know that he was banned)";
                        $keyboard = makeInlineKeyboard([["text" => "перейти на форум", "url" => "(useful link)"]]);
                        $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend, 'reply_markup' => $keyboard]);

                        $user->updateValues(['test_round' => 0, 'last_time_test' => time(), 'ban_reason' => "(ban reason)"], 'users');

                        break;

                    } else {
                        #else send 2nd question
                        $textToSend = "вопрос *2* из *9*:\n - Представтесь, ваше имя";
                        $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend]);
                        
                        $user->updateValues(['test_round' => 2], 'users');
                        break;
                    } 
                case 2:
                    # 3rd question (phone number)
                    $user->insertValues(["name", "telegram_id"], [$mes, $Id], 'middle_users');
        
                    $textToSend = "вопрос *3* из *9*:\n - ваш номер телефона **без цифры страны (+7, 8)**, пример 9165341212";
                    $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend]);

                    $user->updateValues(['test_round' => 3], 'users');
                                
                    break;
                case 3:
                    # 4th question
                    #validating phone number
                    $validated = validatePhone($mes);
                    if(!$validated){

                        sendErrorMessage($Id, $bot, "извините, номер не распознан, проверьте правильность написания. Попробойте ещё раз, обратите внимание на *форму* ответа на вопрос");
                        break;
                    }; 

                    defaultRound("phone", $mes, "(4th question)", 4, $user, $bot, $Id);
                    break;
                case 4: 
                    #5th question (user`s job or hobby)
                    defaultRound("vehicle_number", $mes, "Напишите о своей сфере занятости", 5, $user, $bot, $Id, 1);
                    break;
                case 5:
                    #6th question (ask where is user based and decide which chat is the best for him)
                    $user->updateValues(['job' => $mes], 'middle_users');

                    $textToSend = "вопрос *6* из *9*:\n В каком городе или регионе вы проживаете?";
                    $keyboard = json_encode(['inline_keyboard' => [[['text' => 'Москва', 'callback_data' => $env['chats'][0]]], [['text' => 'Россия', 'callback_data' => $env['chats'][1]]], [['text' => 'Санкт-Петербург', 'callback_data' => $env['chats'][2]]], [['text' => 'Урал', 'callback_data' => $env['chats'][3]]], [['text' => 'Беларусь', 'callback_data' => $env['chats'][4]]]]]);
                    $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend, 'reply_markup' => $keyboard]);

                    $user->updateValues(['test_round' => 6], 'users');
                    break;
                case 6:
                    #7th question (smm link)
                    defaultRound("group_id", $mes, "Вставте ссылку на любую свою соцсеть (VK, Instagram, Facebook)", 7, $user, $bot, $Id);
                    break;
                case 7:
                    #8th question (nick on chat`s forum)
                    defaultRound("smm", $mes, "Укажите свой ник на нашем форуме", 8, $user, $bot, $Id, 1);
                    break;
                case 8:
                    #9th question (image)
                    defaultRound("forum_nick", $mes, "(asking user to send photo of smt(depends on your imagination))", 9, $user, $bot, $Id);
                    break;
                case 9:
                    #send the appeal to admin  
                    $user->updateValues(['img' => $mes], 'middle_users');

                    $textToSend = "Ваша заявка отправлена на рассмотрение администратору, *как только он её одобрит*, мы вам сообщим)";
                    $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend]);
                    
                    sendValidationToAdmin($bot, $user);
                   
                    break;

                default:

                    $textToSend = "Попробуйте ещё раз";
                    $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend]);
                    return false;

            }
            return true;

        }
            
    }

    #send error message
    function sendErrorMessage($id, $bot, $special_text = null){
        if(!$special_text){
            $textToSend = "извините, сообщение не распознано. Попробойте ещё раз, обратите внимание на *форму* ответа на вопрос";
        } else {
            $textToSend = $special_text;
        }
        $bot->sendMessage(['chat_id' => $id, 'text' => $textToSend]);
    }

    #validating phone number
    function validatePhone($str){
        $numbers = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
        $arr = str_split($str);
        if(count($arr) != 10){
            return false;
        };
        foreach($arr as $d){
            if(!in_array($d, $numbers)){
                return false;
            };
        };
        return true;
    }

    #this function for sending the default question to user (it has option to skip the certain questions)
    function defaultRound($field, $mes, $text, $round, $user, $bot, $Id, $isOptional = 0){
        $user->updateValues([$field => $mes], 'middle_users');
        
        $textToSend = "вопрос *$round* из *9*:\n - $text";
        if($isOptional != 0){
            $keyboard = makeInlineKeyboard([['text' => "пропустить вопрос", "callback_data" => "skip(#hash#)$round"]]);
            $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend, 'reply_markup' => $keyboard]);
        } else {
            $bot->sendMessage(['chat_id' => $Id, 'text' => $textToSend]);
        }

        $user->updateValues(['test_round' => $round], 'users');
    }
?>

