<?php

declare(strict_types=1);

/**
 * GeneratedCcdaResult holds the generated ccda content.
 *
 * @package openemr
 * @link      http://www.open-emr.org
 * @author    Stephen Nielson <snielson@discoverandchange.com>
 * @copyright Copyright (c) 2022 Discover and Change <snielson@discoverandchange.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
namespace Carecoordination\Model;

class GeneratedCcdaResult
{
    /**
     * @var int The database id from the ccda table for the generated ccda.
     */
    private int $id;

    /**
     * @var string The database uuid from the ccda table for the generated ccda.
     */
    private string $uuid;

    /**
     * @var string The xml content for the generated ccda
     */
    private string $content;

    /**
     * @var string The human readable file name for the generated ccda
     */
    private string $filename;

    public function __construct(int $id, string $uuid, string $filename, string $content)
    {
        $this->id = $id;
        $this->uuid = $uuid;
        $this->content = $content;
        $this->filename = $filename;
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): GeneratedCcdaResult
    {
        $this->id = $id;
        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): GeneratedCcdaResult
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): GeneratedCcdaResult
    {
        $this->content = $content;
        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): GeneratedCcdaResult
    {
        $this->filename = $filename;
        return $this;
    }
}
