<?php


namespace App\Services\Telegram;


use Illuminate\Support\Facades\Cache;

class TextManager
{
    public static $defLang = 'rus';

    # @todo Перевести на БД
    public static $translates = [
        [
            "name" => "MAIN_MENU",
            "rus"  => "Главное меню",
            "ukr"  => "Главное меню",
        ],
        [
            "name" => "MAIN_MENU_TEXT",
            "rus"  => "Сделайте выбор в меню:",
            "ukr"  => "Сделайте выбор в меню:",
        ],
        [
            "name" => "USER_INFO",
            "rus"  => "👱‍♂️Моя информация",
            "ukr"  => "👱‍♂️Моя информация",
        ],
        [
            "name" => "SEND_CONTACT",
            "rus"  => "Отправить контакт",
            "ukr"  => "Отправить контакт",
        ],
        [
            "name" => "BACK",
            "rus"  => "🔙 Назад",
            "ukr"  => "🔙 Назад",
        ],
        [
            "name" => "ABOUT",
            "rus"  => "🤖 О нас",
            "ukr"  => "🤖 О нас",
        ],
        [
            "name" => "CONTACTS",
            "rus"  => "☎ Контакты",
            "ukr"  => "☎ Контакты",
        ],
        [
            "name" => "NEWS",
            "rus"  => "📢 Новости",
            "ukr"  => "📢 Новости",
        ],
        [
            "name" => "HELP",
            "rus"  => "🆘 Помощь",
            "ukr"  => "🆘 Помощь",
        ],
        [
            "name" => "SETTINGS",
            "rus"  => "⚙️Настройки",
            "ukr"  => "⚙️Настройки",
        ],
        [
            "name" => "NOTIFICATIONS",
            "rus"  => "🔔 Уведомления",
            "ukr"  => "🔔 Уведомления",
        ],
        [
            "name" => "LANG",
            "rus"  => "🇺🇸 Выбор языка",
            "ukr"  => "🇺🇸 Выбор языка",
        ],
        [
            "name" => "AUTH",
            "rus"  => "Авторизоваться",
            "ukr"  => "Авторизоваться",
        ],
        [
            "name" => "CONTACT_WITH_ME",
            "rus"  => "Связать со мной",
            "ukr"  => "Связать со мной",
        ],
        [
            "name" => "OTP_SENDED",
            "rus" => "Введите код доступа, который мы отправили вам в SMS ✉️",
            "ukr" => "Введи код доступу, який ми надіслали тобі в SMS ✉️"
        ],
    ];

    /**
     * Вернуть локлаль для переменной
     *
     * @param $name
     * @param string $lang
     * @return mixed|string
     */
    public static function get($name, $lang = 'rus')
    {
        // Проверим есть ли в кеше
        if (Cache::has($name)) {
            // Берем из кеша
            return Cache::get($name);
        } else {
            foreach (self::$translates as $translate) {
                if ($translate['name'] === $name) {
                    if (isset($translate[$lang])) {
                        // Пишем в кеш
                        Cache::put($name, $translate[$lang]);
                        return $translate[$lang];
                    } else {
                        return $translate[self::$defLang];
                    }
                }
            }
        }

        return $name;
    }

    /**
     * Получить команду из текста
     *
     * @param $text
     */
    public static function checkCommand($text)
    {
        // Проверим есть ли в кеше
        if (Cache::has($text)) {
            // Берем из кеша
            return Cache::get($text);
        } else {
            foreach (self::$translates as $translate) {

                // Ищем по локалям
                foreach ($translate as $key => $value) {
                    // Нашли
                    if ($value === $text) {

                        // Пишем в кеш
                        Cache::put($text, $translate['name']);

                        return $translate['name'];
                    }
                }
            }
        }

        return 'MAIN_MENU'; //default
    }
}
