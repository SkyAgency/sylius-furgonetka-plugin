<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Service;

use DateMalformedStringException;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Furgonetka\SyliusFurgonetkaPlugin\Api\FurgonetkaOAuthApi;
use Furgonetka\SyliusFurgonetkaPlugin\Enum\FurgonetkaLogsLevel;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaAuthTokenStructure;

use function Symfony\Component\Clock\now;

class FurgonetkaAuthService
{
    public function __construct(
        private readonly FurgonetkaOAuthApi $oAuthApi,
        private readonly FurgonetkaSettingsService $settingsService,
        private readonly FurgonetkaLogsService $logsService,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     */
    public function createOAuthApplication(
        string $shopUrl,
        string $redirectUrl,
        string $environment,
        ?string $shopEmail = null,
        ?string $shopPassword = null,
        ?string $shopToken = null,
    ): bool {
        $oAuthAuthentication = $this->settingsService->getOAuthAuthentication();
        $currentEnvironment = $this->settingsService->getSettings()->environment;

        // Reuse the OAuth application only for the same environment; a switch starts fresh.
        if ($oAuthAuthentication && $currentEnvironment === $environment) {
            return true;
        }

        $this->settingsService->resetSettings(skipMapConfiguration: true);
        $this->settingsService->save(FurgonetkaSettingsService::ENVIRONMENT, $environment);
        $oAuthAuthentication = $this->oAuthApi->createOAuthApplication(
            $shopUrl,
            $redirectUrl,
            $shopEmail,
            $shopPassword,
            $shopToken,
        );

        if ($oAuthAuthentication) {
            $this->settingsService->setOAuthAuthentication($oAuthAuthentication);
            return true;
        }

        $this->settingsService->removeSetting(FurgonetkaSettingsService::ENVIRONMENT);
        $this->logsService->addLog(
            FurgonetkaLogsLevel::Error,
            'Create OAuth application failed.',
            ['shopUrl' => $shopUrl, 'redirectUrl' => $redirectUrl, 'environment' => $environment],
        );
        return false;
    }

    /**
     * @throws Exception
     */
    public function createOAuthUrl(string $redirectUrl): string
    {
        $oAuthAuthentication = $this->settingsService->getOAuthAuthentication();

        return $this->oAuthApi->getRedirectAuthorizationLink($oAuthAuthentication->clientId, $redirectUrl);
    }

    public function authenticate(string $redirectUrl, string $code): bool
    {
        $authToken = $this->oAuthApi->getAuthToken($redirectUrl, $code);
        if (!$authToken) {
            $this->logsService->addLog(
                FurgonetkaLogsLevel::Error,
                'OAuth authentication failed.',
                ['redirectUrl' => $redirectUrl, 'code' => $code],
            );
            return false;
        }

        $this->saveAuthToken($authToken);

        return true;
    }

    public function refreshToken(): void
    {
        $settings = $this->settingsService->getSettings();
        $accessTokenExpires = $settings->accessTokenExpires;
        if (!$accessTokenExpires) {
            return;
        }

        try {
            $accessTokenExpiresToRefresh = new DateTime();
            $accessTokenExpiresToRefresh->setTimestamp((int)$accessTokenExpires);
            $accessTokenExpiresToRefresh->modify('-14 days');

            if ($accessTokenExpiresToRefresh->getTimestamp() >= now()->getTimestamp()) {
                return;
            }
        } catch (DateMalformedStringException) {
            $this->logsService->addLog(
                FurgonetkaLogsLevel::Warning,
                'Check refresh token expire date failed.',
            );
            return;
        }

        $authToken = $this->oAuthApi->refreshToken($settings->refreshToken);
        if (!$authToken) {
            $this->logsService->addLog(
                FurgonetkaLogsLevel::Warning,
                'Refresh token failed.',
            );
            return;
        }

        $this->saveAuthToken($authToken);
    }

    public function saveAuthToken(FurgonetkaAuthTokenStructure $authToken): void
    {
        $accessTokenExpires = time() + $authToken->expiresIn;

        $this->settingsService->setAuthToken(
            new FurgonetkaAuthTokenStructure(
                $authToken->tokenType,
                $accessTokenExpires,
                $authToken->refreshToken,
                $authToken->accessToken,
            )
        );
    }
}
