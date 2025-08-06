<?php 

namespace App\Interfaces;

interface EmailInterface
{
    public function sendEmail(string $to, string $subject, string $body): array;
}