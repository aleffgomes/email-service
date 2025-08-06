<?php

namespace App\Config;

class EmailConfig
{
    public static function getConfig(): array
    {
        return [
            'host' => getenv('EMAIL_HOST'),
            'user' => getenv('EMAIL_USER'),
            'pass' => getenv('EMAIL_PASS'),
            'port' => getenv('EMAIL_PORT'),
        ];
    }
}
