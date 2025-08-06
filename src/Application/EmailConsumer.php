<?php

declare(strict_types=1);

namespace App\Application;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use App\Interfaces\EmailInterface;
use League\CLImate\CLImate;
use Swoole\Coroutine;
use App\Services\MessageValidatorService;

class EmailConsumer
{
    private $rabbitMqConfig;
    private $connection;
    private $channel;
    private $emailService;
    private $queue;
    private $logger;
    private $climate;
    private $validator;
    private $stats = [
        'consumed' => 0,
        'successful' => 0,
        'failed' => 0,
        'total' => 0,
    ];

    public function __construct(array $rabbitMqConfig, EmailInterface $emailService, LoggerInterface $logger, CLImate $climate)
    {
        ob_implicit_flush(true);
        $this->rabbitMqConfig = $rabbitMqConfig;
        $this->climate = $climate;
        $this->emailService = $emailService;
        $this->logger = $logger;
        $this->validator = new MessageValidatorService();
    }

    public function run(string $queue): void
    {
        while (true) {
            try {
                $this->connect();

                $this->queue = $queue;
                $this->channel->queue_declare($queue, false, true, false, false);

                $callback = function ($msg) {
                    $this->processMessage($msg);
                };

                $this->channel->basic_qos(null, 1, false);
                $this->channel->basic_consume($queue, '', false, false, false, false, $callback);

                while ($this->channel->is_consuming()) {
                    $this->channel->wait();
                    $this->logStats();
                }
            } catch (\Exception $e) {
                $this->logger->error('Error in RabbitMQ connection or consumption loop.', ['error' => $e->getMessage()]);
                $this->climate->red('Error detected. Reconnecting to RabbitMQ...');
                sleep(5);
            }
        }
    }

    private function connect(): void
    {
        $rabbitMqConfig = $this->rabbitMqConfig;
        try {
            $heartbeat = 30;
            $this->connection = new AMQPStreamConnection(
                $rabbitMqConfig['host'],
                $rabbitMqConfig['port'],
                $rabbitMqConfig['user'],
                $rabbitMqConfig['pass'],
                '/',
                false, // Insist
                'AMQPLAIN', // Auth method
                null, // Login response
                'en_US', // Locale
                0, // Read timeout
                0, // Write timeout
                null, // Context
                false, // Keepalive
                $heartbeat // Heartbeat interval
            );
            $this->channel = $this->connection->channel();

            $this->logStats();
            $this->logger->info("Connection established to RabbitMQ on {$rabbitMqConfig['host']}:{$rabbitMqConfig['port']}");

        } catch (\Exception $e) {
            $this->logger->error('Error connecting to RabbitMQ.', ['error' => $e->getMessage()]);
            $this->climate->red('Error connecting to RabbitMQ. Verify that RabbitMQ is running.');
            $this->climate->darkGray('Message: ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }
    }

    private function processMessage(AMQPMessage $msg): void
    {
        $this->handleEmail($msg);
    }

    private function handleEmail(AMQPMessage $msg): void
    {
        $messageBody = json_decode($msg->body, true) ?? [];
        $isValid = $this->validator->validate($messageBody);
        if (!$isValid['status']) {
            $this->handleInvalidMessage($msg, $messageBody, $isValid);
            return;
        }

        Coroutine::create(function ($msg, $messageBody) {

            try {
                $to = $messageBody['to'];
                $subject = $messageBody['subject'];
                $body = $messageBody['body'];
                $attachments = $messageBody['attachments'] ?? [];
                $bcc = $messageBody['bcc'] ?? null;
                $bccObject = is_array($bcc) ? json_encode($bcc) : $bcc;
                $this->stats['consumed']++;

                $this->logger->info('Message received:', $this->messageBodyToLog($messageBody));

                $sendEmail = $this->emailService->sendEmail($to, $subject, $body, $attachments, $bcc);
                if ($sendEmail['status']) {
                    $this->logger->info("Email sent to {$bccObject} successfully", $sendEmail);
                    $this->stats['successful']++;
                    $msg->ack();
                } else {
                    $this->logger->error("Email could not be sent", $sendEmail);
                    $this->stats['failed']++;
                    $msg->nack();
                }
            } catch (Exception $e) {
                $msg->nack(true);
                $this->logger->error("Email could not be sent: {$e->getMessage()}");
                $this->stats['failed']++;
            } finally {
                $this->stats['total']++;
            }

        }, $msg, $messageBody);
    }

    private function logStats(): void
    {
        $consumed = $this->stats['consumed'];
        $successful = $this->stats['successful'];
        $failed = $this->stats['failed'];
        $total = $this->stats['total'];

        $this->climate->clear();
        $this->climate->yellow(' [*] Waiting for messages. To exit press CTRL+C' . PHP_EOL);
        $this->climate->lightBlue("Consumed Messages: <light_gray>$consumed</light_gray>");
        $this->climate->lightGreen("Sent: <light_gray>$successful</light_gray>");
        $this->climate->lightRed("Failed: <light_gray>$failed</light_gray>");
        $this->climate->lightBlue("Total: <light_gray>$total</light_gray>");
    }

    private function messageBodyToLog(array $messageBody): array
    {
        $subject = $messageBody['subject'] ?? 'No subject';
        $to = json_encode($messageBody['to']) ?? 'No recipient';
        $bcc = json_encode($messageBody['bcc']) ?? 'No bcc';
        return [$subject . ' - ' . $to . ' - ' . $bcc];
    }

    private function handleInvalidMessage(AMQPMessage $msg, array $messageBody, array $isValid): void
    {
        $this->logger->error("Invalid message received:", $isValid);
        $this->climate->red("Invalid message format: " . $this->messageBodyToLog($messageBody)[0]);
        $this->stats['failed']++;
        $msg->nack();
    }
}
