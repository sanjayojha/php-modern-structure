<?php

declare(strict_types=1);

namespace App\Controller;

use Twig\Environment;
use App\Service\MailerService; // Import the service
use App\Repository\UserRepository; // Import UserRepository
use App\Model\User;
use App\Exception\NotFoundException; // Import your custom exception

class HomeController
{
    private Environment $twig;
    private MailerService $mailerService; // Declare the service
    private UserRepository $userRepository; // Declare the UserRepository

    // PHP-DI will automatically inject these dependencies based on type hints
    public function __construct(Environment $twig, MailerService $mailerService, UserRepository $userRepository)
    {
        $this->twig = $twig;
        $this->mailerService = $mailerService;
        $this->userRepository = $userRepository; // Inject UserRepository
    }

    public function index(): string
    {
        // Example: Using the injected MailerService
        $emailSent = $this->mailerService->sendWelcomeEmail('test@example.com', 'JohnDoe');
        $emailStatus = $emailSent ? 'successfully sent' : 'failed to send';

        // Example: Using the injected UserRepository to fetch all users
        $users = $this->userRepository->findAll();

        return $this->twig->render('home.html.twig', [
            'pageTitle' => 'Welcome to My App!',
            'message' => "This is the homepage. Welcome email status: {$emailStatus}.",
            'users' => $users, // Pass users to the template
        ]);
    }

    public function userDetail(array $args): string
    {
        $id = (int) $args['id'];
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw new NotFoundException("User with ID {$id} not found.");
        }

        return $this->twig->render('user_detail.html.twig', [
            'pageTitle' => 'User Detail',
            'user' => $user,
        ]);
    }


    public function about(): string
    {
        return $this->twig->render('about.html.twig', [
            'pageTitle' => 'About Us',
            'appName' => 'Modern PHP App',
        ]);
    }

    public function hello(array $args): string
    {
        $name = $args['name'] ?? 'Guest';

        // Example: Triggering a custom 404 exception
        if ($name === 'bad-user') {
            throw new NotFoundException("The user '{$name}' could not be found.");
        }

        // Example: Triggering a generic error (e.g., division by zero)
        if ($name === 'error-user') {
            trigger_error("A custom error was triggered for user '{$name}'", E_USER_WARNING);
            $result = 1 / 0; // This will trigger a fatal error or warning depending on PHP settings
        }

        return $this->twig->render('hello.html.twig', [
            'pageTitle' => 'Greetings!',
            'name' => ucfirst($name),
        ]);
    }

    public function admin(): string
    {
        return $this->twig->render('admin_panel.html.twig', [
            'pageTitle' => 'Admin Panel',
            'message' => 'Welcome to the protected admin area!',
        ]);
    }

    public function secretReport(): string
    {
        // This will only be reached if AuthMiddleware and LoggingMiddleware pass
        return $this->twig->render('secret_report.html.twig', [
            'pageTitle' => 'Secret Report',
            'reportData' => 'Highly confidential report data from the database.',
        ]);
    }
}
