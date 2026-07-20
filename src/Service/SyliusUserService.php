<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Service;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Sylius\Component\User\Security\PasswordUpdaterInterface;

class SyliusUserService
{
    private const API_USER_EMAIL = 'furgonetka-api@sylius-plugin.invalid';
    private const API_USER_NAME = 'furgonetka-api';

    public function __construct(
        private UserRepositoryInterface $adminUserRepository,
        private FactoryInterface $adminUserFactory,
        private EntityManagerInterface $adminEntityManager,
        private PasswordUpdaterInterface $passwordUpdater,
    ) {
    }

    public function getOrCreateApiUser(): AdminUserInterface
    {
        $existingApiUser = $this->adminUserRepository->findOneBy([
            'username' => self::API_USER_NAME,
        ]);

        $plainPassword = $this->createPassword();

        /** @var AdminUserInterface $apiUser */
        $apiUser = $existingApiUser ?: $this->adminUserFactory->createNew();
        $apiUser->setUsername(self::API_USER_NAME);
        $apiUser->setEmail(self::API_USER_EMAIL);
        $apiUser->setPlainPassword($plainPassword);
        $apiUser->setLocaleCode('pl_PL');
        $apiUser->addRole('ROLE_API_ACCESS');
        $apiUser->setEnabled(true);

        $this->passwordUpdater->updatePassword($apiUser);

        $this->adminEntityManager->persist($apiUser);
        $this->adminEntityManager->flush();

        $apiUser->setPlainPassword($plainPassword);

        return $apiUser;
    }

    private function createPassword(): string
    {
        return str_shuffle(
            rtrim(
                base64_encode(bin2hex(openssl_random_pseudo_bytes(5))),
                '='
            ) . strtoupper(bin2hex(openssl_random_pseudo_bytes(7)))
            . bin2hex(openssl_random_pseudo_bytes(13))
        );
    }
}
