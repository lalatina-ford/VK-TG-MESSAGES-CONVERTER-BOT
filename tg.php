<?php
require_once 'config.php';
require_once ROOT.'/Components/Autoload.php';

$DB = new DB();
$TG = new TG();

// получаем то, что передано боту в POST-сообщении и
// распарсиваем в ассоциативный массив
$input_array = json_decode(file_get_contents('php://input'),TRUE);
if(!$input_array) dd('Access denied!');

$chat_id = $input_array['message']['chat']['id']; // выделяем идентификатор чата
$message = $input_array['message']['text'];       // выделяем сообщение
$caption = $input_array['message']['caption'];    // выделяем подпись(аналог сообщения)
$reply_to_message = $input_array['message']['reply_to_message'];
$user_id = $input_array['message']['from']['id'];  // выделяем идентификатор юзера
$isPrivate = false;
if ($user_id == $chat_id) $isPrivate = true;
$fname = $input_array['message']['from']['first_name']; // выделяем имя собеседника
$lname = $input_array['message']['from']['last_name'];  // выделяем фамилию собеседника
$uname = $input_array['message']['from']['username'];   // выделяем ник собеседника
$full_name = implode(' ', array($fname, $lname));

TG::checkServiceMessages(array_keys($input_array['message']));

// начинаем распарсивать полученное сообщение
$command = '';          // команды нет
$user_chat_id = '';     // адресат не определён
$user_text = '';        // текст от юзера пустой
$admin_text = '';       // текст сообщения от админа тоже пустой
 
$message_length = strlen($message);   // определяем длину сообщения
if($message_length!=0){               // если сообщение не нулевое
    $fs_pos = strpos($message,' ');   // определяем позицию первого пробела
    if($fs_pos === false){            // если пробелов нет,
        $command = $message;          //  то это целиком команда, без текста
    }
    else{                             // если пробелы есть,
        // выделяем команду и текст
        $command = substr($message,0,$fs_pos);
        $user_text = substr($message,$fs_pos+1,$message_length-$fs_pos-1);
 
        $user_text_length = strlen($user_text);    // определяем длину выделенного текста
        // если команда от админа и после неё есть текст - продолжаем парсить
        if(($chat_id == $admin_chat_id)&&($command === '/send') && ($user_text_length!=0)){
            // определяем позицию второго пробела
            $ss_pos = strpos($user_text,' ');
            if($ss_pos === false){                 // если второго пробела нет
                $user_chat_id = $user_text;        // то это целиком id чата назначения,
                $user_text = '';                   // а user_text - пустой
			}
            else{                     // если пробелы есть
                // выделяем id чата назначения и текст
                $user_chat_id = substr($user_text,0,$ss_pos);
                $admin_text = substr($user_text,$ss_pos+1,$user_text_length-$ss_pos-1);
            }
        }
    }
}

