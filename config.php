<?php
define("ROOT", $_SERVER['DOCUMENT_ROOT']);// Корневая директория(не менять)
define("TG_TOKEN", "");// Токен телеграмм бота
define("VK_TOKEN", "");// Токен ВК сообщества
define("VK_CONFIRMATION_TOKEN", "be68b0ce");// Код подтверждения
define("VK_VERSION", "5.126");// Версия АПИ
define("TG_ADMIN_CHAT_ID", "1490168848");// АЙДИ чата с админом
define("TG_BOT_API", "https://api.telegram.org/bot".TG_TOKEN);.// Прочие ссылки
define("FILE_TG_BOT_API", "https://api.telegram.org/file/bot".TG_TOKEN);
define("VK_ADMIN_ACCESS_TOKEN", "");// Токен пользователя ВК(для аудио и видео)
// for getting vk user token: https://oauth.vk.com/authorize?client_id=АЙДИ_ПРИЛОЖЕНИЯ&scope=65564&redirect_uri=https://oauth.vk.com/blank.html&display=page&response_type=token&revoke=1
// MYSQL PARAMS
define("DB_USER", "");// Имя пользователя БД
define("DB_PASS", "");// Пароль БД
define("DB_DATABASE", "");// Название БД