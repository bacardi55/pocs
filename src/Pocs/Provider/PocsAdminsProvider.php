<?php
namespace Pocs\Provider;

use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class PocsAdminsProvider implements UserProviderInterface
{
    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function loadUserByUsername($email) {
        $stmt = $this->conn->executeQuery(
            'SELECT * FROM admins WHERE email = ?',
            array(strtolower($email))
        );

        if (!$user = $stmt->fetch()) {
            throw new UsernameNotFoundException(
              sprintf('Email "%s" does not exist.', $email)
            );
        }

        return new User($user['email'], $user['password'],
            array('ROLE_ADMIN'), true, true, true, true
        );
    }

    public function refreshUser(UserInterface $user) {
        if (!$user instanceof User) {
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

    public function emailExists($email) {
        $stmt = $this->conn->executeQuery(
            'SELECT email FROM admins WHERE email = ?',
            array(strtolower($email))
        );
        $user = $stmt->fetch();

        return $user ? true : false;
    }

    public function createUser($user) {
        $this->conn->executeQuery(
            'INSERT INTO admins (email, password)
            VALUES(?, ?)',
            array($user->getUsername(), $user->getPassword())
        );
    }
}

