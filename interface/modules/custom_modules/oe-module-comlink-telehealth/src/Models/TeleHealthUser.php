<?php

declare(strict_types=1);

/**
 * Represents a TeleHealth Provisioned User on the Comlink api.
 *
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Comlink Inc <https://comlinkinc.com/>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Comlink\OpenEMR\Modules\TeleHealthModule\Models;

use Comlink\OpenEMR\Modules\TeleHealthModule\DateTime;

class TeleHealthUser
{
    private $id;

    private $username;

    private $isPatient;

    private $dbRecordId;

    private $authToken;

    /**
     * @var \DateTime
     */
    private \DateTime $dateCreated;

    /**
     * @var \DateTime
     */
    private $dateRegistered;

    /**
     * @var \DateTime
     */
    private \DateTime $dateUpdated;

    private $isActive;

    private ?string $registrationCode = null;

    public function __construct()
    {
        $this->dateCreated = new \DateTime();
        $this->dateUpdated = new \DateTime();
    }

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
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username): static
    {
        $this->username = $username;
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

    /**
     * @return mixed
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * @param mixed $authToken
     */
    public function setAuthToken($authToken): static
    {
        $this->authToken = $authToken;
        return $this;
    }

    public function getDateCreated(): \DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @param mixed $dateCreated
     */
    public function setDateCreated($dateCreated): static
    {
        $this->dateCreated = $dateCreated;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDateRegistered(): ?\DateTime
    {
        return $this->dateRegistered;
    }

    /**
     * @param mixed $dateRegistered
     */
    public function setDateRegistered($dateRegistered): TeleHealthUser
    {
        $this->dateRegistered = $dateRegistered;
        return $this;
    }

    public function getDateUpdated(): \DateTime
    {
        return $this->dateUpdated;
    }

    /**
     * @param mixed $dateUpdated
     */
    public function setDateUpdated($dateUpdated): static
    {
        $this->dateUpdated = $dateUpdated;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param mixed $isActive
     */
    public function setIsActive($isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * @return string
     */
    public function getRegistrationCode(): ?string
    {
        return $this->registrationCode;
    }

    /**
     * @param string $registrationCode
     */
    public function setRegistrationCode(?string $registrationCode): TeleHealthUser
    {
        $this->registrationCode = $registrationCode;
        return $this;
    }
}
