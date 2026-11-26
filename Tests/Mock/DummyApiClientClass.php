<?php

declare(strict_types=1);

namespace Compass\DatadogBundle\Tests\Mock;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A principal that is not the application's user entity, such as an API client authenticated by
 * an access token on a second firewall. It has none of the user entity's properties.
 */
class DummyApiClientClass implements UserInterface
{
    public function getRoles(): array
    {
        return ['ROLE_API_CLIENT'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return 'api-client';
    }
}
