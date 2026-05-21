<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Validates a Google id_token or access token and returns normalized profile data.
 */
final class GoogleIdentityTokenVerifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $googleOAuthClientId,
    ) {
    }

    /**
     * @return array{email: string, firstName: string, lastName: string}
     */
    public function verify(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            throw new \InvalidArgumentException('Google token is required.');
        }

        $fromIdToken = $this->tryVerifyIdToken($token);
        if ($fromIdToken !== null) {
            return $fromIdToken;
        }

        return $this->verifyAccessToken($token);
    }

    /**
     * @return array{email: string, firstName: string, lastName: string}|null
     */
    private function tryVerifyIdToken(string $token): ?array
    {
        $response = $this->httpClient->request('GET', 'https://oauth2.googleapis.com/tokeninfo', [
            'query' => ['id_token' => $token],
        ]);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->toArray(false);

        $aud = $payload['aud'] ?? $payload['azp'] ?? null;
        if (!\is_string($aud) || $aud !== $this->googleOAuthClientId) {
            throw new \RuntimeException('Google token audience does not match this application.');
        }

        $email = $payload['email'] ?? null;
        if (!\is_string($email) || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Google account has no valid email address.');
        }

        $givenName = \is_string($payload['given_name'] ?? null) ? trim($payload['given_name']) : '';
        $familyName = \is_string($payload['family_name'] ?? null) ? trim($payload['family_name']) : '';
        $fullName = \is_string($payload['name'] ?? null) ? trim($payload['name']) : '';

        if ($givenName === '' && $fullName !== '') {
            $givenName = explode(' ', $fullName)[0];
        }
        if ($familyName === '' && $fullName !== '') {
            $parts = explode(' ', $fullName, 2);
            $familyName = $parts[1] ?? '';
        }

        return [
            'email' => strtolower($email),
            'firstName' => $givenName !== '' ? $givenName : 'Teach Me',
            'lastName' => $familyName !== '' ? $familyName : 'User',
        ];
    }

    /**
     * @return array{email: string, firstName: string, lastName: string}
     */
    private function verifyAccessToken(string $token): array
    {
        $response = $this->httpClient->request(
            'GET',
            'https://www.googleapis.com/oauth2/v3/userinfo',
            [
                'headers' => ['Authorization' => 'Bearer '.$token],
            ],
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Invalid or expired Google token.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->toArray(false);

        $email = $payload['email'] ?? null;
        if (!\is_string($email) || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Google account has no valid email address.');
        }

        $givenName = \is_string($payload['given_name'] ?? null) ? trim($payload['given_name']) : '';
        $familyName = \is_string($payload['family_name'] ?? null) ? trim($payload['family_name']) : '';
        $fullName = \is_string($payload['name'] ?? null) ? trim($payload['name']) : '';

        if ($givenName === '' && $fullName !== '') {
            $givenName = explode(' ', $fullName)[0];
        }
        if ($familyName === '' && $fullName !== '') {
            $parts = explode(' ', $fullName, 2);
            $familyName = $parts[1] ?? '';
        }

        return [
            'email' => strtolower($email),
            'firstName' => $givenName !== '' ? $givenName : 'Teach Me',
            'lastName' => $familyName !== '' ? $familyName : 'User',
        ];
    }
}
