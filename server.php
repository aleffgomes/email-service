<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use League\CLImate\CLImate;

use App\Config\RabbitMqConfig;
use App\Config\EmailConfig;
use App\Services\EmailService;
use App\Adapters\PHPMailerAdapter;
use App\Adapters\FileDownloaderAdapter;
use App\Application\EmailConsumer;

require_once __DIR__ . '/vendor/autoload.php';

const LOG_DIR = __DIR__ . '/logs/';

$rabbitMqConfig = RabbitMqConfig::getConfig();
$emailConfig = EmailConfig::getConfig();
$queue = $rabbitMqConfig['queue'];

$emailService = new EmailService($emailConfig, new PHPMailerAdapter(), new FileDownloaderAdapter());

$logger = new Logger('email-service');
$file = LOG_DIR . date('Y-m-d') . '.log';
$logger->pushHandler(new StreamHandler($file, Logger::DEBUG));

$consumer = new EmailConsumer($rabbitMqConfig, $emailService, $logger, new CLImate());

$consumer->run($queue);