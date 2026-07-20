<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Api;

use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaIntegrationService;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaIntegrationStructure;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpFoundation\Request;

class FurgonetkaApi extends AbstractFurgonetkaApi
{
    /**
     * Paths to furgonetka api
     */
    private const PATH_ACCOUNT = '/account';
    private const PATH_INTEGRATIONS_CONFIGURATIONS = '/ecommerce/integrations/configurations';
    private const PATH_INIT_APP_LINK = '/ecommerce/app/link/init';
    private const PATH_INTEGRATION_CONNECTION = '/e-commerce/integrations/{integrationUuid}/connection';

    public function createIntegration(
        string $shopUrl,
        ?string $shopUserEmail = null,
        ?string $shopUserPassword = null,
        ?string $shopToken = null,
        ?string $shopName = null,
    ): ?FurgonetkaIntegrationStructure {
        $body = [
            'type' => FurgonetkaIntegrationService::INTEGRATION_TYPE,
            'data' => [
                'shopUrl' => $shopUrl,
                'name' => $shopName,
                'version' => $this->pluginVersion,
                'email' => $shopUserEmail,
                'password' => $shopUserPassword,
                'accessToken' => $shopToken,
            ],
        ];

        $response = $this->sendApiRequest(
            method: Request::METHOD_POST,
            path: self::PATH_INTEGRATIONS_CONFIGURATIONS,
            body: $body
        );
        if ($response) {
            return new FurgonetkaIntegrationStructure(
                $response['integrationUuid'],
                $response['mapApiKey'] ?? null,
            );
        }

        return null;
    }

    public function createAppUrl(string $integrationUuid, string $page, array $additionalParams = []): ?string
    {
        $params = array_merge(
            $additionalParams,
            [
                'integrationUuid' => $integrationUuid,
                'page' => $page,
            ],
        );

        $response = $this->sendApiRequest(
            method: Request::METHOD_POST,
            path: self::PATH_INIT_APP_LINK,
            params: $params
        );
        if ($response) {
            return $response['url'] ?? null;
        }

        return null;
    }

    public function disconnectIntegration(string $integrationUuid): void
    {
        $path = $this->replacePlaceholdersInPathParams(
            self::PATH_INTEGRATION_CONNECTION,
            ['integrationUuid' => $integrationUuid],
        );

        $this->sendApiRequest(method: Request::METHOD_DELETE, path: $path);
    }

    public function getAccount(): ?array
    {
        return $this->sendApiRequest(
            method: Request::METHOD_GET,
            path: self::PATH_ACCOUNT,
            headers: [
                self::HEADER_ACCEPT => self::CONTENT_TYPE_FURGONETKA_V2,
            ],
        );
    }

    protected function getClientOptions(): HttpOptions
    {
        $options = parent::getClientOptions();
        $settings = $this->settingsService->getSettings();
        if ($settings->accessToken) {
            $options->setAuthBearer($settings->accessToken);
        }

        return $options;
    }
}
