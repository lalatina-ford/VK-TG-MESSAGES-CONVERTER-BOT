<?php
/**
`tg_user` int(11) NOT NULL,
  `vk_user` int(11) NOT NULL,
  `status_vk` int(11) NOT NULL,
  `status_tg` int(11) NOT NULL,
  `chat_type` int(11) NOT NULL
) 
**/
/**
 * DB Class
 */
class DB
{
	function __construct()// Auto connect
		{
			R::setup( 'mysql:host=localhost;dbname='.DB_DATABASE,DB_USER, DB_PASS);
			if(!R::testConnection())
				{
					TG::sendMessage(TG_ADMIN_CHAT_ID,'Не удалось подключиться к БД');
				}
		}
	public function getChats($user_id)
		{
			$result = "Ваши чаты:";
			$chats = R::find('chat', 'tg_user = ?',[$user_id]);
			if (empty($chats))
				$result = 'Тебя нет ни в одном чате';
			else
				foreach ($chats as $chat) 
					{
						$result = $result."\n".$chat['id']."==".$chat['vk_chat'];
					}
			return $result;
		}
	public function set($id, $chatId, $tg_user)
	{
		$chat = R::load('chat', $id);
		if ($tg_user == $chat->tg_user) {
			$chat->tg_chat = $chatId;
			$chat->chat_type = '1';
			R::store($chat);

			return $chat->tg_chat; // выводим наше новое значение
		}
		else{
			return "Вас нет в введённом чате!!!";
		}
	}
	
	public function inviteUser($inviteUser=null, $chat_id)
	{
		$inviteUser = $inviteUser[0];
		if (is_numeric($inviteUser)) {
			if (R::find('chat', 'tg_user = :tg_user AND vk_chat = :vk_chat',array(':tg_user' => $inviteUser, ':vk_chat' => $chat_id))) return false;
			$invite = R::dispense('chat');
			$invite->tg_user = $inviteUser;
			$invite->vk_chat = $chat_id;
			$invite->status_vk = '1';// invited
			$invite->status_tg = '0';// not joined
			$invite->chat_type = '2';// group chat
			$id = R::store($invite);
			return $id;
		}
	}
	public function leave($id, $chat_id,$user_id)
	{
		$chat = R::load('chat', $id);
		if ($user_id == $chat->tg_user) {
			$chat->tg_chat = $chatId;
			$chat->chat_type = '2';// 2-not joined
			R::store($chat);

			return "Вы успешно покинули чат";
		}
		else{
			return "Вас нет в введённом чате!!!";
		}
	}
	public function delete($id, $chat_id,$user_id)
	{
		$chat = R::load('chat', $id);
		if ($user_id == $chat->tg_user) {
			R::trash($chat);

			return "Вы успешно удалили чат";
		}
		else{
			return "Вас нет в введённом чате!!!";
		}
	}
}
