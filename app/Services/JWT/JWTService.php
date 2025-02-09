<?php

namespace App\Services\JWT;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;
use DateTimeImmutable;

class JWTService
{
    private Configuration $config;

    public function __construct()
    {
        $key = base64_encode(hash_hmac('sha256', config('app.key'), 'oauth-jwt', true));
        
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::base64Encoded($key)
        );
    }

    /**
     * JWT token oluştur
     *
     * @param array $claims Token içinde yer alacak bilgiler
     * @param int $expiresIn Token geçerlilik süresi (saniye)
     * @return string JWT token
     */
    public function createToken(array $claims, int $expiresIn): string
    {
        $now = new DateTimeImmutable();

        $token = $this->config->builder()
            // JWT ID - token'ın unique identifier'ı
            ->identifiedBy($claims['jti'])
            // Token'ın oluşturulma zamanı
            ->issuedAt($now)
            // Token'ın kullanılmaya başlanabileceği zaman
            ->canOnlyBeUsedAfter($now)
            // Token'ın geçerlilik süresi
            ->expiresAt($now->modify("+{$expiresIn} seconds"))
            // Token'ı oluşturan (issuer)
            ->issuedBy(config('app.url'))
            // OAuth 2.0 claims
            ->permittedFor($claims['aud'])
            ->relatedTo((string) $claims['sub'])
            ->withClaim('scope', $claims['scope'])
            // Token'ı imzala ve oluştur
            ->getToken($this->config->signer(), $this->config->signingKey());

        return $token->toString();
    }

    /**
     * JWT token'ı doğrula
     *
     * @param string $token
     * @return Token|null Geçerli token veya null
     */
    public function validateToken(string $token): ?Token
    {
        try {
            $token = $this->config->parser()->parse($token);

            // Token'ın imzasını kontrol et
            if ($this->config->validator()->validate($token, new \Lcobucci\JWT\Validation\Constraint\SignedWith(
                $this->config->signer(),
                $this->config->signingKey()
            ))) {
                return $token;
            }
        } catch (\Exception $e) {
            // Token parse edilemezse veya doğrulanamazsa
            report($e);
        }

        return null;
    }

    /**
     * Token'dan JTI (JWT ID) değerini al
     *
     * @param Token $token
     * @return string
     */
    public function getJtiFromToken(Token $token): string
    {
        return $token->claims()->get('jti');
    }
} 