<?php


namespace App\Services\Telegram\Commands;


use App\Services\Telegram\TextManager;
use WeStacks\TeleBot\Handlers\CommandHandler;

class StartCommand extends Command
{
    protected static $aliases = ['/start', '/s'];
    protected static $description = 'Send "/start" or "/s" to get "Hello, World!"';

    /**
     * Обработчик подключения к боту или команды /start
     */
    public function handle()
    {
        $chat_id = $this->update->message->from->id;

        if (isset($this->update->message->chat->last_name, $this->update->message->chat->first_name)) {
            $text = "<b>Приветствую,  " . $this->update->message->chat->last_name . " " . $this->update->message->chat->first_name . " ! </b> 👋 \n\n";
        } else {
            $text = "<b>Приветствую! </b> 👋 \n\n";
        }
//        $text .= "Ваш ID: " . $chat_id . "\n";

        $text .= "MikBillUsers_Bot поможет Вам контролировать платежи и управлять доступом в интернет в один клик

Что может MikBillUsers_Bot?

Для абонентов:
✔️ Проверять статус услуг;
🛒 Изменять тарифные планы, заказывать услуги;
🔔 Уведомлять о финансовых операциях Вашей учетной записи;
💳 Пополнить счет;
ℹ️ Просматривать статистику по вашей учетной записе;
🆘 Поддержка 24/7.
";

        $this->sendMessage([
            'text'       => $text,
            'parse_mode' => 'HTML'
        ]);
    }
}
