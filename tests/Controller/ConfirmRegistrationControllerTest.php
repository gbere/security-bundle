<?php

declare(strict_types=1);

namespace Gbere\SimpleAuth\Tests\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Gbere\SimpleAuth\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConfirmRegistrationControllerTest extends WebTestCase
{
    /** @var string */
    private const EMAIL = 'confirm-my-registration@test.com';

    /** @var User|null */
    private $user;

    public function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @throws Exception
     */
    public function testConfirmRegistrationForm(): void
    {
        $this->removeTestUserIfExist();
        $this->createUserToConfirmRegistration();
        $client = static::createClient();
        $client->request('GET', $this->generateConfirmRegistrationRoute());
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHeaderSame($email, 'To', self::EMAIL);
        $this->assertEmailTextBodyContains($email, 'Welcome');
        $this->assertResponseRedirects('/login');
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function removeTestUserIfExist(): void
    {
        /** @var EntityManager $manager */
        $manager = self::$container->get('doctrine.orm.entity_manager');
        /** @var User|null $user */
        $user = $manager->getRepository(User::class)->findOneBy(['email' => self::EMAIL]);
        if (null !== $user) {
            $manager->remove($user);
            $manager->flush();
        }
    }

    /**
     * @throws Exception
     */
    private function createUserToConfirmRegistration(): void
    {
        $this->user = (new User())
            ->setEmail(self::EMAIL)
            ->setPassword('')
            ->generateToken()
            ->hasEnabled(false)
        ;
        /** @var EntityManager $manager */
        $manager = self::$container->get('doctrine.orm.entity_manager');
        $manager->persist($this->user);
        $manager->flush();
    }

    private function generateConfirmRegistrationRoute(): string
    {
        return self::$container->get('router')->generate(
            'gbere_auth_confirm_registration',
            ['token' => $this->user->getConfirmationToken()]
        );
    }
}