<?php
/**
 * TG METHODS CLASS
 */
class TG
{
	//
	//// Методы Telegram API
	//
	/**
	* Функция отправки сообщения в чат с использованием метода sendMessage
	* @param chat_id	Integer or String
	* @param text	String
	* @param parse_mode	String	Необязательный	Send Markdown or HTML
	* @param disable_web_page_preview	Boolean	Необязательный	Disables link previews for links in this message
	* @param disable_notification	Boolean	Необязательный
	* @param reply_to_message_id	Integer	Необязательный	If the message is a reply, ID of the original message
	* @param reply_markup	InlineKeyboardMarkup or ReplyKeyboardMarkup or ReplyKeyboardHide or ForceReply
	* @return null
	*/
	public static function sendMessage($var_chat_id,$var_message,$parse_mode='html')
	{
	    file_get_contents(TG_BOT_API.'/sendMessage?chat_id='.$var_chat_id.
	    		'&text='.urlencode($var_message).
	    		'&parse_mode='.urlencode($parse_mode));
	}
	public static function sendPhoto($var_chat_id, $var_photo, $var_message="", $parse_mode='html')
	{
		file_get_contents(TG_BOT_API.'/sendPhoto?chat_id='.$var_chat_id.
				'&photo='.urlencode($var_photo).
				'&caption='.urlencode($var_message).'&parse_mode='.urlencode($parse_mode));
	}
	public static function sendAudio($var_chat_id, $var_audio, $var_message="", $parse_mode='html')
	{
		file_get_contents(TG_BOT_API.'/sendAudio?chat_id='.$var_chat_id.
				'&audio='.urlencode($var_audio).
				'&caption='.urlencode($var_message).'&parse_mode='.urlencode($parse_mode));
	}
	public static function sendVoice($var_chat_id, $var_audio, $var_message="", $parse_mode='html')
	{
		file_get_contents(TG_BOT_API.'/sendVoice?chat_id='.$var_chat_id.
				'&voice='.urlencode($var_audio).
				'&caption='.urlencode($var_message).'&parse_mode='.urlencode($parse_mode));
	}
	public static function sendVideo($var_chat_id, $var_audio, $var_message="", $parse_mode='html')
	{
		file_get_contents(TG_BOT_API.'/sendVideo?chat_id='.$var_chat_id.
				'&video='.urlencode($var_audio).
				'&caption='.urlencode($var_message).'&parse_mode='.urlencode($parse_mode));
	}
	public static function sendAnimation($var_chat_id, $var_animation, $var_message="", $parse_mode='html')
	{
		file_get_contents(TG_BOT_API.'/sendAnimation?chat_id='.$var_chat_id.
				'&animation='.urlencode($var_animation).
				'&caption='.urlencode($var_message).'&parse_mode='.urlencode($parse_mode));
	}
	public static function sendSticker($var_chat_id, $var_sticker)
	{
		file_get_contents(TG_BOT_API.'/sendSticker?chat_id='.$var_chat_id.
				'&sticker='.urlencode($var_sticker));
	}
	public static function getFile($file_id)
	{
		$result = file_get_contents(TG_BOT_API.'/getFile?file_id='.$file_id);
		$result = json_decode($result, true);
		return $result['result']['file_path'];
	}
	//
	//// КОНЕЦ МЕТОДОВ TELEGRAM API
	//

