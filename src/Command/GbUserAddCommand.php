<?php

declare(strict_types=1);

namespace Gbere\SimpleAuth\Command;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Constraints as Assert;

final class GbUserAddCommand extends AbstractCommand
{
    private const QUESTION_MAX_ATTEMPTS = 3;

    protected static $defaultName = 'gb:user:add';

    protected function configure(): void
    {
        $this
            ->setDescription('Register a new user')
            ->addArgument('email', InputArgument::OPTIONAL)
        ;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');
        $email = $input->getArgument('email');
        $isEmailOk = false;
        $questionMaxAttempts = self::QUESTION_MAX_ATTEMPTS;
        if ($this->isTestEnv()) {
            $questionMaxAttempts = 1;
        }

        while (false === $isEmailOk) {
            if (null !== $email) {
                $emailConstraint = new Assert\Email();
                $errors = $this->validator->validate($email, $emailConstraint);
                if (0 < \count($errors)) {
                    $io->error($errors[0]->getMessage());

                    return 1;
                }
                if (null !== $this->findUserByEmail($email)) {
                    $io->error(sprintf('The email %s is already registered', $email));

                    return 1;
                }
                $isEmailOk = true;
            } else {
                $question = new Question('Enter the email address of the new user: ');
                $question->setValidator(function ($answer) {
                    if (null === $answer) {
                        throw new \Exception('The email address is required to create a new user');
                    }

                    return $answer;
                });
                $question->setMaxAttempts($questionMaxAttempts);
                $email = $helper->ask($input, $output, $question);
            }
        }

        $question = new Question('Enter a password: ');
        $question->setValidator(function ($answer) {
            if (null === $answer || '' === trim($answer)) {
                throw new \Exception('The password can\'t be empty');
            }

            return $answer;
        });
        $question->setMaxAttempts($questionMaxAttempts);
        if (false === $this->isTestEnv()) {
            $question->setHidden(true);
        }
        $password = $helper->ask($input, $output, $question);

        $question = new Question('Enter the name of the new user: ');
        $question->setValidator(function ($answer) {
            if (null === $answer) {
                throw new \Exception('The name field is required to create a new user');
            }

            return $answer;
        });
        $question->setMaxAttempts($questionMaxAttempts);
        $name = $helper->ask($input, $output, $question);

        $user = $this->userRepository->createUser();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword($this->userRepository->encodePassword($password));
        $this->userRepository->persistAndFlush($user);

        $io->success(sprintf('The new user with email %s, was successfully created', $user->getEmail()));

        return 0;
    }
}
