<?php

declare(strict_types=1);

namespace Compass\DatadogBundle\Tests\Mock;

use Symfony\Component\Security\Core\User\UserInterface;

class DummyUserClass implements UserInterface
{
    private string $firstName = 'John';
    private string $lastName = 'Doe';

    public function __toString(): string
    {
        return '';
    }

    public function getRoles(): array
    {
        return [];
    }

    public function eraseCredentials()
    {

    }

    public function getUserIdentifier(): string
    {
        return "mike@compasshp.org";
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

}