<?php

declare(strict_types=1);

/**
 * Responsible for rendering TeleHealth features on the patient portal
 *
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Comlink Inc <https://comlinkinc.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Comlink\OpenEMR\Modules\TeleHealthModule\Controller;

use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\CalendarEventCategoryRepository;
use Comlink\OpenEMR\Modules\TeleHealthModule\Repository\TeleHealthSessionRepository;
use Comlink\OpenEMR\Modules\TeleHealthModule\TelehealthGlobalConfig;
use Comlink\OpenEMR\Modules\TeleHealthModule\Util\CalendarUtils;
use OpenEMR\Events\PatientPortal\AppointmentFilterEvent;
use OpenEMR\Services\AppointmentService;
use OpenEMR\Services\ListService;
use OpenEMR\Services\UserService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use OpenEMR\Events\PatientPortal\RenderEvent;
use Symfony\Component\EventDispatcher\GenericEvent;
use Twig\Environment;

class TeleHealthPatientPortalController
{
    private \Twig\Environment $twigEnvironment;

    private $assetPath;

    private \Comlink\OpenEMR\Modules\TeleHealthModule\TelehealthGlobalConfig $telehealthGlobalConfig;

    public function __construct(Environment $twigEnvironment, $assetPath, TelehealthGlobalConfig $telehealthGlobalConfig)
    {
        $this->twigEnvironment = $twigEnvironment;
        $this->assetPath = $assetPath;
        $this->telehealthGlobalConfig = $telehealthGlobalConfig;
    }

    public function subscribeToEvents(EventDispatcher $eventDispatcher): void
    {
        $eventDispatcher->addListener(AppointmentFilterEvent::EVENT_NAME, [$this, 'filterPatientAppointment']);
        $eventDispatcher->addListener(RenderEvent::EVENT_SECTION_RENDER_POST, [$this, 'renderTeleHealthPatientVideo']);
    }

    public function renderTeleHealthPatientVideo(GenericEvent $genericEvent): void
    {

        $data = [
            'assetPath' => $this->assetPath,
            'debug' => $this->telehealthGlobalConfig->isDebugModeEnabled()
        ];
        echo $this->twigEnvironment->render('comlink/patient-portal.twig', $data);
    }

    public function filterPatientAppointment(AppointmentFilterEvent $appointmentFilterEvent): void
    {
        $dbRecord = $appointmentFilterEvent->getDbRecord();
        $appointment = $appointmentFilterEvent->getAppointment();
        // 'appointmentDate' => $dayname . ', ' . $row['pc_eventDate'] . ' ' . $disphour . ':' . $dispmin . ' ' . $dispampm,
        $dateTime = \DateTime::createFromFormat("Y-m-d H:i:s", $dbRecord['pc_eventDate']
            . " " . $dbRecord['pc_startTime']);

        $appointmentService = new AppointmentService();

        $appointment['showTelehealth'] = false;
        if (
            $dateTime !== false && CalendarUtils::isAppointmentDateTimeInSafeRange($dateTime)
            // since this hits the database we do this one last
        ) {
            if (
                $appointmentService->isCheckOutStatus($dbRecord['pc_apptstatus'])
                || $appointmentService->isPendingStatus($dbRecord['pc_apptstatus'])
            ) {
                $appointment['showTelehealth'] = false;
            } else {
                $appointment['showTelehealth'] = true;
            }
        }

        $appointmentFilterEvent->setAppointment($appointment);
    }
}
