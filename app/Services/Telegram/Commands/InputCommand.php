<?php

namespace App\Services\Telegram\Commands;

use App\Models\TelegramUsers;
use App\Services\Telegram\TextManager;
use WeStacks\TeleBot\Interfaces\UpdateHandler;
use WeStacks\TeleBot\Objects\Keyboard\InlineKeyboardMarkup;
use WeStacks\TeleBot\Objects\KeyboardButton;
use WeStacks\TeleBot\Objects\Update;
use WeStacks\TeleBot\TeleBot;

class InputCommand extends Command
{
    public static function trigger(Update $update, TeleBot $bot)
    {
        return isset($update->message->text) || isset($update->message->contact);
    }

    /*
     * Обработчик срабатывает когда был введен текст
     */
    public function handle()
    {
        $update = $this->update;
        $bot = $this->bot;

        $text = isset($update->message->text) ? $update->message->text : '';
        $command = TextManager::checkCommand($text);

        // Если не авторизованы
        if (!$this->isAuth()) {

            // Если поделились контактом
            if (isset($update->message->contact)) {
                $command = "SEND_CONTACT"; // переопределям меню
            }

            switch ($command) {

                case "AUTH":
                    $this->authMenu();
                    break;

                case "NO_AUTH":
                    $this->noAuthMenu();
                    break;

                case "SEND_CONTACT":
                    $this->sendContactMenu();
                    break;

                case "CONTACT_WITH_ME":
                    $this->contactWithMeMenu();
                    break;

                default:
                    $this->parseInputText($text);
            }
        } else {

//            dump($command);
            switch ($command) {

                case "MAIN_MENU":
                    $this->mainMenu();
                    break;

                case "USER_INFO":
                    $this->userInfoMenu();
                    break;

                case "NEWS":
                    $this->newsMenu();
                    break;

                case "HELP":
                    $this->helpMenu();
                    break;

                case "CONTACTS":
                    $this->contactsMenu();
                    break;

                case "ABOUT":
                    $this->aboutMenu();
                    break;

                case "SETTINGS":
                    $this->settingsMenu();
                    break;

                case "NOTIFICATIONS":
                    $this->notificationsMenu();
                    break;

                case "LANG":
                    $this->langMenu();
                    break;

                default:
                    $this->parseInputText($text);
            }
        }

    }

    /**
     * Ищем в какое меню нужно зайти
     */
    private function parseInputText($text)
    {
        $lastAction = $this->getLastAction();

        // Если не авторизованы
        if (!$this->isAuth()) {


            switch ($lastAction) {

                case "OTP_SENDED":
                    $this->applyOtp($text);
                    break;

                default:
                    // по умолчанию
                    $this->noAuthMenu();
            }

        } else {

            switch ($lastAction) {

                case "langMenu":

                    break;

                default:
                    // по умолчанию
                    $this->mainMenu();
            }
        }
    }

    private function changeLang($text)
    {
        $this->setLastAction(__FUNCTION__);

    }

    /**
     * Проверяем введенный ОТР код
     */
    private function applyOtp($text)
    {
        $this->setLastAction(__FUNCTION__);

        $keyboard = [
            [["text" => TextManager::get("BACK")]],
        ];

        $response = $this->ClientAPI->authPhoneOtpApply($text);

        if (isset($response['success'])) {

            // Привяжем номер user_id телеграма к uid запишем токен
            TelegramUsers::updateOrCreate(
                ['id' => $this->getUserID()],
                [
                    'mb_uid' => $response['data']['uid'],
                    'token'  => $response['data']['token'],
                ]
            );

            $text = "Спасибо. Бот успешно авторизован! 🎉";

            $keyboard = [
                [["text" => TextManager::get("MAIN_MENU")]],
            ];
        } else {
            $text = "Что то пошло не так...";
        }


        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }

    private function sendContactMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $phone_number = $this->update->message->contact->phone_number;

        // Запишем присланный телефон
        TelegramUsers::where('id', $this->getUserID())
            ->update(['phone' => $phone_number]);

        $text = "Ваш телефон: " . $phone_number;

        $keyboard = [
            [["text" => TextManager::get("BACK")]],
        ];

        // Пришел номер пытаемся авторизоваться по ОТП
        $response = $this->ClientAPI->authPhone($phone_number);

