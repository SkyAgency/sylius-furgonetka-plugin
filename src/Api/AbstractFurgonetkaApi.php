<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Api;

use Exception;
use Furgonetka\SyliusFurgonetkaPlugin\Enum\FurgonetkaLogsLevel;
use Furgonetka\SyliusFurgonetkaPlugin\Exception\ErrorScreenException;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaIntegrationService;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaLogsService;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaSettingsService;
use Sylius\Bundle\CoreBundle\SyliusCoreBundle;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractFurgonetkaApi
{
    /*
     * Urls for furgonetka apis
     */
    private const URL = 'https://api.furgonetka.pl';
    private const SANDBOX_URL = 'https://api.sandbox.furgonetka.pl';

    /**
     * Headers
     */
    protected const HEADER_ACCEPT = 'Accept';
    protected const HEADER_CONTENT_TYPE = 'Content-Type';
    protected const HEADER_USER_AGENT = 'User-Agent';

    /**
     * Content types
     */
    protected const CONTENT_TYPE_FURGONETKA_V1 = 'application/vnd.furgonetka.v1+json';
    protected const CONTENT_TYPE_FURGONETKA_V2 = 'application/vnd.furgonetka.v2+json';

    public function __construct(
        protected HttpClientInterface $client,
        protected readonly FurgonetkaSettingsService $settingsService,
        protected readonly FurgonetkaLogsService $logsService,
        protected readonly string $pluginVersion,
    ) {
    }

    protected function getClientOptions(): HttpOptions
    {
        $settings = $this->settingsService->getSettings();

        $options = new HttpOptions();
        $options->setBaseUri($this->getApiUrlByEnvironment($settings->environment));
        $options->setHeaders($this->getClientHeaders());

        return $options;
    }

    protected function getClientHeaders(): array
    {
        return [
            self::HEADER_CONTENT_TYPE => self::CONTENT_TYPE_FURGONETKA_V1,
            self::HEADER_USER_AGENT => sprintf(
                '%s_%s_plugin_%s',
                FurgonetkaIntegrationService::INTEGRATION_TYPE,
                SyliusCoreBundle::VERSION,
                $this->pluginVersion,
            ),
        ];
    }

    protected function sendApiRequest(
        string $method,
        string $path,
        array $params = [],
        array $body = [],
        array $headers = [],
    ): ?array {
        $options = $this->getClientOptions();
        $requestHeaders = array_merge($this->getClientHeaders(), $headers);

        $requestOptions = ['query' => $params];

        if (!empty($body)) {
            $requestOptions['body'] = $body;
        } else {
            // Without a body the JSON Content-Type would make the API try to decode
            // an empty payload and respond with 400 "JSON decode error".
            unset($requestHeaders[self::HEADER_CONTENT_TYPE]);
        }

        $options->setHeaders($requestHeaders);

        // withOptions() merges headers per key, so we always derive the client from the
        // originally injected $this->client. Reusing a previously configured client would
        // keep its Content-Type and bring it back despite the unset() above.
        $client = $this->client->withOptions($options->toArray());

        try {
            $response = $client->request($method, $path, $requestOptions);

            if ($response->getStatusCode() === Response::HTTP_NO_CONTENT) {
                return [];
            }

            return $response->toArray();
        } catch (ExceptionInterface $clientException) {
            $this->logsService->addLog(
                FurgonetkaLogsLevel::Error,
                $clientException->getMessage(),
                [
                    'response' => $clientException->getResponse()->getContent(false),
                    'trace' => $clientException->getTraceAsString(),
                ],
            );

            $errors = json_decode($clientException->getResponse()->getContent(false), true);

            $errorCode = is_array($errors['errors'] ?? null) ? (current($errors['errors'])['code'] ?? null) : null;

            if ($errorCode !== null && in_array($errorCode, ErrorScreenException::SUPPORTED_ERROR_CODES, true)) {
                throw new ErrorScreenException($errorCode);
            }

            return null;
        }
    }

    protected function getApiUrlByEnvironment(?string $environment): string
    {
        return $environment === FurgonetkaIntegrationService::INTEGRATION_ENVIRONMENT_SANDBOX ? self::SANDBOX_URL : self::URL;
    }

    /**
     * @throws Exception
     */
    protected function replacePlaceholdersInPathParams(string $path, array $pathParams): string
    {
        foreach ($pathParams as $key => $value) {
            if (is_array($value)) {
                throw new Exception('Array cannot be param for path');
            }
            $path = str_replace('{' . $key . '}', urlencode($value), $path);
        }

        return $path;
    }
}
