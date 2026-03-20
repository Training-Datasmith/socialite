# Architecture: socialite

## Purpose

Laravel Socialite provides OAuth 1.0 and OAuth 2.0 authentication against popular social providers (GitHub, Google, Facebook, Twitter/X, LinkedIn, Bitbucket, GitLab, Slack, Twitch) with a unified, driver-based API.

## Directory Structure

```
src/
  Socialite.php                   - Static entry point and configuration accessor
  Socialite_Manager.php           - Driver manager: resolves and caches provider instances
  Socialite_Service_Provider.php  - Laravel service provider; binds the manager into the container
  Facades/Socialite.php           - Laravel Facade for static access
  Abstract_User.php               - Base user value object; maps provider-specific fields
  Contracts/
    Factory.php                   - Contract for the driver manager
    Provider.php                  - Contract all OAuth providers must satisfy
    User.php                      - Contract for user objects returned by providers
  One/                            - OAuth 1.0 implementations
    Abstract_Provider.php         - Base class for OAuth 1 providers (Twitter)
    Twitter_Provider.php          - Twitter OAuth 1.0 implementation
    User.php                      - OAuth 1 user value object
  Two/                            - OAuth 2.0 implementations
    Abstract_Provider.php         - Base class for OAuth 2 providers
    Provider_Interface.php        - OAuth 2 provider contract
    Github_Provider.php           - GitHub OAuth 2 implementation
    Google_Provider.php           - Google OAuth 2 implementation
    Facebook_Provider.php         - Facebook OAuth 2 implementation
    Linked_In_Provider.php / Linked_In_Open_Id_Provider.php
    Gitlab_Provider.php / Bitbucket_Provider.php
    Slack_Provider.php / Slack_Open_Id_Provider.php
    Twitch_Provider.php / Twitter_Provider.php / X_Provider.php
    Token.php                     - OAuth 2 access/refresh token value object
    User.php                      - OAuth 2 user value object
    Invalid_State_Exception.php   - Thrown when the OAuth state parameter doesn't match
  Testing/
    Fake_Provider.php             - Test double provider for unit testing
    Socialite_Fake.php            - Socialite fake that returns a pre-configured user
  Exceptions/
    Driver_Missing_Configuration_Exception.php
```

## Key Design Decisions

- **Driver pattern**: `Socialite_Manager` extends Laravel's `Manager` class; each provider is a driver resolved by name (`Socialite::driver('github')`), making it trivial to add community providers.
- **CSRF state protection**: OAuth 2 flow stores a random state parameter in the session and verifies it on callback, preventing CSRF attacks. `Invalid_State_Exception` is thrown on mismatch.
- **Stateless mode**: Providers support `->stateless()` for APIs where session-based state is unavailable.
- **Scopes and options**: `->scopes(['repo'])` and `->with(['redirect_uri' => ...])` allow per-request OAuth parameter customisation.

## Extension Points

- Register additional community providers via `Socialite::extend('provider-name', fn() => new MyProvider(...))`.
- Implement `Provider_Interface` to build a completely custom OAuth provider.
- Use `Socialite_Fake` in tests to bypass actual OAuth flows.

## Dependency Flow

```
Socialite::driver('github')
  └─> Socialite_Manager::createGithubDriver()
        └─> Two\Github_Provider
              └─> redirect() — sends user to GitHub
              └─> user()     — exchanges code for token, fetches user profile
                    └─> Two\User (value object)
```
