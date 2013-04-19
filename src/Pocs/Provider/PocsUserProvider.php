<?php
namespace Pocs\Provider;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class PocsUserProvider implements UserProviderInterface
{
    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function loadUserByUsername($username) {
        $stmt = $this->conn->executeQuery(
            'SELECT * FROM admins WHERE username = ?',
            array(strtolower($username))
        );

        if (!$user = $stmt->fetch()) {
            throw new UsernameNotFoundException(
              sprintf('Username "%s" does not exist.', $username)
            );
        }

        return new User($user['username'], $user['password'],
            explode(',', $user['roles']), true, true, true, true
        );
    }

    public function refreshUser(UserInterface $user) {
        if ($user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.',
                get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class) {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}

