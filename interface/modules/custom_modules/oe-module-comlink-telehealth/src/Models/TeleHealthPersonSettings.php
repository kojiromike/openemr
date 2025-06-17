<?php

declare(strict_types=1);

/**
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Comlink Inc <https://comlinkinc.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Comlink\OpenEMR\Modules\TeleHealthModule\Models;

class TeleHealthPersonSettings
{
    private $id;

    private $isPatient;

    private $dbRecordId;

    private ?\DateTime $dateCreated = null;

    private ?\DateTime $dateRegistered = null;

    private ?\DateTime $dateUpdated = null;

    /**
     * @var bool
     */
    private $isEnabled;

    /**
     * @var string Encrypted mobile app registration code used to identify the app registration location from Comlink's servers
     */
    private ?string $appRegistrationCode = null;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsPatient()
    {
        return $this->isPatient;
    }

    /**
     * @param mixed $isPatient
     */
    public function setIsPatient($isPatient): static
    {
        $this->isPatient = $isPatient;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDbRecordId()
    {
        return $this->dbRecordId;
    }

    /**
     * @param mixed $dbRecordId
     */
    public function setDbRecordId($dbRecordId): static
    {
        $this->dbRecordId = $dbRecordId;
        return $this;
    }

    public function getDateCreated(): \DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTime $dateCreated): TeleHealthPersonSettings
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    public function getDateRegistered(): \DateTime
    {
        return $this->dateRegistered;
    }

    public function setDateRegistered(\DateTime $dateRegistered): TeleHealthPersonSettings
    {
        $this->dateRegistered = $dateRegistered;
        return $this;
    }

    public function getDateUpdated(): \DateTime
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(\DateTime $dateUpdated): TeleHealthPersonSettings
    {
        $this->dateUpdated = $dateUpdated;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * @param mixed $isEnabled
     */
    public function setIsEnabled($isEnabled): static
    {
        $this->isEnabled = $isEnabled;
        return $this;
    }

    public function getAppRegistrationCode(): string
    {
        return $this->appRegistrationCode;
    }

    public function setAppRegistrationCode(string $appRegistrationCode): TeleHealthPersonSettings
    {
        $this->appRegistrationCode = $appRegistrationCode;
        return $this;
    }
}
