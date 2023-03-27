<?php

declare(strict_types=1);

namespace Compass\DatadogBundle\Event;

class DatadogRequestEvent
{
    private array $meta = [];

    public function addUserMeta($key, $value): self
    {
        $this->meta['usr.' . $key] = $value;

        return $this;
    }

    public function addMeta($key, $value): self
    {
        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }


}