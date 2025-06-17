<?php

declare(strict_types=1);

/**
 * Represents the export job that is being processed and stored in the database.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 *
 * @author    Stephen Nielson <snielson@discoverandchange.com
 * @copyright Copyright (c) 2023 OpenEMR Foundation, Inc
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace OpenEMR\Modules\EhiExporter\Models;

use OpenEMR\Services\Utils\DateFormatterUtils;
use Ramsey\Uuid\Rfc4122\UuidV4;

class EhiExportJob
{
    public function __construct()
    {
        $this->user_id = $_SESSION['authUserID'];
        $this->creation_date = date("Y-m-d H:i:s");
        $this->completion_date = date("Y-m-d H:i:s");
    }

    private ?int $ehi_export_job_id = null;

    public string $uuid;

    public int $user_id;

    /**
     * @var "processing"|"failed"|"completed"
     */
    private string $status = "processing";

    public string $creation_date;

    public string $completion_date;

    /**
     * @var int[]
     */
    private array $pids = [];

    public bool $include_patient_documents = true;

    /**
     * @var EhiExportJobTask[]
     */
    private array $jobTasks = [];

    /**
     * @var int The maximum size in bytes that a document zip file can be for an export.  The default is 500
     */
    private int $document_limit_size = 524288000;

    public function getDocumentLimitSize(): int
    {
        return $this->document_limit_size;
    }

    public function setDocumentLimitSize(int $size): void
    {
        $this->document_limit_size = $size;
    }

    public function setId(int $id): void
    {
        $this->ehi_export_job_id = $id;
    }

    public function getId(): ?int
    {
        return $this->ehi_export_job_id;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, ['processing', 'completed', 'failed'])) {
            throw new \InvalidArgumentException("Invalid status");
        }

        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getJobTasks(): array
    {
        return $this->jobTasks;
    }

    public function addJobTask(EhiExportJobTask $ehiExportJobTask): void
    {
        $this->jobTasks[] = $ehiExportJobTask;
    }

    public function addPatientId($pid): void
    {
        $this->pids[] = $pid;
    }

    public function addPatientIdList(array $pids): void
    {
        $this->pids = array_map('intval', $pids); // make sure we don't get invalid pids here
    }

    public function getPatientIds(): array
    {
        return $this->pids;
    }

    public function hasPatientIds(): bool
    {
        return $this->pids !== [];
    }
}
