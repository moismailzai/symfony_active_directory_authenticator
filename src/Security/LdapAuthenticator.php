<?php

namespace App\Security;

use App\Entity\User;
use App\Service\ActiveDirectory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LdapAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private ActiveDirectory $activeDirectory,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private EntityManagerInterface $entityManager,
        private UserPasswordEncoderInterface $passwordEncoder,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function authenticate(Request $request): PassportInterface
    {

        $csrfToken = $request->request->get('_csrf_token');
        $password = $request->request->get('password');
        $username = $request->request->get('username');

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $username
        );

        // get a local user entity if a matching one exists
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);

        // get an Active Directory entry if one exists
        $ldapEntry = $this->activeDirectory->getEntryFromActiveDirectory($username, $password);

        if (!$user && $ldapEntry && ($ldapEntry::class === Entry::class)) {
            // if a local user doesn't exist, but an Active Directory one does, create a local user
            $user = $this->activeDirectory->createUserFromActiveDirectory($password, $ldapEntry);
        } elseif (!$ldapEntry) {
            // if an Active Directory user doesn't exist, throw an error
            throw new CustomUserMessageAuthenticationException('No such user in Active Directory.');
        }

        if ($user !== null) {
            // sync the local user with the Active Directory user if both exist
            $this->activeDirectory->updateUserFromActiveDirectory($user, $ldapEntry, $password);
        }

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('login_form', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $session = $request->getSession();

        // if a target path has been specified, honor it
        if ($targetPath = $this->getTargetPath($session, $firewallName)) {
            $this->removeTargetPath($session, $firewallName);
            return new RedirectResponse($targetPath);
        }

        // otherwise, divert to the front page
        return new RedirectResponse('/');
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('login');
    }
}