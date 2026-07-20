<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Listener;

use Furgonetka\SyliusFurgonetkaPlugin\Enum\FurgonetkaLogsLevel;
use Furgonetka\SyliusFurgonetkaPlugin\Exception\ErrorScreenException;
use Furgonetka\SyliusFurgonetkaPlugin\Service\FurgonetkaLogsService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ErrorScreenListener
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FurgonetkaLogsService $logsService,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof ErrorScreenException) {
            return;
        }

        $this->logsService->addLog(
            FurgonetkaLogsLevel::Error,
            $exception->getMessage(),
            ['trace' => $exception->getTraceAsString()]
        );

        $response = new RedirectResponse(
            $this->urlGenerator->generate(
                'furgonetka_sylius_furgonetka_plugin_admin_error_page',
                ['errorCode' => $exception->getMessage()],
            ),
        );
        $event->setResponse($response);
    }
}