	//
	//// Пользовательские функции
	//
	/**
	* Функция получения файла по ссылке
	*@param {String} URL цели
	*@return {String} Путь к файлу
	*/
	public static function downloadFile($file_path)
		{
			$imgType = array_pop(explode('.', $file_path));// Расширение файла
			if ($imgType == 'tgs') return 'TGS';
            $salt = rand(0000000000, 9999999999);// Рандомное имя файла
            $path = 'downloads/';// Директория для сохранения на время работы скрипта
            $low_path = $path.$salt.'.'.$imgType;// Короткий путь к файлу
            $path = $_SERVER['DOCUMENT_ROOT'] .'/'. $low_path;// Полный путь к файлу
            file_put_contents($path, file_get_contents(FILE_TG_BOT_API.'/'.$file_path));// Вставка фото в файл
			return $low_path;
		}
	/**
	* Функция отправки файла через curl
	*@param {String} URL назначения
	*@param {Array} Данные для отправки
	*@return {String} Массив-результат
	*/
	public static function sendFile($targetUrl, $send_data)
		{
			$curl = curl_init($targetUrl);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true); // enable posting
            curl_setopt($curl, CURLOPT_POSTFIELDS, $send_data); // post images
            $result = curl_exec($curl);
            curl_close($curl);
            return json_decode($result,true);
		}
	/**
	* Убиваем скрипт если это сервисное сообщение
	*@param {Array} Ключи приходящего запроса
	*/
	public static function checkServiceMessages($messageKeys)
		{
			if (in_array('new_chat_title', $messageKeys))die;
			if (in_array('new_chat_photo', $messageKeys))die;
			if (in_array('new_chat_members', $messageKeys))die;
			if (in_array('delete_chat_photo', $messageKeys))die;
			if (in_array('group_chat_created', $messageKeys))die;
			if (in_array('supergroup_chat_created', $messageKeys))die;
			if (in_array('pinned_message', $messageKeys))die;
		}
	/**
	* Отправляем сообщение из телеграма в телеграм чаты других участников беседы ВК
	* @param {String} ID Чата отправителя
	* @param {String} ID Отправителя
	* @param {String} Сообщение
	* @param {String} ID Прикреплённого  к сообщению обьекта
	* @param {String} Тип прикреплённого  к сообщению обьекта
	*/
	public function sendToOtherUsers($chat_id, $user_id, $message, $attachment = null, $attachment_type = "")
		{
			$res = R::findOne('chat', 'tg_chat = :chat_id AND tg_user = :tg_user', array(':chat_id' => $chat_id, 'tg_user' => $user_id));
			$keys = array(':vk_chat' => $res->vk_chat, ':tg_user' => $user_id, ':tg_chat' => $chat_id);
			$users = R::find('chat', 'vk_chat = :vk_chat AND tg_user <> :tg_user AND tg_chat <> :tg_chat', $keys);
			foreach ($users as $user) {
				if ($user->tg_user != $user_id && $user->tg_chat != $chat_id) {
					switch ($attachment_type) {
						case 'photo':
							TG::sendPhoto($user->tg_chat, $attachment, $message);
							break;
						case 'animation':
							TG::sendAnimation($user->tg_chat, $attachment, $message);
							break;
						case 'audio':
							TG::sendAudio($user->tg_chat, $attachment, $message);
							break;
						case 'voice':
							TG::sendVoice($user->tg_chat, $attachment, $message);
							break;
						case 'video':
							TG::sendVideo($user->tg_chat, $attachment, $message);
							break;
						case 'sticker':
							TG::sendSticker($user->tg_chat, $attachment);
							break;
						default:
							$this->sendMessage($user->tg_chat, $message);
							break;
					}	
				}
			}			
		}
	/**
	* Проверяет есть ли пользователь в связке ТГ-ВК,
	* Если нет то убивает скрипт
	*@param {Object} Текущий чат
	*@param {String} ID Отправителя
	*@param {String} ID Чата отправителя
	*/
	public function checkUser($chat, $user_id, $chat_id)
		{
			if ($chat->chat_type != "1" || $chat->tg_user != $user_id) 
			{
				TG::sendMessage($chat_id,"Вас нет в этом чате!");
				die();
			}
		}
	/**
	* Получение типа сообщения
	*@param {Array} Ключи приходящего запроса
	*@return {String} Тип сообщения
	*/
	public function getMessageType($messageKeys)
		{
			if (in_array('animation', $messageKeys)) return 'Animation';
			if (in_array('audio', $messageKeys)) return 'Audio';
			if (in_array('document', $messageKeys)) return 'Document';
			if (in_array('video', $messageKeys)) return 'Video';
			if (in_array('voice', $messageKeys)) return 'Voice';
			if (in_array('photo', $messageKeys)) return 'Photo';
			if (in_array('sticker', $messageKeys)) return 'Sticker';
			return 'Text';
		}
}
