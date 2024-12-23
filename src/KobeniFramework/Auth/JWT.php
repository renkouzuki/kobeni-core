<?php

namespace KobeniFramework\Auth;

class JWT
{
    public function __construct(
        protected ?string $secretKey = null
    ) {
        $this->secretKey = $secretKey ?? throw new Exceptions\TokenException('No JWT secret key provided');
    }

    public function encode(array $payload, array $headers = []): string
    {
        $headers = array_merge([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ], $headers);

        if (!isset($payload['iat'])) {
            $payload['iat'] = time();
        }

        $encodedHeader = $this->base64UrlEncode(json_encode($headers));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload));
        $signature = $this->sign("$encodedHeader.$encodedPayload", $this->secretKey);
        $encodedSignature = $this->base64UrlEncode($signature);

        return "$encodedHeader.$encodedPayload.$encodedSignature";
    }

    public function decode(string $token, bool $verify = true): array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new Exceptions\TokenException('Invalid token format');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;

        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        if ($verify) {
            $signature = $this->base64UrlDecode($encodedSignature);
            $this->verify("$encodedHeader.$encodedPayload", $signature);
            
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw Exceptions\TokenException::expired();
            }
        }

        return $payload;
    }

    protected function sign(string $input, string $key): string
    {
        return hash_hmac('sha256', $input, $key, true);
    }

    protected function verify(string $input, string $signature): bool
    {
        $hash = hash_hmac('sha256', $input, $this->secretKey, true);
        
        if (!hash_equals($hash, $signature)) {
            throw Exceptions\TokenException::invalid();
        }
        
        return true;
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}