<?php

namespace Ybenhssaien\AuthorizationBundle\Voter;

use Ybenhssaien\AuthorizationBundle\Annotation\Authorization;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Ybenhssaien\AuthorizationBundle\Service\AuthorizationService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthorizationVoter extends Voter
{
    protected AuthorizationService $authorization;

    /**
     * Entities list using authorizations.
     *
     * @var array|string[]
     */
    protected array $supportedEntites = [];

    public function __construct(AuthorizationService $authorization)
    {
        $this->authorization = $authorization;
        $this->supportedEntites = $authorization->getAuthorizationMap()->getEntities();
    }

    protected function supports($attribute, $subject)
    {
        return \in_array($attribute, Authorization::ACTIONS)
            && \is_array($subject)
            && 2 === \count($subject)
            && $this->supportsEntity($subject[0]);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $property = $subject[1];
        $class = \is_object($subject[0]) ? \get_class($subject[0]) : $subject[0];

        return $this->authorization->isPropertyExists($property, $class)
            ? $this->authorization->canPerformActionOnData($property, $class, $attribute)
            : true;
    }

    protected function supportsEntity($entity): bool
    {
        $class = \is_object($entity) ? \get_class($entity) : $entity;

        /* TODO : use instanceof tu supports parent classes and interfaces */
        return \in_array($class, $this->supportedEntites);
    }
}
