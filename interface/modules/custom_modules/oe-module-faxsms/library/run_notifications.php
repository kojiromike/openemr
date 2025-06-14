<?php

declare(strict_types=1);

function doEmailNotificationTask(): void
{
    $scheduled_task_flag = 1;
    $_GET['type'] = 'email';
    $_GET['site'] = $_SESSION['site_id'];

    require_once(__DIR__ . "/rc_sms_notification.php");
}

function doSmsNotificationTask(): void
{
    $scheduled_task_flag = 1;
    $_GET['type'] = 'sms';
    $_GET['site'] = $_SESSION['site_id'];

    require_once(__DIR__ . "/rc_sms_notification.php");
}
