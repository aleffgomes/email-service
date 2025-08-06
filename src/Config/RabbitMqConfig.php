<?php

namespace App\Config;

class RabbitMqConfig
{
    public static function getConfig(): array
    {
        return [
            'host' => getenv('RABBITMQ_HOST'),
            'port' => getenv('RABBITMQ_PORT'),
            'user' => getenv('RABBITMQ_USER'),
            'pass' => getenv('RABBITMQ_PASS'),
            'queue' => getenv('RABBITMQ_QUEUE'),
        ];
    }
}
