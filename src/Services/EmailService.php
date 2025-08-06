<?php
declare(strict_types=1);

namespace App\Services;

use App\Interfaces\MailerInterface;
use App\Interfaces\EmailInterface;
use App\Interfaces\FileDownloaderInterface;
use Exception;

class EmailService implements EmailInterface
{
    /** @var array */
    private $config;

    /** @var MailerInterface */
    private $mailer;

    /** @var FileDownloaderInterface */
    private $fileDownload;

    /** @var array */
    private $tmpAttachments = [];

    /**
     * @param array<string, string> $config
     * @param MailerInterface $mailer
     */
    public function __construct(array $config, MailerInterface $mailer, FileDownloaderInterface $fileDownload)
    {
        $this->config = $config;
        $this->mailer = $mailer;
        $this->fileDownload = $fileDownload;
        $this->setupMailer();
    }

    private function setupMailer(): void
    {
        $this->mailer->isSMTP();
        $this->mailer->setSMTPAuth(true);
        $this->mailer->setHost($this->config['host']);
        $this->mailer->setUsername($this->config['user']);
        $this->mailer->setPassword($this->config['pass']);
        $this->mailer->setPort((int) $this->config['port']);
        $this->mailer->setFrom($this->config['user']);
        $this->mailer->setSMTPSecure('');
        $this->mailer->setSMTPDebug(0);
        $this->mailer->setSMTPTimeout(5);
        $this->mailer->isHTML(true);
        $this->mailer->setSMTPAutoTLS(false);
    }

    /**
     * Sends an email.
     *
     * @param array|string $to The recipient's email address(es).
     * @param string $subject The subject of the email.
     * @param string $body The body of the email.
     * @param array|null $attachments Optional attachments to include in the email.
     * @param array|string|null $bcc The BCC recipient's email address(es).
     * @return bool True if the email was sent successfully, false otherwise.
     * @throws Exception If there was an error sending the email.
     */
    public function sendEmail(string|array $to, string $subject, string $body, $attachments = null, $bcc = null): array
    {
        try {
            $this->prepareRecipients($to);
            $this->prepareBCC($bcc);
            $this->prepareEmail($subject, $body);
            $statusAttachments = $this->prepareAttachments($attachments);

            if (!$statusAttachments['status']) return $statusAttachments;

            $send = $this->mailer->send();

            if ($send && !empty($this->tmpAttachments)) $this->deleteAttachments($this->tmpAttachments);

            return ['status' => $send, 'message' => $send ? 'Email sent successfully' : 'Email could not be sent'];
        } catch (Exception $e) {
            return ['message' => $e->getMessage(), 'status' => false];
        }
    }

    private function prepareRecipients(array|string $to): void
    {
        $this->mailer->clearAddresses();
        if (is_array($to)) {
            foreach ($to as $recipient) {
                $this->mailer->addAddress($recipient);
            }
        } else {
            $this->mailer->addAddress($to);
        }
    }

    private function prepareEmail(string $subject, string $body): void
    {
        $this->mailer->setSubject($subject);
        $this->mailer->setBody($body);
    }

    private function prepareBCC(string|array|null $bcc): void
    {
        $this->mailer->clearBCCs();
        if ($bcc) {
            if (is_array($bcc)) {
                foreach ($bcc as $bccRecipient) {
                    $this->mailer->addBCC($bccRecipient);
                }
            } else {
                $this->mailer->addBCC($bcc);
            }
        }
    }

    private function prepareAttachments(string|array|null $attachments): array
    {
        $this->mailer->clearAttachments();

        if (is_array($attachments)) {
            foreach ($attachments as $attachment) {
                $fileContent = $this->fileDownload->download($attachment);

                if ($fileContent) {
                    $this->mailer->addAttachment($fileContent);
                    $this->tmpAttachments[] = $fileContent;
                    return ['message' => 'Attachments are valid', 'status' => true];
                }

                return ['message' => "Invalid attachment", 'status' => false];
            }
        } elseif ($attachments) {
            $fileContent = $this->fileDownload->download($attachments);

            if ($fileContent) {
                $this->mailer->addAttachment($fileContent);
                $this->tmpAttachments[] = $fileContent;
                return ['message' => 'Attachments are valid', 'status' => true];
            }
            
            return ['message' => "Invalid attachment", 'status' => false];
        }

        return ['message' => 'No attachments', 'status' => true];
    }

    private function deleteAttachments(array $tmpAttachments): void
    {
        foreach ($tmpAttachments as $pathFile) {
            $this->fileDownload->delete($pathFile);
        }
    }
}
