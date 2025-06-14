<?php

declare(strict_types=1);

/**
 * Handles participant invitation emails sent out for inviting third party patients to a telehealth session.
 *
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Comlink Inc <https://comlinkinc.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Comlink\OpenEMR\Modules\TeleHealthModule\Services;

use Comlink\OpenEMR\Modules\TeleHealthModule\Events\TelehealthNotificationSendEvent;
use Comlink\OpenEMR\Modules\TeleHealthModule\Models\NotificationSendAddress;
use Comlink\OpenEMR\Modules\TeleHealthModule\TelehealthGlobalConfig;
use MyMailer;
use OpenEMR\Common\Auth\OneTimeAuth;
use OpenEMR\Common\Logging\SystemLogger;
use OpenEMR\Services\LogoService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Environment;

class TeleHealthParticipantInvitationMailerService
{
    const MESSAGE_ID_TELEHEALTH_EXISTING_PATIENT = 'comlink-telehealth-invitation-existing-patient';

    const MESSAGE_ID_TELEHEALTH_NEW_PATIENT = 'comlink-telehealth-invitation-new-patient';

    private $publicPathFQDN;

    private \Twig\Environment $twigEnvironment;

    private \Comlink\OpenEMR\Modules\TeleHealthModule\TelehealthGlobalConfig $telehealthGlobalConfig;

    private \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcher;

    public function __construct(EventDispatcher $eventDispatcher, Environment $twigEnvironment, $publicPathFQDN, TelehealthGlobalConfig $telehealthGlobalConfig)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->twigEnvironment = $twigEnvironment;
        $this->publicPathFQDN = $publicPathFQDN;
        $this->telehealthGlobalConfig = $telehealthGlobalConfig;
    }

    public function sendInvitationToExistingPatient($patient, $session, $thirdPartyLaunchAction): void
    {
        $data = $this->getInvitationData($patient, $session, $thirdPartyLaunchAction);
        $htmlMsg = $this->twigEnvironment->render('comlink/emails/telehealth-invitation-existing.html.twig', $data);
        $plainMsg = $this->twigEnvironment->render('comlink/emails/telehealth-invitation-existing.text.twig', $data);
        $this->sendMessageToPatient(
            $htmlMsg,
            $plainMsg,
            $patient,
            $data['url'],
            self::MESSAGE_ID_TELEHEALTH_EXISTING_PATIENT
        );
    }

    /**
     * Returns the data that for the mailer invitation that can be used to manually send the invitation outside of
     * the OpenEMR mailer system. IE a user could take the html or link properties and send them via their own email.
     * @param $patient
     * @param $session
     * @param $thirdPartyLaunchAction
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getMailerInvitationForManualSend(array $patient, array $session, $thirdPartyLaunchAction): array
    {
        $data = $this->getInvitationData($patient, $session, $thirdPartyLaunchAction);
        $htmlMsg = $this->twigEnvironment->render('comlink/emails/telehealth-invitation-existing.html.twig', $data);
        $plainMsg = $this->twigEnvironment->render('comlink/emails/telehealth-invitation-existing.text.twig', $data);
        return [
            'link' => $data['url']
            ,'html' => $htmlMsg
            ,'text' => $plainMsg
            ,'pc_eid' => $session['pc_eid']
            ,'pid' => $patient['pid']
        ];
    }

    public function sendInvitationToNewPatient($patient, $session, $thirdPartyLaunchAction): void
    {
        $data = $this->getInvitationData($patient, $session, $thirdPartyLaunchAction);
        $htmlMsg = $this->twigEnvironment->render('comlink/emails/telehealth-invitation-new.html.twig', $data);
        $plainMsg = $this->twigEnvironment->render('comlink/emails/telehealth-invitation-new.text.twig', $data);

        $this->sendMessageToPatient(
            $htmlMsg,
            $plainMsg,
            $patient,
            $data['url'],
            self::MESSAGE_ID_TELEHEALTH_NEW_PATIENT
        );
    }

    private function getInvitationData($patient, array $session, $thirdPartyLaunchAction): array
    {
        $logoService = new LogoService();
        $logoPath = $this->telehealthGlobalConfig->getQualifiedSiteAddress() . $logoService->getLogo('core/login/primary');
        $name = $this->telehealthGlobalConfig->getOpenEMRName();
        return [
            'url' => $this->getJoinLink($patient, $session, $thirdPartyLaunchAction)
            ,'pc_eid' => $session['pc_eid']
            ,'launchAction' => $thirdPartyLaunchAction
            ,'salutation' => ($patient['fname'] ?? '') . ' ' . ($patient['lname'] ?? '')
            ,'logoPath' => $logoPath
            ,'logoAlt' => $name ?? 'OpenEMR'
            ,'title' => $name ?? 'OpenEMR'
        ];
    }

    private function getJoinLink(array $patient, array $session, $thirdPartyLaunchAction)
    {
        /**
         * $p[
         *    'pid' => '', // required for most onetime auth
         *   'target_link' => '', // Onetime endpoint
         *   'redirect_link' => '', // Where to redirect the user after auth
         *   'enabled_datetime' => 'NOW', // Use a datetime if wish to enable for a future date.
         *   'expiry_interval' => 'PT15M', // Always PTxx{Sec,Min,Day} PeriodTime
         *   'email' => '']
         */

        if ($this->telehealthGlobalConfig->isOneTimePasswordLoginEnabled()) {
            $parameters = [
                'pid' => $patient['pid']
                ,'redirect_link' => $this->publicPathFQDN . "index-portal.php?action=" . urlencode($thirdPartyLaunchAction)
                    . "&pc_eid=" . urlencode($session['pc_eid'])
                ,'email' => $patient['email']
                ,'expiry_interval' => $this->telehealthGlobalConfig->getOneTimePasswordTimeoutSetting()
            ];
            $oneTimeAuth = new OneTimeAuth();
            $oneTime = $oneTimeAuth->createPortalOneTime($parameters);
            if (isset($oneTime['encoded_link'])) {
                return $oneTime['encoded_link'];
            } else {
                (new SystemLogger())->errorLogCaller("Failed to generate encoded_link with onetime service");
                return $this->publicPathFQDN . "index-portal.php";
            }
        } else {
            // the index-portal will redirect the person to login before completing the action
            return $this->publicPathFQDN . "index-portal.php?action=" . urlencode($thirdPartyLaunchAction)
                . "&pc_eid=" . urlencode($session['pc_eid']);
        }

        return $oneTime;
    }

    private function sendMessageToPatient(string $htmlMsg, string $plainMsg, array $patient, string $joinLink, string $messageId): void
    {
        // TODO: @adunsulag need to check to see if the SMTP notifications are configured.  If they are not we need to
        // skip over the email notifications.
        if (!$this->telehealthGlobalConfig->isEmailNotificationsConfigured()) {
            (new SystemLogger())->info(
                self::class
                . "->sendMessageToPatient() skipping email notification as email notifications are not configured",
                ['pid' => $patient['pid'], 'messageId' => $messageId]
            );
            return;
        }

        $email_subject = xl('Join Telehealth Session');
        $email_sender = $this->telehealthGlobalConfig->getPatientReminderName();

        $pt_name = $patient['fname'] . ' ' . $patient['lname'];
        $pt_email = $patient['email'];

        $telehealthNotificationSendEvent = new TelehealthNotificationSendEvent();
        $telehealthNotificationSendEvent->setMessageId($messageId);
        $telehealthNotificationSendEvent->setPatient($patient);
        $telehealthNotificationSendEvent->setSubject($email_subject);
        $telehealthNotificationSendEvent->setJoinLink($joinLink);
        $telehealthNotificationSendEvent->setFrom($email_sender, $email_sender);
        $telehealthNotificationSendEvent->addSendToDestination($pt_email, $pt_name);
        $telehealthNotificationSendEvent->addReplyToDestination($email_sender, $email_sender);
        $telehealthNotificationSendEvent->setTextBody($plainMsg);
        $telehealthNotificationSendEvent->setHTMLBody($htmlMsg);

        $resultEvent = $this->eventDispatcher->dispatch($telehealthNotificationSendEvent, TelehealthNotificationSendEvent::EVENT_HANDLE);

        $throwExceptions = true;
        $myMailer = new MyMailer($throwExceptions);

        foreach ($resultEvent->getReplyToDestinations() as $sendToDestination) {
            if ($sendToDestination->getType() == NotificationSendAddress::TYPE_EMAIL) {
                $myMailer->addReplyTo($sendToDestination->getDestination(), $sendToDestination->getName());
            }
        }

        foreach ($resultEvent->getSendToDestinations() as $sendToDestination) {
            if ($sendToDestination->getType() == NotificationSendAddress::TYPE_EMAIL) {
                $myMailer->AddAddress($sendToDestination->getDestination(), $sendToDestination->getName());
            }
        }

        $sender = $resultEvent->getFrom();
        $myMailer->setFrom($sender->getDestination(), $sender->getName());

        $myMailer->Subject = $resultEvent->getSubject();
        $myMailer->AltBody = $resultEvent->getTextBody();

        $htmlBody = $resultEvent->getHTMLBody();
        if (!empty($htmlBody)) {
            $myMailer->MsgHTML($htmlBody);
            $myMailer->IsHTML(true);
        }

        // the invitation is critical and participants can't join w/o it.  We will send any failure exceptions
        // up the chain to fail everything
        // if the email does not go out
        $myMailer->Send();
    }
}
