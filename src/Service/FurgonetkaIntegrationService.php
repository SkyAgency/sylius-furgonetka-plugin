<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Service;

use Furgonetka\SyliusFurgonetkaPlugin\Api\FurgonetkaApi;
use Furgonetka\SyliusFurgonetkaPlugin\Enum\FurgonetkaAccountType;
use Furgonetka\SyliusFurgonetkaPlugin\Enum\FurgonetkaLogsLevel;
use Furgonetka\SyliusFurgonetkaPlugin\Exception\ErrorScreenException;

class FurgonetkaIntegrationService
{
    public const INTEGRATION_TYPE = 'sylius';

    /**
     * Environments
     */
    public const INTEGRATION_ENVIRONMENT_SANDBOX = 'sandbox';
    public const INTEGRATION_ENVIRONMENT_PRODUCTION = 'production';

    public const PATH_ERROR_SCREEN = '/ecommerce/app/error_screen?';

    public function __construct(
        private readonly FurgonetkaApi $api,
        private readonly FurgonetkaSettingsService $settingsService,
        private readonly FurgonetkaLogsService $logsService,
    ) {
    }

    public function createIntegration(
        string $shopUrl,
        string $shopName,
        ?string $shopUserEmail = null,
        ?string $shopUserPassword = null,
        ?string $shopToken = null,
    ): bool {
        $accountType = $this->getAccountType();

        if (!$accountType) {
            $this->logsService->addLog(
                FurgonetkaLogsLevel::Error,
                'Get account type failed.',
            );
            return false;
        }

        $integration = $this->api->createIntegration(
            $shopUrl,
            $shopUserEmail,
            $shopUserPassword,
            $shopToken,
            $shopName,
        );

        if (!$integration) {
            $this->logsService->addLog(
                FurgonetkaLogsLevel::Error,
                'Create integration failed.',
            );
            return false;
        }

        $this->settingsService->save(FurgonetkaSettingsService::INTEGRATION_UUID, $integration->integrationUuid);
        $this->settingsService->save(FurgonetkaSettingsService::ACCOUNT_TYPE, $accountType->value);

        if (null !== $integration->mapApiKey) {
            $this->settingsService->save(FurgonetkaSettingsService::MAP_API_KEY, $integration->mapApiKey);
        }

        $this->logsService->addLog(
            FurgonetkaLogsLevel::Info,
            'Integration created.',
            ['integrationUuid' => $integration->integrationUuid, 'accountType' => $accountType->value],
        );
        return true;
    }

    public function disconnectIntegration(): void
    {
        $integrationUuid = $this->settingsService->getSettings()->integrationUuid;

        if (!$integrationUuid) {
            return;
        }

        try {
            $this->api->disconnectIntegration($integrationUuid);
        } catch (\Throwable) {
            // Best-effort: never block the local reset if the API is unreachable.
        }
    }

    /**
     * @throws ErrorScreenException
     */
    public function createAppUrl(string $page, array $additionalParams = []): string
    {
        $integrationUuid = $this->settingsService->getSettingByName(FurgonetkaSettingsService::INTEGRATION_UUID);

        if (!$integrationUuid) {
            throw new ErrorScreenException(ErrorScreenException::ERROR_CODE_MISSING_REQUIRED_PARAMETERS);
        }

        $url = $this->api->createAppUrl($integrationUuid->value, $page, $additionalParams);

        if (!$url) {
            throw new ErrorScreenException(ErrorScreenException::ERROR_CODE_UNSUPPORTED_LINK);
        }

        return $url;
    }

    public function createErrorUrl(?string $errorCode, string $shopUrl): string
    {
        $errorCode = in_array($errorCode, ErrorScreenException::SUPPORTED_ERROR_CODES) ? $errorCode : ErrorScreenException::ERROR_CODE_UNKNOWN;

        $errorScreenUrl = self::getBaseShopUrl() . self::PATH_ERROR_SCREEN;
        $errorScreenUrl .= http_build_query(
            [
                'origin' => $shopUrl,
                'type' => self::INTEGRATION_TYPE,
                'error' => $errorCode,
            ]
        );

        return $errorScreenUrl;
    }

    private function getAccountType(): ?FurgonetkaAccountType
    {
        $response = $this->api->getAccount();

        return FurgonetkaAccountType::tryFrom($response['account_type'] ?? '');
    }

    public static function getBaseShopUrl(): string
    {
        return 'https://shop.furgonetka.pl';
    }
}
