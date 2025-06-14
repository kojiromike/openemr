<?php

declare(strict_types=1);

/**
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2025 Jerry Padgett <sjpadgett@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace OpenEMR\Modules\Dorn\EventSubscriber;

use OpenEMR\Events\Services\DornLabEvent;
use OpenEMR\Modules\Dorn\DornGenHl7Order;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DornLabSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DornLabEvent::GEN_HL7_ORDER => 'onGenHl7Order',
            DornLabEvent::GEN_BARCODE => 'onGenBarcode',
            DornLabEvent::SEND_ORDER => 'onSendOrder',
        ];
    }

    public function onGenHl7Order(DornLabEvent $dornLabEvent): void
    {
        try {
            $dornGenHl7Order = new DornGenHl7Order();
            $msg = $dornGenHl7Order->genHl7Order($dornLabEvent->getFormid(), $dornLabEvent->getHl7());
            $dornLabEvent->addMessage($msg);
        } catch (\Exception $exception) {
            $dornLabEvent->addMessage("GEN_HL7_ORDER error: " . $exception->getMessage());
        }
    }

    public function onGenBarcode(DornLabEvent $dornLabEvent): void
    {
        try {
            $dornGenHl7Order = new DornGenHl7Order();
            $msg = $dornGenHl7Order->genHl7OrderBarCode($dornLabEvent->getFormid(), $dornLabEvent->getReqStr());
            $dornLabEvent->addMessage($msg);
        } catch (\Exception $exception) {
            $dornLabEvent->addMessage("GEN_BARCODE error: " . $exception->getMessage());
        }
    }

    public function onSendOrder(DornLabEvent $dornLabEvent): void
    {
        try {
            $dornGenHl7Order = new DornGenHl7Order();
            $msg = $dornGenHl7Order->sendHl7Order($dornLabEvent->getPpid(), $dornLabEvent->getFormid(), $dornLabEvent->getHl7());
            $dornLabEvent->addMessage($msg);
        } catch (\Exception $exception) {
            $dornLabEvent->addMessage("SEND_ORDER error: " . $exception->getMessage());
        }
    }
}
