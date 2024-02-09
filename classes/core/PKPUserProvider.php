<?php

namespace PKP\core;

use PKP\user\User;
use APP\facades\Repo;
use PKP\security\Validation;
use PKP\validation\ValidatorFactory;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class PKPUserProvider implements UserProvider
{
    /**
     * The active database connection.
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The hasher implementation.
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * The table containing the users.
     *
     * @var string
     */
    protected $table;

    /**
     * Create a new database user provider.
     *
     * @param  \Illuminate\Database\ConnectionInterface  $connection
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @param  string  $table
     * @return void
     */
    public function __construct(ConnectionInterface $connection, HasherContract $hasher, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->hasher = $hasher;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $id
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($id)
    {
        return Repo::user()->get($id);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed    $identifier
     * @param  string   $token
     * 
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($id, $token)
    {
        $authIdentifierName = $this->createUserInstance()->getAuthIdentifierName();

        $userId = $this
            ->connection
            ->table($this->table)
            ->where($authIdentifierName, $id)
            ->first()
            ?->{$authIdentifierName};
        
        if (!$userId) {
            return null;
        }

        $user = Repo::user()->get($userId);

        return $user?->getRememberToken() && hash_equals($user?->getRememberToken(), $token)
            ? $user 
            : null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|\PKP\user\User  $user
     * @param  string  $token
     * 
     * @return void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
        if ($user->getRememberToken() === $token) {
            return;
        }

        $user->setRememberToken($token);
        Repo::user()->edit($user);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $credentials = array_filter(
            $credentials,
            fn ($key) => ! str_contains($key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($credentials)) {
            return;
        }

        if (ValidatorFactory::make(['email' => $credentials['username']], ['email' => 'email'])->passes()) {
            $user = Repo::user()->getByEmail($credentials['username'], true);
            Validation::setAuthKey(Validation::AUTH_KEY_EMAIL);
        } else {
            $user = Repo::user()->getByUsername($credentials['username'], true);
        }

        return $user;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        if (is_null($plain = $credentials['password'])) {
            return false;
        }

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * Create a new instance of the \PKP\user\User
     * 
     * @return \PKP\user\User
     */
    public function createUserInstance(): User
    {
        return new User;
    }

    /**
     * Gets the hasher implementation.
     *
     * @return \Illuminate\Contracts\Hashing\Hasher
     */
    public function getHasher(): HasherContract
    {
        return $this->hasher;
    }

    /**
     * Sets the hasher implementation.
     *
     * @return $this
     */
    public function setHasher(HasherContract $hasher)
    {
        $this->hasher = $hasher;

        return $this;
    }
}