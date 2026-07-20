<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Api;

use Exception;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaAuthTokenStructure;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaOAuthAuthenticationStructure;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpFoundation\Request;

class FurgonetkaOAuthApi extends AbstractFurgonetkaApi
{
    /**
     * Paths to furgonetka oAuth api
     */
    private const PATH_CREATE_OAUTH_APPLICATION = '/ecommerce/integrations/create-oauth-application';
    private const PATH_OAUTH_AUTHORIZE = '/oauth/authorize?response_type=code&client_id={clientId}&redirect_uri={redirectUri}';
    private const PATH_OAUTH_TOKEN = '/oauth/token';

    public function createOAuthApplication(
        string $shopUrl,
        string $redirectUrl,
        ?string $email = null,
        ?string $password = null,
        ?string $token = null,
    ): ?FurgonetkaOAuthAuthenticationStructure {
        $body = [
            'type' => 'sylius',
            'url' => $redirectUrl,
            'data' => [
                'shopUrl' => $shopUrl,
                'email' => $email,
                'password' => $password,
                'accessToken' => $token,
            ],
        ];

        $response = $this->sendApiRequest(
            method: Request::METHOD_POST,
            path: self::PATH_CREATE_OAUTH_APPLICATION,
            body: $body
        );

        if ($response) {
            return new FurgonetkaOAuthAuthenticationStructure($response['client_id'], $response['client_secret']);
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getRedirectAuthorizationLink(string $clientId, string $redirectUri): string
    {
        $settings = $this->settingsService->getSettings();

        $apiUrl = $this->getApiUrlByEnvironment($settings->environment);
        $path = $this->replacePlaceholdersInPathParams(self::PATH_OAUTH_AUTHORIZE, [
            'clientId' => $clientId,
            'redirectUri' => $redirectUri,
        ]);

        return $apiUrl . $path;
    }

    public function getAuthToken(
        string $redirectUrl,
        string $code
    ): ?FurgonetkaAuthTokenStructure {
        $body = [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUrl,
        ];

        $response = $this->sendApiRequest(method: Request::METHOD_POST, path: self::PATH_OAUTH_TOKEN, body: $body);
        if ($response) {
            return new FurgonetkaAuthTokenStructure(
                $response['token_type'],
                $response['expires_in'],
                $response['refresh_token'],
                $response['access_token'],
            );
        }

        return null;
    }

    public function refreshToken(string $refreshToken): ?FurgonetkaAuthTokenStructure
    {
        $body = [
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        $response = $this->sendApiRequest(method: Request::METHOD_POST, path: self::PATH_OAUTH_TOKEN, body: $body);
        if ($response) {
            return new FurgonetkaAuthTokenStructure(
                $response['token_type'],
                $response['expires_in'],
                $response['refresh_token'],
                $response['access_token'],
            );
        }

        return null;
    }

    protected function getClientOptions(): HttpOptions
    {
        $options = parent::getClientOptions();
        $settings = $this->settingsService->getSettings();
        if ($settings->clientId && $settings->clientSecret) {
            $options->setAuthBasic($settings->clientId, $settings->clientSecret);
        }

        return $options;
    }
}
