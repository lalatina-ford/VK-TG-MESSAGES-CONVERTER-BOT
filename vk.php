<?php
require_once 'config.php';
require_once ROOT.'/Components/Autoload.php';
$DB = new DB();
$VK = new VK();

$data = json_decode(file_get_contents('php://input'), TRUE);
if(!$data) dd('ok');
switch ($data['type']) {  
	case 'confirmation': 
		echo VK_CONFIRMATION_TOKEN; 
	break;  	
	case 'message_new': 
		$message = $data['object']['message']['text'];
		$message_arr = explode(' ', $message);
		$message_text = array_shift($message_arr); // first element
		$chat_id = $data['object']['message']['peer_id'];
		$user_id = $data['object']['message']['from_id'];
		$attachments = $data['object']['message']['attachments'];
		$reply_message = $data['object']['message']['reply_message'];
		if($chat_id == $user_id) $isPrivate = true; //check message type(chat OR privete bot msg)
		else $isPrivate = false;

		switch ($message_text) {
			case '/invite':
				if (!$isPrivate) {
					$id = $DB->inviteUser($message_arr, $chat_id);
					if ($id) {
						VK::send($chat_id, "Ваш id чата: ".$id);
					}
					else{
						VK::send($chat_id, "Семпай ты дурак!)~");
					}
				}
				else VK::send($chat_id, "Только в групповом чате!!!");
				break;
			
			default:
				//Если сообщение отправлено в групповой чат, то пересылаем его в телегу
				if (!$isPrivate) {
					$chat = R::find('chat', 'vk_chat = ?',[$chat_id]);
					$tg_chats = array();//Массив с ИД телеграм чатов
				foreach ($chat as $chat_to) 
					{
						if ($chat_to->chat_type == "1") 
						{
							$userProfile = VK::getProfileInfo($user_id);
							$user = $userProfile['response']['0']['first_name']." ".$userProfile['response']['0']['last_name'];
							$send_text = $user.":".$message;
							if ($reply_message) {
								$send_text = '--'.'<i>'.$reply_message['text'].'</i>'."\n".$send_text;
							}
							$onlyText = empty($attachments);
							//Чтобы сообщегия не шли в один чат
							//Если в массиве нет этого значения то идём дальше
							if (!in_array($chat_to->tg_chat, $tg_chats)) {
								if ($onlyText)
									TG::sendMessage($chat_to->tg_chat, $send_text);
								else
									$VK->switchAttachment($chat_to->tg_chat, $attachments, $send_text);
							}
							$tg_chats[] = $chat_to->tg_chat;						
						}
					}
				}
				break;
		}
		echo 'ok';
	break;
}