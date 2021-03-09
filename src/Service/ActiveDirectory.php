<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ActiveDirectory
{

    public function __construct(
        private Adapter $ldapAdapter,
        private EntityManagerInterface $entityManager,
        private UserPasswordEncoderInterface $passwordEncoder,
        private string $ldapServiceDn,
        string $ldapServiceUser,
        string $ldapServicePassword
    ) {
        $this->ldap = new Ldap($this->ldapAdapter);
        $this->ldap->bind(implode(',', [$ldapServiceUser, $ldapServiceDn]), $ldapServicePassword);
        $this->entryManager = $this->ldapAdapter->getEntryManager();
    }

    // get an Active Directory user entry via LDAP using user-submitted credentials (used to authenticate the user)
    public function getEntryFromActiveDirectory(string $username, string $password): false|Entry
    {
        $ldap = new Ldap($this->ldapAdapter);
        $search = false;
        try {
            $ldap->bind(implode(',', ['uid=' . $username, $this->ldapServiceDn]), $password);
            if ($this->ldapAdapter->getConnection()->isBound()) {
                $search = $ldap->query(
                    'dc=example,dc=com',
                    'uid=' . $username
                )->execute()->toArray();
            }
        } catch (ConnectionException) {
        }

        if ($search && count($search) === 1) {
            return $search[0];
        }

        return false;
    }

    // create a local user from an Active Directory entry
    public function createUserFromActiveDirectory(string $password, Entry $adEntry): User
    {
        $user = new User();
        $user->setUsername((string)($adEntry->getAttribute('uid') ?? [])[0]);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }

    // update a local user from an Active Directory entry
    public function updateUserFromActiveDirectory(User $user, Entry $adEntry, string $password): void
    {
        // here we can add additional roles or properties based on $adEntry data
        $userRoles = ['ROLE_USER'];
        $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
        $user->setRoles($userRoles);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

}