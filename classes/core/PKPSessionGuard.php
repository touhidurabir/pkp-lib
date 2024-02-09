<?php

namespace PKP\core;

use Carbon\Carbon;
use PKP\user\User;
use APP\facades\Repo;
use PKP\core\Registry;
use APP\core\Application;
use PKP\security\Validation;
use InvalidArgumentException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Contracts\Session\Session;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class PKPSessionGuard extends SessionGuard
{
    /**
     * The currently authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|\PKP\user\User|null
     */
    protected $user;

    /**
     * The user provider implementation.
     *
     * @var \Illuminate\Contracts\Auth\UserProvider|\PKP\core\PKPUserProvider
     */
    protected $provider;

    /**
     * update the current user without firing any events or changes
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|User  $user
     * @return $this
     */
    public function updateUser(\Illuminate\Contracts\Auth\Authenticatable|\PKP\user\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|\PKP\user\User  $user
     * @return $this
     */
    public function setUser(AuthenticatableContract|\PKP\user\User $user)
    {
        Registry::set('user', $user);

        return parent::setUser($user);
    }

    /**
     * Sign In as different user
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|\PKP\user\User  $user
     * @return void
     */
    public function signInAs(AuthenticatableContract|\PKP\user\User $user)
    {
        $auth = app()->get('auth'); /** @var \PKP\core\PKPAuthManager $auth */

        $this->session->put([
            'signedInAs' => $this->session->get('user_id'),
            'password_hash_'.$auth->getDefaultDriver() => $user->getPassword(),
        ]);

        $this
            ->setUserDataToSession($user)
            ->updateUser($user)
            ->updateSession($user->getId());
    }

    /**
     * Sign Out from previously sign in as different user
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|\PKP\user\User  $user
     * @return void
     */
    public function signOutAs(AuthenticatableContract|\PKP\user\User $user)
    {
        $auth = app()->get('auth'); /** @var \PKP\core\PKPAuthManager $auth */

        $this->session->forget('signedInAs');

        $this->session->put('password_hash_'.$auth->getDefaultDriver(), $user->getPassword());

        $this
            ->setUserDataToSession($user)
            ->updateUser($user)
            ->updateSession($user->getId());
    }

    /**
     * Set the user data to session
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|\PKP\user\User $user
     * @return $this
     */
    public function setUserDataToSession(AuthenticatableContract|\PKP\user\User $user)
    {
        $this->session->put('user_id',  $user->getId());
        $this->session->put('username', $user->getUsername());
        $this->session->put('email',    $user->getEmail());

        return $this;
    }

    /**
     * Update the session with the given ID.
     *
     * @param  string  $id
     * @return void
     */
    public function updateSession($id)
    {
        parent::updateSession($id);

        $this->updateLaravelSession($this->getSession());

        $this->updateSessionCookieToResponse($this->getSession());
    }

    /**
     * Update the session instance to laravel request singleton object
     *
     * @param \Illuminate\Contracts\Session\Session $session
     * @return void
     */
    public function updateLaravelSession($session)
    {
        $request = app()->get('request'); /** @var \Illuminate\Http\Request $request */
        $request->setLaravelSession($session);
    }

    /**
     * Get the session store used by the guard.
     *
     * @param \Illuminate\Contracts\Session\Session $session
     * @return $this
     */
    public function setSession(\Illuminate\Contracts\Session\Session $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Update session cookie based in the response
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|null
     * @return void
     */
    public function updateSessionCookieToResponse(Session $session = null): void
    {   
        $session ??= $this->getSession();
        $headerCookies = [];

        $config = app()->get("config")["session"];

        /** @var \Illuminate\Http\Response $response */
        $response = app()->get(\Illuminate\Http\Response::class);

        $response->headers->removeCookie($session->getName());

        $cookie = new Cookie(
            $session->getName(), 
            $session->getId(), 
            $this->getCookieExpirationDate($config),
            $config['path'], 
            $config['domain'], 
            $config['secure'] ?? false,
            $config['http_only'] ?? true, 
            false, 
            $config['same_site'] ?? null
        );

        $headerCookies[] = $session->getName().'='.$session->getId();
        $response->headers->setCookie($cookie);
        
        // Set remember me cookie
        $response->headers->removeCookie($this->getRecallerName());
        $cookieJar = $this->getCookieJar(); /** @var \Illuminate\Cookie\CookieJar $cookieJar */
        if ( ($rememberCookie = $cookieJar->queued($this->getRecallerName())) ) {
            $response->headers->setCookie($rememberCookie);
            $headerCookies[] = $rememberCookie->getName() . '=' . $rememberCookie->getValue();
        }
        
        // update response header cookie values in formar [name=value]
        $response->headers->set('cookie', $headerCookies);
    }

    /**
     * Send the cookie headers
     *
     * @return void
     */
    public function sendCookies()
    {
        $response = app()->get(\Illuminate\Http\Response::class); /** @var \Illuminate\Http\Response $response */

        foreach ($response->headers->getCookies() as $cookie) {
            header('Set-Cookie: '.$cookie, false, $response->getStatusCode() ?? 0);
        }
    }

    /**
     * Invalidate/Remove/Delete other user session by user auth identifier name (e.g. user_id)
     *
     * @param int       $userId                 The user id for which session data need to be removed
     * @param string    $excludableSessionId    The session id which should be kept
     * 
     * @return void
     */
    public function invalidateOtherSessions($userId, $excludableSessionId = null)
    {
        DB::table('sessions')
            ->where($this->provider->createUserInstance()->getAuthIdentifierName(), $userId)
            ->when($excludableSessionId, fn ($query) => $query->where('id', '<>', $excludableSessionId))
            ->delete();
    }

    /**
     * Rehash the current user's password.
     *
     * @param  string  $password
     * @param  string  $attribute
     * 
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     *
     * @throws \InvalidArgumentException
     */
    protected function rehashUserPassword($password, $attribute)
    {
        $rehash = null;

        if (! Validation::verifyPassword($this->user->getUsername(), $password, $this->user->getPassword(), $rehash)) {
            throw new InvalidArgumentException('The given password does not match the current password.');
        }

        return tap($this->user, function(&$user) use ($password) {
            $rehash ??= Validation::encryptCredentials($user->getUsername(), $password);
            $user->setPassword($rehash);
            
            Application::get()->getRequest()->getSession()->put([
                'password_hash_' . app()->get('auth')->getDefaultDriver() => $rehash,
            ]);

            Repo::user()->edit($user);
        });
    }

    /**
     * Get the cookie lifetime in seconds.
     *
     * @return \DateTimeInterface|int
     */
    protected function getCookieExpirationDate(array $config)
    {
        return $config['expire_on_close'] ? 0 : Date::instance(
            Carbon::now()->addRealMinutes($config['lifetime'])
        );
    }
}