<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    public const VIEW = 'view';
    public const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::DELETE])
            && $subject instanceof \App\Entity\User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $client = $token->getUser();

        if (!$client instanceof UserInterface) {
            return false;
        }

        $user = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $client === $user->getClient();
            case self::DELETE:
                return $client === $user->getClient();
        }

        return false;
    }
}
