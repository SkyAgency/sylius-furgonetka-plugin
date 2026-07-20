<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Controller\Admin;

use Exception;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaAuthService;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaIntegrationService;
use Furgonetka\SyliusFurgonetkaPlugin\Service\SyliusUserService;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FurgonetkaAdminIntegrationController extends AbstractController
{
    public function __construct(
        private readonly FurgonetkaAuthService $authService,
        private readonly FurgonetkaIntegrationService $integrationService,
        private readonly SyliusUserService $userService,
        private readonly TranslatorInterface $translator,
        private readonly ChannelRepositoryInterface $channelRepository,
    ) {
    }

    /**
     * @throws Exception
     */
    public function connectAction(Request $request): Response
    {
        $shopUrl = $request->getSchemeAndHttpHost();
        $redirectUrl = $this->generateUrl(
            'furgonetka_sylius_furgonetka_plugin_admin_integration_oauth',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $payload = json_decode($request->getContent() ?: '[]', true);
        $testMode = is_array($payload) && ($payload['testMode'] ?? false);
        $environment = $testMode ?
            FurgonetkaIntegrationService::INTEGRATION_ENVIRONMENT_SANDBOX :
            FurgonetkaIntegrationService::INTEGRATION_ENVIRONMENT_PRODUCTION;

        $apiUser = $this->userService->getOrCreateApiUser();

        $oAuthApplicationCreated = $this->authService->createOAuthApplication(
            shopUrl: $shopUrl,
            redirectUrl: $redirectUrl,
            environment: $environment,
            shopEmail: $apiUser->getEmail(),
            shopPassword: $apiUser->getPlainPassword(),
        );
        if (!$oAuthApplicationCreated) {
            return new JsonResponse([
                'error' => $this->translator->trans('sylius.furgonetka.authorization_error', [], 'furgonetka'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $oAuthUrl = $this->authService->createOAuthUrl($redirectUrl);

        return new JsonResponse(['redirectUrl' => $oAuthUrl], Response::HTTP_OK);
    }

    public function oAuthAction(Request $request): Response
    {
        if ($request->query->get('error') || !$request->query->get('code')) {
            return $this->redirectToRoute('furgonetka_sylius_furgonetka_plugin_admin_page_begin');
        }

        $shopUrl = $request->getSchemeAndHttpHost();
        $redirectUrl = $this->generateUrl(
            'furgonetka_sylius_furgonetka_plugin_admin_integration_oauth',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $code = (string) $request->query->get('code');

        $authenticated = $this->authService->authenticate($redirectUrl, $code);

        if (!$authenticated) {
            return $this->redirectToRoute('furgonetka_sylius_furgonetka_plugin_admin_page_begin');
        }

        $apiUser = $this->userService->getOrCreateApiUser();

        $host = $request->getHost();
        $shopName = $this->channelRepository->findOneByHostname($host)?->getName() ?? $host;

        $createdIntegration = $this->integrationService->createIntegration(
            shopUrl: $shopUrl,
            shopName: $shopName,
            shopUserEmail: $apiUser->getEmail(),
            shopUserPassword: $apiUser->getPlainPassword(),
        );

        if (!$createdIntegration) {
            return $this->redirectToRoute('furgonetka_sylius_furgonetka_plugin_admin_page_begin');
        }

        return $this->redirectToRoute(
            'furgonetka_sylius_furgonetka_plugin_admin_page',
            ['page' => FurgonetkaAdminPageController::PAGE_PLUGIN_PANEL]
        );
    }
}
