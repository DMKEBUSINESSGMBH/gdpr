<?php

declare(strict_types=1);

namespace GeorgRinger\Gdpr\Domain\Model\Dto;

class Search
{
    /** @var string */
    protected $searchWord = '';

    /** @var bool */
    protected $sensitiveOnly = false;

    public function getSearchWord(): string
    {
        return $this->searchWord;
    }

    public function setSearchWord(string $searchWord): void
    {
        $this->searchWord = $searchWord;
    }

    public function isSensitiveOnly(): bool
    {
        return (bool) $this->sensitiveOnly;
    }

    public function setSensitiveOnly(bool $sensitiveOnly): void
    {
        $this->sensitiveOnly = $sensitiveOnly;
    }
}
