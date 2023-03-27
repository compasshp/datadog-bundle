<?php

declare(strict_types=1);

namespace Compass\DatadogBundle\Event;

abstract class DatadogAuthenticationEvent
{
    private array $userMeta = [];

    public function addUserMeta($key, $value): self
    {
        $this->userMeta[$key] = $value;

        return $this;
    }

    public function getUserMeta(): array
    {
        return $this->userMeta;
    }
}