// после того, как всё распарсили, - начинаем проверять и выполнять
switch($command){
    case('/start'):
    case('/help'):
        TG::sendMessage($chat_id,'Здравствуйте! Я робот, бла-бла-бла. Команды:
            /help - вывести список поддерживаемых команд
            /getMyId - узнать свой ID телеграма
            /chats (писать ТОЛЬКО в сообщения боту) Узнать АЙДИ для установки связи
            /set АЙДИ- (писать ТОЛЬКО в чатах) Установить связь между беседой в вк и чатом в телеграме
            /send <i>message</i> - послать <i>message</i> админу');
        // если это команда от админа, дописываем что можно только ему
        if($chat_id == $admin_chat_id){
            TG::sendMessage($chat_id,'Поскольку вы админ, то можно ещё вот это:
            /send <i>chat_id</i> <i>message</i> - послать <i>message</i> в указанный чат');
        }
    break;
    case('/send'):    // отсылаем админу id чата юзера и его сообщение
        if($chat_id == $admin_chat_id){
            // посылаем текст по назначению (в указанный user_chat)
            TG::sendMessage($user_chat_id, $admin_text);
        }
        else{
            TG::sendMessage($admin_chat_id,$chat_id.': '.$user_text);
        }
    break;
    // команда /whoami добавлена чтобы админ мог узнать и записать
    // id своего чата с ботом, после этого её можно стереть
    case('/whoami'):
        TG::sendMessage($chat_id,$chat_id);    // отсылаем юзеру id его чата с ботом
    break;
    case('/getMyId'):
        TG::sendMessage($chat_id,$user_id);    // отсылаем юзеру id его чата с ботом
    break;
    case('/leave'):
        TG::sendMessage($chat_id, $DB->leave($user_text, $chat_id,$user_id));
    break;
    case('/delete'):
        TG::sendMessage($chat_id, $DB->delete($user_text, $chat_id,$user_id));
    break;
    case('/where'):
    	if ($isPrivate) TG::sendMessage($chat_id,'Вы пишете ботy в лс');
        else TG::sendMessage($chat_id,'Вы в чате');
    break;
    case('/chats'):
    	if ($isPrivate) TG::sendMessage($chat_id,$DB->getChats($user_id));
    	else TG::sendMessage($chat_id,'Это можно писать только в сообщ боту');
    break;
    case('/set'):
    	if (!$isPrivate){
    		TG::sendMessage($chat_id,$DB->set($user_text, $chat_id, $user_id));
    	}
    	else TG::sendMessage($chat_id,'Это можно писать только в сообщ ЧАТА');
    break;
    default:
        if (!$isPrivate) 
        {
            if ($reply_to_message) {
                $message = '<<'.$reply_to_message['text']."\n".$full_name.":".$message;
            }
            else{
                $message = $full_name.':'.$message;
            }

        	$chat = R::findOne('chat', 'tg_chat = :chat_id AND tg_user = :user_id',array(':chat_id' => $chat_id, 'user_id' => $user_id));
            if ($chat) {
                $TG->checkUser($chat, $user_id, $chat_id);
                $messageType = $TG->getMessageType(array_keys($input_array['message']));
                switch ($messageType) {
                    case 'Animation':
                        $file = $input_array['message']['animation'];
                        $file_path = TG::getFile($file['file_id']);// ссылка на вайл на сервера телеграм
                        $targetUrl = VK::docs_getMessagesUploadServer($chat->vk_chat);// Ссылка КУДА отправлять файл(на сервере вк)

                        $low_path = TG::downloadFile($file_path);// Download file
                        $photoOnVK = TG::sendFile($targetUrl, array('file' => new CURLFile(realpath($low_path))));

                        if(file_exists($low_path)) unlink($low_path); //Удаление только что созданной картинки
                        $photoData = VK::docs_save($photoOnVK);
                        $attachment = 'doc'.$photoData['response']['doc']['owner_id'].'_'.$photoData['response']['doc']['id'];
                        VK::send($chat->vk_chat, $message.$caption, $attachment);
                        $TG->sendToOtherUsers($chat_id, $user_id, $message.$caption, $file['file_id'], 'animation');
                        break;
                     case 'Audio':
                        $file = $input_array['message']['audio'];
                        $file_path = TG::getFile($file['file_id']);// ссылка на вайл на сервера телеграм
                        $targetUrl = VK::audio_getUploadServer();// Ссылка КУДА отправлять файл(на сервере вк)

                        $low_path = TG::downloadFile($file_path);
                        $photoOnVK = TG::sendFile($targetUrl, array('file' => new CURLFile(realpath($low_path))));

                        $photoData = VK::audio_save($photoOnVK, $caption);
                        if(file_exists($low_path)) unlink($low_path); //Удаление только что созданной картинки
                        $attachment = 'audio'.$photoData['response']['owner_id'].'_'.$photoData['response']['id'];
                        VK::send($chat->vk_chat, $message."-", $attachment);
                        $TG->sendToOtherUsers($chat_id, $user_id, $message, $file['file_id'], 'audio');
                        break;
                     case 'Document':
                        $file = $input_array['message']['document'];
                        $file_path = TG::getFile($file['file_id']);// ссылка на вайл на сервера телеграм
                        $targetUrl = VK::docs_getMessagesUploadServer($chat->vk_chat);// Ссылка КУДА отправлять файл(на сервере вк)

                        $low_path = TG::downloadFile($file_path);
                        $photoOnVK = TG::sendFile($targetUrl, array('file' => new CURLFile(realpath($low_path))));

                        $photoData = VK::docs_save($photoOnVK);
                        if(file_exists($low_path)) unlink($low_path); //Удаление только что созданной картинки
                        $attachment = 'doc'.$photoData['response']['doc']['owner_id'].'_'.$photoData['response']['doc']['id'];
                        VK::send($chat->vk_chat, $message.$caption, $attachment);
                        $TG->sendToOtherUsers($chat_id, $user_id, $message.$caption, $file['file_id'], 'document');
                        break;
                     case 'Video':
                        $file = $input_array['message']['video'];                        
                        $file_path = TG::getFile($file['file_id']);// ссылка на вайл на сервера телеграм
                        $targetUrl = VK::video_save($chat->vk_chat);// Ссылка КУДА отправлять файл(на сервере вк)

                        $low_path = TG::downloadFile($file_path);
                        $photoData = TG::sendFile($targetUrl, array('video_file' => new CURLFile(realpath($low_path))));

                        if(file_exists($low_path)) unlink($low_path); //Удаление только что созданной картинки
                        $attachment = 'video'.$photoData['owner_id'].'_'.$photoData['video_id'];
                        VK::send($chat->vk_chat, $message.$caption, $attachment);
                        $TG->sendToOtherUsers($chat_id, $user_id, $message.$caption, $file['file_id'], 'video');
                        break;
                     case 'Voice':
                        $file = $input_array['message']['voice'];
                        $file_path = TG::getFile($file['file_id']);// ссылка на вайл на сервера телеграм
                        $targetUrl = VK::docs_getMessagesUploadServer($chat->vk_chat, 'audio_message');// Ссылка КУДА отправлять файл(на сервере вк)

                        $low_path = TG::downloadFile($file_path);
                        $photoOnVK = TG::sendFile($targetUrl, array('file' => new CURLFile(realpath($low_path))));

                        $photoData = VK::docs_save($photoOnVK, $caption);
                        if(file_exists($low_path)) unlink($low_path); //Удаление только что созданной картинки
                        $attachment = 'doc'.$photoData['response']['audio_message']['owner_id'].'_'.$photoData['response']['audio_message']['id'];
                        VK::send($chat->vk_chat, $message."-", $attachment);
                        $TG->sendToOtherUsers($chat_id, $user_id, $message, $file['file_id'], 'voice');
                        break;
                     case 'Photo':
                        $file = array_pop($input_array['message']['photo']);
                        $file_path = TG::getFile($file['file_id']);// ссылка на вайл из серверов телеграм
                        $targetUrl = VK::getMessagesUploadServer();// Ссылка КУДА отправлять файл(на сервере вк)

                        $low_path = TG::downloadFile($file_path);// Путь начинающийся из корня сайта
                        $photoOnVK = TG::sendFile($targetUrl, array('photo' => new CURLFile(realpath($low_path))));

                        if(file_exists($low_path)) unlink($low_path); //Удаление только что созданной картинки
                        $photoData = VK::saveMessagesPhoto($photoOnVK);
                        $attachment = 'photo'.$photoData['response']['0']['owner_id'].'_'.$photoData['response']['0']['id'];
                        VK::send($chat->vk_chat, $message.$caption, $attachment);
                        $TG->sendToOtherUsers($chat_id, $user_id, $message.$caption, $file['file_id'], 'photo');
                        break;
                     case 'Sticker':
                        $file = $input_array['message']['sticker'];
                        $file_path = TG::getFile($file['file_id']);// ссылка на вайл на сервера телеграм
                        $targetUrl = VK::getMessagesUploadServer();// Ссылка КУДА отправлять файл(на сервере вк)

                        $low_path = TG::downloadFile($file_path);
                        // hard convert TGS to GIF
                        if ($low_path == 'TGS') {
                            TG::sendMessage($chat_id,"Анимированые стикеры отправлять нельзя");
                            die;
                        }
                        $photoOnVK = TG::sendFile($targetUrl, array('photo' => new CURLFile(realpath($low_path))));

                        if(file_exists($low_path)) unlink($low_path); //Удаление только что созданной картинки
                        $photoData = VK::saveMessagesPhoto($photoOnVK);
                        $attachment = 'photo'.$photoData['response']['0']['owner_id'].'_'.$photoData['response']['0']['id'];
                        VK::send($chat->vk_chat, $message."Telegram стикер", $attachment);
                        $TG->sendToOtherUsers($chat_id, $user_id, '', $file['file_id'], 'sticker');
                        break;
                    case 'Text':
                        VK::send($chat->vk_chat, $message);
                        $TG->sendToOtherUsers($chat_id, $user_id, $message);
                        break;
                    default:
                        // Unavaliable message type
                        break;
                }


            }
            else{
                TG::sendMessage($chat_id,"Вас нет в этом чате!");
            }                	
        }
    break;
}