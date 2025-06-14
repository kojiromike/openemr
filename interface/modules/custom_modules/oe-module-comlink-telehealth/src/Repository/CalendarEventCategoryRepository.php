<?php

declare(strict_types=1);

/**
 * Handles the retrieval of calendar categories that are specific to TeleHealth
 *
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Comlink Inc <https://comlinkinc.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Comlink\OpenEMR\Modules\TeleHealthModule\Repository;

use OpenEMR\Services\AppointmentService;

class CalendarEventCategoryRepository
{
    const TELEHEALTH_EVENT_CATEGORY_CONSTANT_IDS = ['comlink_telehealth_new_patient', 'comlink_telehealth_established_patient'];

    private array $categoryEvents = [];

    public function getEventCategoryForId($id)
    {
        $categoryEvents = $this->getEventCategories();
        if (isset($categoryEvents[$id])) {
            return $categoryEvents[$id];
        }

        return null;
    }

    public function getEventCategories($skipCache = false)
    {
        if (!$skipCache && !empty($this->categoryEvents)) {
            return $this->categoryEvents;
        }

        $appointmentService = new AppointmentService();
        $categories = $appointmentService->getCalendarCategories();
        $filteredCategories = [];
        foreach ($categories as $category) {
            if (in_array($category['pc_constant_id'], self::TELEHEALTH_EVENT_CATEGORY_CONSTANT_IDS)) {
                $filteredCategories[$category['pc_catid']] = $category;
            }
        }

        $this->categoryEvents = $filteredCategories;
        return $this->categoryEvents;
    }
}
