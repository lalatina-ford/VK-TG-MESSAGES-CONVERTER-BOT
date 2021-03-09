<?php
/**
 * VK METHODS CLASS
 */
class VK
{
	public static function send($user_id, $text, $attachment = false)
	{
		$rand = rand(0000000000, 9999999999);
		$request_params = array(
			'random_id' => $rand,
			'peer_id' => $user_id,
			'message' => $text
		);
		if ($attachment) $request_params['attachment'] = $attachment;
		$get_params = http_build_query($request_params); 
		$res = 'https://api.vk.com/method/messages.send?'. $get_params.'&access_token='.VK_TOKEN.'&v='.VK_VERSION;
		file_get_contents($res);
	}
	public static function getProfileInfo($user_id)
	{
		$request_params = array(
			'user_ids' => $user_id
		);
		$get_params = http_build_query($request_params); 
		$res = 'https://api.vk.com/method/users.get?'. $get_params.'&access_token='.VK_TOKEN.'&v='.VK_VERSION;
		$result = file_get_contents($res);
		return json_decode($result, true);
	}
	/**
	*@return {String}  Returns photo upload link
	**/
	public static function getMessagesUploadServer()
	{
		$request_params = array(
			'peer_id' => 0
		);
		$get_params = http_build_query($request_params); 
		$res = 'https://api.vk.com/method/photos.getMessagesUploadServer?'. $get_params.'&access_token='.VK_TOKEN.'&v='.VK_VERSION;
		$result = file_get_contents($res);
		$result = json_decode($result, true);
		return $result['response']['upload_url'];
	}
	/**
	*@return {String}  Returns document upload link
	**/
	public static function docs_getMessagesUploadServer($id , $type = 'doc')
	{
		$request_params = array(
			'peer_id' => $id,
			'type' => $type
		);
		$get_params = http_build_query($request_params); 
		$res = 'https://api.vk.com/method/docs.getMessagesUploadServer?'. $get_params.'&access_token='.VK_TOKEN.'&v='.VK_VERSION;
		$result = file_get_contents($res);
		$result = json_decode($result, true);
		return $result['response']['upload_url'];
	}
	public static function audio_getUploadServer()
	{
		$res = 'https://api.vk.com/method/audio.getUploadServer?'.'&access_token='.VK_ADMIN_ACCESS_TOKEN.'&v='.VK_VERSION;
		$result = file_get_contents($res);
		$result = json_decode($result, true);
		return $result['response']['upload_url'];
	}
	public static function saveMessagesPhoto($photoOnVK)
	{
		$request_params = array(
			'server' => $photoOnVK['server'],
			'photo' => $photoOnVK['photo'],
			'hash' => $photoOnVK['hash']
		);
		$get_params = http_build_query($request_params); 
		$res = 'https://api.vk.com/method/photos.saveMessagesPhoto?'. $get_params.'&access_token='.VK_TOKEN.'&v='.VK_VERSION;
		$result = file_get_contents($res);
		return json_decode($result, true);
	}
	public static function docs_save($photoOnVK)
	{
		$request_params = array(
			'file' => $photoOnVK['file']

		);
		$get_params = http_build_query($request_params); 
		$res = 'https://api.vk.com/method/docs.save?'. $get_params.'&access_token='.VK_TOKEN.'&v='.VK_VERSION;
		$result = file_get_contents($res);
		return json_decode($result, true);
	}
	public static function video_save($photoOnVK)
	{
		$request_params = array(
			'is_private' => 1
		);
		$get_params = http_build_query($request_params); 
		$res = 'https://api.vk.com/method/video.save?'. $get_params.'&access_token='.VK_ADMIN_ACCESS_TOKEN.'&v='.VK_VERSION;
		$result = json_decode(file_get_contents($res), true);
		return $result['response']['upload_url'];
	}
	public static function audio_save($photoOnVK, $message)
	{
		$request_params = array(
			'server' => $photoOnVK['server'],
			'audio' => $photoOnVK['audio'],
			'hash' => $photoOnVK['hash'],
			'artist' => $message

		);
		$get_params = http_build_query($request_params); 
		$res = 'https://api.vk.com/method/audio.save?'. $get_params.'&access_token='.VK_ADMIN_ACCESS_TOKEN.'&v='.VK_VERSION;
		$result = file_get_contents($res);
		return json_decode($result, true);
	}
	public static function video_get($video)
	{
		
		$request_params = array(
			'videos' => $video,
		);
		$get_params = http_build_query($request_params); 
		$res = 'https://api.vk.com/method/video.get?'. $get_params.'&access_token='.VK_ADMIN_ACCESS_TOKEN.'&v='.VK_VERSION;
		$result = file_get_contents($res);
		$result = json_decode($result, true);
		return $result['response']['items'][0]['player'];
	}
	// custom functions
	public function switchAttachment($tg_chat, $attachments, $send_text)
	{
		foreach ($attachments as $attachment) {
			switch ($attachment['type']) {
				/*
				photo — фотография; ok
				video — видеозапись; ok
				audio — аудиозапись; ok
				doc — документ; ok
				wall — запись на стене; ok
				sticker — стикер; ok
				--wall_reply — комментарий к записи на стене;
				--link — ссылка;
				--gift — подарок.
				*/
				case 'photo':
					$photo = array_pop($attachment['photo']['sizes']);
					TG::sendPhoto($tg_chat, $photo['url'], $send_text);
					sleep(1);
					break;
				case 'video':
					$video = $attachment['video'];
					$video = $video['owner_id'].'_'.$video['id'].'_'.$video['access_key'];
					$res = VK::video_get($video);
					TG::sendMessage($tg_chat, $res."\n".$send_text);
					break;
				case 'audio':
					$audio = $attachment['audio'];
					$artist = $audio['artist'];
					$title = $audio['title'];
					$audioString = "Аудио - [{$artist} - {$title}]";
					TG::sendMessage($tg_chat, $audioString."\n".$send_text);
					break;
				case 'audio_message':
					TG::sendVoice($tg_chat, $attachment['audio_message']['link_ogg'], $send_text);
					break;
				case 'doc':
					TG::sendMessage($tg_chat, $attachment['doc']['url']."\n".$send_text);
					break;
				case 'wall':
					$wall = $attachment['wall'];
					$this->switchAttachment($tg_chat, $wall['attachments'], "{$send_text}\n[Запись на стене]\n{$wall['text']}");
					break;
				case 'sticker':
					$sticker = array_pop($attachment['sticker']['images']);
					TG::sendPhoto($tg_chat, $sticker['url'], $send_text);
					break;
				default:
					# Undefined
					break;
			}
		}
	}
}