<?php

namespace App\Interfaces;

interface MailerInterface
{
    public function setFrom(string $address): void;
    public function addAddress(string $address): void;
    public function setSubject(string $subject): void;
    public function setBody(string $body): void;
    public function send(): bool;
    public function addAttachment(string $filePath): void;
    public function clearAttachments(): void;
    public function clearAddresses(): void;
    public function addBCC(string $address): void;
    public function clearBCCs(): void;

    public function setSMTPSecure(string $encryption): void;
    public function isSMTP(): void;
    public function setHost(string $host): void;
    public function setSMTPAuth(bool $auth): void;
    public function setUsername(string $username): void;
    public function setPassword(string $password): void;
    public function setPort(int $port): void;
    public function setSMTPDebug(int $debug): void;
    public function setSMTPTimeout(int $timeout): void;
    public function setSMTPKeepAlive(bool $keepAlive): void;
    public function isHTML(bool $isHTML): void;
    public function setSMTPAutoTLS(bool $value): void;
}
