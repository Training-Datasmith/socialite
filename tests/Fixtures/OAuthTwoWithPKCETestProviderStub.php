<?php

declare(strict_types=1);

namespace Laravel\Socialite\Tests\Fixtures;

class OAuthTwoWithPKCETestProviderStub extends OAuthTwoTestProviderStub
{
    protected $usesPKCE = true;
}
