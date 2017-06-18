<?php
namespace Hallboav\Security\Guard;

use Hallboav\Security\Guard\Helper\JsonWebTokenExtractor;
use Lcobucci\JWT as JsonWebToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class JsonWebTokenGuardAuthenticator extends AbstractGuardAuthenticator
{
    private $tokenExtractor;
    private $constraint;
    private $signer;
    private $secret;
    private $token;

    public function __construct(
        JsonWebTokenExtractor $tokenExtractor,
        JsonWebToken\ValidationData $constraint,
        JsonWebToken\Signer $signer,
        $secret
    ) {
        $this->tokenExtractor = $tokenExtractor;
        $this->constraint = $constraint;
        $this->signer = $signer;
        $this->secret = $secret;
        $this->token = null;
    }

    public function getCredentials(Request $request)
    {
        return $this->getToken($request);
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        if (!$credentials->verify($this->signer, $this->secret)) {
            throw $this->createUnauthorizedHttpException('Token provided does not belong to us.');
        }

        if (!$credentials->validate($this->constraint)) {
            throw $this->createUnauthorizedHttpException('Invalid token.');
        }

        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw $this->createAccessDeniedHttpException();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $token->setAttribute('security.jwt.token', $this->getToken($request));
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        throw $this->createUnauthorizedHttpException();
    }

    public function supportsRememberMe()
    {
        return false;
    }

    private function getToken(Request $request)
    {
        if (is_null($this->token)) {
            $this->token = $this->tokenExtractor->extract($request);
        }

        return $this->token;
    }

    private function createAccessDeniedHttpException($message = null)
    {
        return new AccessDeniedHttpException($message);
    }

    private function createUnauthorizedHttpException($message = null, $challenge = 'Bearer')
    {
        return new UnauthorizedHttpException($challenge, $message);
    }
}
