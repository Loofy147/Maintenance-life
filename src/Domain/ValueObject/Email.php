<?php
declare(strict_types=1);

namespace MaintenancePro\Domain\ValueObject;

final class Email
{
    private string $email;

    public function __construct(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: {$email}");
        }
        $this->email = strtolower($email);
    }

    public function toString(): string
    {
        return $this->email;
    }

    public function getDomain(): string
    {
        return substr($this->email, strpos($this->email, '@') + 1);
    }

    public function getLocalPart(): string
    {
        return substr($this->email, 0, strpos($this->email, '@'));
    }
}