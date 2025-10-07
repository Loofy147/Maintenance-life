<?php
declare(strict_types=1);

namespace Tests\Integration\Application\Service;

use MaintenancePro\Application\Service\AuthService;
use MaintenancePro\Domain\Entity\User;
use MaintenancePro\Domain\Repository\UserRepositoryInterface;
use MaintenancePro\Infrastructure\Repository\SqliteUserRepository;
use PHPUnit\Framework\TestCase;
use PDO;

class AuthServiceTest extends TestCase
{
    private PDO $pdo;
    private UserRepositoryInterface $userRepository;
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up in-memory SQLite database
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Set up repository and service
        $this->userRepository = new SqliteUserRepository($this->pdo);
        $this->authService = new AuthService($this->userRepository);
    }

    public function testGetLoggedInUserReturnsCorrectUser()
    {
        // 1. Create and save a user
        $username = 'testuser';
        $password = 'password123';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $user = new User($username, $hashedPassword);
        $this->userRepository->save($user);

        // Retrieve the user to get their assigned ID
        $savedUser = $this->userRepository->findByUsername($username);
        $this->assertNotNull($savedUser, "Failed to save or retrieve user.");
        $userId = $savedUser->getId();
        $this->assertNotNull($userId, "Saved user ID should not be null.");

        // 2. Log the user in
        $loginSuccess = $this->authService->login($username, $password);
        $this->assertTrue($loginSuccess, "Login should be successful.");

        // 3. Get the logged-in user
        $loggedInUser = $this->authService->getLoggedInUser();

        // 4. Assert the user is correct
        $this->assertNotNull($loggedInUser, "getLoggedInUser should not return null.");
        $this->assertInstanceOf(User::class, $loggedInUser);
        $this->assertEquals($userId, $loggedInUser->getId(), "Returned user ID should match the logged-in user.");
    }
}