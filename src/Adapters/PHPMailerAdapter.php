<?php

namespace App\Adapters;

use PHPMailer\PHPMailer\PHPMailer;
use App\Interfaces\MailerInterface;

class PHPMailerAdapter implements MailerInterface
{
    private $mailer;
    protected $ErrorInfo;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->CharSet = 'UTF-8';
        $this->ErrorInfo = $this->mailer->ErrorInfo;
    }

    public function setFrom(string $address): void
    {
        $this->mailer->setFrom($address, 'TPTEC');
    }

    public function addAddress(string $address): void
    {
        $this->mailer->addAddress($address);
    }

    public function setSubject(string $subject): void
    {
        $this->mailer->Subject = $subject;
    }

    public function setBody(string $body): void
    {
        $this->mailer->Body = $body;
    }

    public function send(): bool
    {
        return $this->mailer->send();
    }

    public function addAttachment(string $filePath): void
    {
        $this->mailer->addAttachment($filePath);
    }

    public function clearAttachments(): void
    {
        $this->mailer->clearAttachments();
    }

    public function addBCC(string $address): void
    {
        $this->mailer->addBCC($address);
    }

    public function clearBCCs(): void
    {
        $this->mailer->clearBCCs();
    }

    public function clearAddresses(): void
    {
        $this->mailer->clearAddresses();
    }

    public function setSMTPSecure(string $encryption): void
    {
        $this->mailer->SMTPSecure = $encryption;
    }

    public function isSMTP(): void
    {
        $this->mailer->isSMTP();
    }

    public function setHost(string $host): void
    {
        $this->mailer->Host = $host;
    }

    public function setSMTPAuth(bool $auth): void
    {
        $this->mailer->SMTPAuth = $auth;
    }

    public function setUsername(string $username): void
    {
        $this->mailer->Username = $username;
    }

    public function setPassword(string $password): void
    {
        $this->mailer->Password = $password;
    }

    public function setPort(int $port): void
    {
        $this->mailer->Port = $port;
    }

    public function setSMTPDebug(int $debug): void
    {
        $this->mailer->SMTPDebug = $debug;
    }

    public function setSMTPTimeout(int $timeout): void
    {
        $this->mailer->Timeout = $timeout;
    }

    public function setSMTPKeepAlive(bool $keepAlive): void
    {
        $this->mailer->SMTPKeepAlive = $keepAlive;
    }

    public function isHTML(bool $isHTML): void
    {
        $this->mailer->isHTML($isHTML);
    }

    public function setSMTPAutoTLS(bool $value): void // @TODO: Add support for SMTPAutoTLS
    {
        $this->mailer->SMTPAutoTLS = $value;
    }
}