        if (isset($response['success'], $response['code'])) {

            switch ($response['code']) {
                case 0:
                    $text = TextManager::get("OTP_SENDED");
                    $this->setLastAction("OTP_SENDED");
                    break;

                default:
                    $text = "Что то пошло не так...";
            }
        } else {
            // АПИ не ответило
            $text = "Что то пошло не так...";
        }


//        // Ищем абонента по номеру
//        $response = $api->searchUser($phone_number, 'mobile_phone'); // from $phone_number
//
//        // Привязка существует, получаем токен для работы через ЛК API
//        if (isset($response['data'][0]['uid'])) {
//            // Абонента нашли, отправляем OTP
//
//            // Привяжем номер user_id телеграма к uid
//            $response = $api->bindUser($this->getUserID(), $response['data'][0]['uid']);
//            if (isset($response['success']) and $response['success'] === true) {
//                $text = "Спасибо. Бот успешно авторизован! 🎉";
//
//                $keyboard = [
//                    [["text" => TextManager::get("MAIN_MENU")]],
//                ];
//            }
//
//            // Поулчили OTP привязываем учетку к номеру
//            // $text2 = "Введите пароль из SMS, отправленный на указанный номер телефона. ";
//
//        } else {
//            // Абонента не нашли, пишем что не наш абонент
//            $text = "К сожалению мы не смогли найти Вас среди наших абонентов. Обратитесь в службу поддержки. ";
//        }

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);

    }


    private function userInfoMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $response = $this->ClientAPI->getUser();
        $user = $response['data'];

        switch ($user['state']) {
            case 1:
                $status = 'обычный';
                break;
            case 2:
                $status = 'замороженный';
                break;
            case 3:
                $status = 'отключенный';
                break;
            case 4:
                $status = 'удаленный';
                break;
            default:
                $status = 'обычный';
        }


        $text = "<b>Информация по абоненту:</b>  \n";
        $text .= "<b>ФИО:</b> " . $user['fio'] . "\n";
        $text .= "<b>Баланс:</b> " . $user['deposit'] . " руб.\n";
        $text .= "<b>Кредит:</b> " . $user['credit'] . " руб.\n";
        $text .= "<b>Тариф:</b> " . $user['tarif'] . "\n";
        $text .= "<b>Логин:</b> " . $user['user'] . "\n";
        $text .= "<b>UID:</b>" . $user['useruid'] . " \n";
        $text .= "<b>Договор:</b>" . $user['numdogovor'] . " \n";
        if ($user['blocked']) {
            $text .= "<b>Интернет:</b> 🚫 \n";
        } else {
            $text .= "<b>Интернет:</b> ✅ \n";

            if (!empty($user['date_itog'])) {
                $text .= "<b>Дата отключения:</b> " . $user['date_itog'] . " \n";
                $text .= "<b>Осталось дней:</b> " . $user['days_left'] . " \n";
            }
        }
//        $text .= "<b>IP:</b> " . $user['framed_ip'] . "\n";
//        $text .= "<b>Cтатус:</b> " . $status . "\n";


        $keyboard = [
            [["text" => TextManager::get("BACK")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }


    private function aboutMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "Здесь в будущем появится информация о вашем провайдере";

        $keyboard = [
            [["text" => TextManager::get("BACK")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }


    private function authMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "Для старта отправьте нам Ваш номер телефона, нажав кнопку <b>Отправить контакт</b>";

        $keyboard = [
            [["text" => TextManager::get("SEND_CONTACT"), "request_contact" => true], ["text" => TextManager::get("BACK")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);

    }

    private function contactWithMeMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "Напишите Ваш вопрос нашей службе поддержки, перейдя по ссылке https://t.me/ACPMikBiLL. Также мы можем Вам позвонить - напишите свой номер телефона";

        $keyboard = [
            [["text" => TextManager::get("BACK")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);

    }

    private function langMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "Выберите язык общения в боте";

        $keyboard = [
            [["text" => "🇺🇦 UA"], ["text" => "🇷🇺 RU"], ["text" => "🇺🇸 EN"]],
            [["text" => TextManager::get("BACK")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);

    }

    private function settingsMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "Настройки \n\n";
        $text .= "🔔 <b>Уведомления</b> - управление уведомлениями при пополнении счета, либо других финансовых операций; \n";
        $text .= "🇺🇸 <b>Выбор языка</b> - выберите язык, на котором бот будет вести диалог; \n";

        $keyboard = [
            [["text" => TextManager::get("NOTIFICATIONS")], ["text" => TextManager::get("LANG")]],
            [["text" => TextManager::get("BACK")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }

    private function notificationsMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "<b>Текущий статус уведомлений:</b> \n\n";
        $text .= "🔕 Новости\n\n";
        $text .= "🔔 Финансовые уведомления \n\n";
        $text .= "🔔 За 3 дня до отключения \n\n";

        $keyboard = [
            [["text" => TextManager::get("BACK")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }

    private function contactsMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "Наши контакты: \n";
        $text .= "Офис: г.Волноваха ул. Народная 101. \n";
        $text .= "т. +38(093) 470-82-80 \n";
        $text .= "telegram: @kagatan";

        $keyboard = [
            [["text" => TextManager::get("BACK")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }


    private function helpMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "🤯 Мы сейчас сильно заняты. Если что то срочное позвоните в техподдержку...";

        $keyboard = [
            [["text" => TextManager::get("BACK")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }

    private function newsMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "🤐 Тсс... Здесь будут новости, но чуть позже...";

        $keyboard = [
            [["text" => TextManager::get("BACK")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }

    private function mainMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = TextManager::get("MAIN_MENU_TEXT");

        $keyboard = [
            [["text" => TextManager::get("USER_INFO")], ["text" => TextManager::get("NEWS")]],
            [["text" => TextManager::get("HELP")], ["text" => TextManager::get("CONTACTS")]],
            [["text" => TextManager::get("SETTINGS")]]
        ];

        $this->sendMessage([
            'text'         => $text,
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }

    private function noAuthMenu()
    {
        $this->setLastAction(__FUNCTION__);

        $text = "👉 Для начала необходимо пройти авторизацию в сервисе";


        $keyboard = [
            [["text" => TextManager::get("AUTH")], ["text" => TextManager::get("CONTACT_WITH_ME")]],
        ];

        $this->sendMessage([
            'text'         => $text,
            'reply_markup' => [
                'keyboard'          => $keyboard,
                'resize_keyboard'   => true,
                'one_time_keyboard' => true
            ]
        ]);
    }

}
