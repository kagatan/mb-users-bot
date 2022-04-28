<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Отправка предупреждений за n-дней
     |--------------------------------------------------------------------------
     |
     | Параметр указывающий за сколько дней до отключения предупредить абонента
     |
     */
    'send_left_day_count'    => env('BOT_LEFT_DAY_COUNT', 3),


    /*
     |--------------------------------------------------------------------------
     | Время отправка предупреждения за n-дней
     |--------------------------------------------------------------------------
     |
     | Параметр указывающий во сколько будет ежедневно запускаться обработчик
     |
     */
    'send_left_day_run_time' => env('BOT_LEFT_DAY_RUN_TIME', '09:00'),


];