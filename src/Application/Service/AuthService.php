<?php
declare(strict_types=1);

namespace MaintenancePro\Application\Service;

use MaintenancePro\Application\Service\Contract\AuthServiceInterface;
use MaintenancePro\Domain\Entity\User;
use MaintenancePro\Domain\Repository\UserRepositoryInterface;
use OTPHP\TOTP;

/**
 * Handles user authentication, session management, and two-factor authentication.
 */
class AuthService implements AuthServiceInterface
{
    private UserRepositoryInterface $userRepository;

    /**
     * AuthService constructor.
     *
     * @param UserRepositoryInterface $userRepository The repository for accessing user data.
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function login(string $username, string $password): bool
    {
        $user = $this->userRepository->findByUsername($username);

        if (!$user || !password_verify($password, $user->getPassword())) {
            return false;
        }

        if ($user->isTwoFactorEnabled()) {
            $_SESSION['2fa_user_id'] = $user->getId();
        } else {
            $_SESSION['user_id'] = $user->getId();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function logout(): void
    {
        session_destroy();
    }

    /**
     * {@inheritdoc}
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getLoggedInUser(): ?User
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->userRepository->findById((int) $_SESSION['user_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function verifyTwoFactorCode(User $user, string $code): bool
    {
        if (!$user->isTwoFactorEnabled()) {
            return false;
        }

        $totp = TOTP::createFromSecret($user->getTwoFactorSecret());
        $isValid = $totp->verify($code);

        if ($isValid) {
            unset($_SESSION['2fa_user_id']);
            $_SESSION['user_id'] = $user->getId();
        }

        return $isValid;
    }

    /**
     * {@inheritdoc}
     */
    public function generateTwoFactorSecret(User $user): string
    {
        $totp = TOTP::create();
        $secret = $totp->getSecret();
        $user->setTwoFactorSecret($secret);
        $this->userRepository->save($user);

        return $secret;
    }
}