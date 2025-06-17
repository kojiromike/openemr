<?php

declare(strict_types=1);

namespace Comlink\OpenEMR\Modules\TeleHealthModule\Events;

use Comlink\OpenEMR\Modules\TeleHealthModule\Models\NotificationSendAddress;

class TelehealthNotificationSendEvent
{
    const EVENT_HANDLE = "comlink.telehealth.notification.send";

    private ?string $messageId = null;

    /**
     * Note as this table changes this data record could change.  If you need type safety its recommended to use the pid.
     * @var array The patient record array from the patient_data table.
     */
    private ?array $patient = null;

    /**
     * @var The unique pid id of the patient
     */
    private ?\Comlink\OpenEMR\Modules\TeleHealthModule\Events\The $the = null;

    private ?string $subject = null;

    private ?string $joinLink = null;

    private ?\Comlink\OpenEMR\Modules\TeleHealthModule\Models\NotificationSendAddress $notificationSendAddress = null;

    /**
     * @var NotificationSendAddress[]
     */
    private ?array $sendToDestinations = null;

    /**
     * @var NotificationSendAddress[]
     */
    private ?array $replyToDestinations = null;

    private ?string $textBody = null;

    private ?string $htmlBody = null;

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function setMessageId(string $messageId): TelehealthNotificationSendEvent
    {
        $this->messageId = $messageId;
        return $this;
    }

    public function getPatient(): array
    {
        return $this->patient;
    }

    public function setPatient(array $patient): TelehealthNotificationSendEvent
    {
        $this->patient = $patient;
        return $this;
    }

    public function getPid(): The
    {
        return $this->the;
    }

    public function setPid(The $the): TelehealthNotificationSendEvent
    {
        $this->the = $the;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): TelehealthNotificationSendEvent
    {
        $this->subject = $subject;
        return $this;
    }

    public function getJoinLink(): string
    {
        return $this->joinLink;
    }

    public function setJoinLink(string $joinLink): TelehealthNotificationSendEvent
    {
        $this->joinLink = $joinLink;
        return $this;
    }

    public function getFrom(): NotificationSendAddress
    {
        return $this->notificationSendAddress;
    }

    /**
     * @param NotificationSendAddress $from
     */
    public function setFrom($destination, $name, $type = NotificationSendAddress::TYPE_EMAIL): TelehealthNotificationSendEvent
    {
        $this->notificationSendAddress = new NotificationSendAddress($destination, $name, $type);
        return $this;
    }

    /**
     * @return NotificationSendAddress[]
     */
    public function getSendToDestinations(): array
    {
        return $this->sendToDestinations;
    }

    /**
     * @param NotificationSendAddress[] $sendToDestinations
     */
    public function setSendToDestinations(array $sendToDestinations): TelehealthNotificationSendEvent
    {
        $this->sendToDestinations = $sendToDestinations;
        return $this;
    }

    /**
     * @param $destination
     * @param $name
     * @param string $type
     */
    public function addSendToDestination($destination, $name, $type = NotificationSendAddress::TYPE_EMAIL): TelehealthNotificationSendEvent
    {
        $this->sendToDestinations[] = new NotificationSendAddress($destination, $name, $type);
        return $this;
    }

    /**
     * @return NotificationSendAddress[]
     */
    public function getReplyToDestinations(): array
    {
        return $this->replyToDestinations;
    }

    /**
     * @param NotificationSendAddress[] $replyToDestinations
     */
    public function setReplyToDestinations(array $replyToDestinations): TelehealthNotificationSendEvent
    {
        $this->replyToDestinations = $replyToDestinations;
        return $this;
    }

    /**
     * @param $destination
     * @param $name
     * @param string $type
     */
    public function addReplyToDestination($destination, $name, $type = NotificationSendAddress::TYPE_EMAIL): TelehealthNotificationSendEvent
    {
        $this->replyToDestinations[] = new NotificationSendAddress($destination, $name, $type);
        return $this;
    }

    public function getTextBody(): string
    {
        return $this->textBody;
    }

    public function setTextBody(string $textBody): TelehealthNotificationSendEvent
    {
        $this->textBody = $textBody;
        return $this;
    }

    public function getHtmlBody(): string
    {
        return $this->htmlBody;
    }

    public function setHtmlBody(string $htmlBody): TelehealthNotificationSendEvent
    {
        $this->htmlBody = $htmlBody;
        return $this;
    }
}
