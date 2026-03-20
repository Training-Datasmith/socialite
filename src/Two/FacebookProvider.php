<?php

declare (strict_types=1);
namespace Laravel\Socialite\Two;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Guzzle_Http\Request_Options;
use Illuminate\Support\Arr;
use phpseclib3\Crypt\RSA;
use phpseclib3\Math\Big_Integer;
class Facebook_Provider extends Abstract_Provider implements Provider_Interface
{
    /**
     * The base Facebook Graph URL.
     *
     * @var string
     */
    protected $graph_url = 'https://graph.facebook.com';
    /**
     * The Graph API version for the request.
     *
     * @var string
     */
    protected $version = 'v23.0';
    /**
     * The user fields being requested.
     *
     * @var array
     */
    protected $fields = ['name', 'email', 'gender', 'verified', 'link', 'picture.width(1920)'];
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['email'];
    /**
     * Display the dialog in a popup view.
     *
     * @var bool
     */
    protected $popup = false;
    /**
     * Re-request a declined permission.
     *
     * @var bool
     */
    protected $re_request = false;
    /**
     * The access token that was last used to retrieve a user.
     *
     * @var string|null
     */
    protected $last_token;
    /**
     * {@inheritdoc}
     */
    protected function get_auth_url($state)
    {
        return $this->build_auth_url_from_base('https://www.facebook.com/' . $this->version . '/dialog/oauth', $state);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_token_url(): string
    {
        return $this->graph_url . '/' . $this->version . '/oauth/access_token';
    }
    /**
     * {@inheritdoc}
     */
    public function get_access_token_response($code)
    {
        $response = $this->get_http_client()->post($this->get_token_url(), [Request_Options::FORM_PARAMS => $this->get_token_fields($code)]);
        $data = json_decode($response->get_body(), true);
        return Arr::add($data, 'expires_in', Arr::pull($data, 'expires'));
    }
    /**
     * {@inheritdoc}
     */
    protected function get_user_by_token($token)
    {
        $this->last_token = $token;
        return $this->get_user_by_oidc_token($token) ?? $this->get_user_from_access_token($token);
    }
    /**
     * Get user based on the OIDC token.
     *
     * @param  string  $token
     * @return array
     */
    protected function get_user_by_oidc_token($token): ?array
    {
        $kid = json_decode(base64_decode(explode('.', $token)[0]), true)['kid'] ?? null;
        if ($kid === null) {
            return null;
        }
        $data = (array) JWT::decode($token, $this->get_public_key_of_oidc_token($kid));
        throw_if($data['aud'] !== $this->client_id, new Exception('Token has incorrect audience.'));
        throw_if($data['iss'] !== 'https://www.facebook.com', new Exception('Token has incorrect issuer.'));
        $data['id'] = $data['sub'];
        if (isset($data['given_name'])) {
            $data['first_name'] = $data['given_name'];
        }
        if (isset($data['family_name'])) {
            $data['last_name'] = $data['family_name'];
        }
        return $data;
    }
    /**
     * Get the public key to verify the signature of OIDC token.
     *
     * @param  string  $id
     * @return \Firebase\JWT\Key
     */
    protected function get_public_key_of_oidc_token(string $kid)
    {
        $response = $this->get_http_client()->get('https://limited.facebook.com/.well-known/oauth/openid/jwks/');
        $key = Arr::first(json_decode($response->get_body()->get_contents(), true)['keys'], function (array $key) use ($kid): bool {
            return $key['kid'] === $kid;
        });
        $key['n'] = new Big_Integer(JWT::urlsafe_b64decode($key['n']), 256);
        $key['e'] = new Big_Integer(JWT::urlsafe_b64decode($key['e']), 256);
        return new Key((string) RSA::load($key), 'RS256');
    }
    /**
     * Get user based on the access token.
     *
     * @param  string  $token
     * @return array
     */
    protected function get_user_from_access_token($token)
    {
        $params = ['access_token' => $token, 'fields' => implode(',', $this->fields)];
        if (!empty($this->client_secret)) {
            $params['appsecret_proof'] = hash_hmac('sha256', $token, $this->client_secret);
        }
        $response = $this->get_http_client()->get($this->graph_url . '/' . $this->version . '/me', [Request_Options::HEADERS => ['Accept' => 'application/json'], Request_Options::QUERY => $params]);
        return json_decode($response->get_body(), true);
    }
    /**
     * {@inheritdoc}
     */
    protected function map_user_to_object(array $user)
    {
        return (new User())->set_raw($user)->map(['id' => $user['id'], 'nickname' => null, 'name' => $user['name'] ?? null, 'email' => $user['email'] ?? null, 'avatar' => $avatar_url = Arr::get($user, 'picture.data.url'), 'avatar_original' => $avatar_url, 'profileUrl' => $user['link'] ?? null]);
    }
    /**
     * {@inheritdoc}
     */
    protected function get_code_fields($state = null)
    {
        $fields = parent::get_code_fields($state);
        if ($this->popup) {
            $fields['display'] = 'popup';
        }
        if ($this->re_request) {
            $fields['auth_type'] = 'rerequest';
        }
        return $fields;
    }
    /**
     * Set the user fields to request from Facebook.
     *
     * @return $this
     */
    public function fields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }
    /**
     * Set the dialog to be displayed as a popup.
     *
     * @return $this
     */
    public function as_popup(): self
    {
        $this->popup = true;
        return $this;
    }
    /**
     * Re-request permissions which were previously declined.
     *
     * @return $this
     */
    public function re_request(): self
    {
        $this->re_request = true;
        return $this;
    }
    /**
     * Get the last access token used.
     *
     * @return string|null
     */
    public function last_token()
    {
        return $this->last_token;
    }
    /**
     * Specify which graph version should be used.
     *
     * @return $this
     */
    public function using_graph_version(string $version): self
    {
        $this->version = $version;
        return $this;
    }
}