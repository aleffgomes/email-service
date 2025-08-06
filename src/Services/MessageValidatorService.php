<?php

declare(strict_types=1);

namespace App\Services;


/**
 * Class MessageValidatorService
 *
 * This class is responsible for validating the structure and content of a message.
 */
class MessageValidatorService
{
    /**
     * Validates a message body.
     *
     * @param array|null $messageBody The message body to validate.
     * @return array Returns an array with a message and a status indicating the validation result.
     */
    public function validate(array $messageBody = null): array
    {
        // Check if the message body is empty
        if (empty($messageBody)) return ['message' => 'Message is empty', 'status' => false];
        

        // Check if the message body is an array
        if (!is_array($messageBody)) return ['message' => 'Message is not an array', 'status' => false];
        

        // Check for missing required fields
        if (empty($messageBody['to']) || empty($messageBody['subject']) || empty($messageBody['body'])) {
            return [
                'message' => 'Required fields missing (to, subject, body)',
                'status' => false
            ];
        }

        // Validate recipients
        $validationResult = $this->validateEmails($messageBody['to']);
        if (!$validationResult['status']) return $validationResult;
        

        // Validate email subject and body
        if (!is_string($messageBody['subject']) || !is_string($messageBody['body'])) {
            return [
                'message' => 'Subject or body is not a string',
                'status' => false
            ];
        }

        // Validate BCC
        if (isset($messageBody['bcc'])) {
            $validationResult = $this->validateEmails($messageBody['bcc']);
            if (!$validationResult['status']) return $validationResult;
        }

        // Validate attachments
        if (isset($messageBody['attachments'])) {
            $validationResult = $this->validateAttachments($messageBody['attachments']);
            if (!$validationResult['status']) return $validationResult;
        }

        return ['message' => 'Validation successful', 'status' => true];
    }

    /**
     * Validates email addresses.
     *
     * @param mixed $emails The email addresses to validate.
     * @return array Returns an array with a message and a status indicating the validation result.
     */
    private function validateEmails(string|array $emails): array
    {
        if (is_array($emails)) {
            foreach ($emails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['message' => "Invalid emails: $email", 'status' => false];
            }
        } else {
            if (!filter_var($emails, FILTER_VALIDATE_EMAIL)) return ['message' => "Invalid email: $emails", 'status' => false];
        }

        return ['message' => 'Emails are valid', 'status' => true];
    }

    /**
     * Validates attachments.
     *
     * @param mixed $attachments The attachments to validate.
     * @return array Returns an array with a message and a status indicating the validation result.
     */
    private function validateAttachments(string|array $attachments): array
    {
        if (is_array($attachments)) {
            foreach ($attachments as $attachment) {
                if (!$this->isValidUrlOrBase64($attachment)) return ['message' => "Invalid attachments: $attachment", 'status' => false];
            }
        } else {
            if (!$this->isValidUrlOrBase64($attachments)) return ['message' => "Invalid attachment: $attachments", 'status' => false];
        }

        return ['message' => 'Attachments are valid', 'status' => true];
    }

    /**
     * Validates if a string is a valid URL or base64 encoded data.
     *
     * @param string $str The string to validate.
     * @return bool Returns true if the string is a valid URL or base64 encoded data, false otherwise.
     */
    private function isValidUrlOrBase64(string $str): bool
    {
        // Check if the string is a valid URL
        if (filter_var($str, FILTER_VALIDATE_URL)) return true;

        $base64Data = preg_replace('/^data:[a-zA-Z0-9\/\-\+]+;base64,/', '', $str);

        // Check if the length of the base64 string is divisible by 4
        $decodedData = base64_decode($base64Data, true);
        if ($decodedData === false) return false;

        // Check if the decoded data and the re-encoded data are equal to the original
        if (base64_encode($decodedData) !== $base64Data) return false;

        return true;
    }
}
