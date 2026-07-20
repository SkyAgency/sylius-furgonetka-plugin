<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Serializer;

use Furgonetka\SyliusFurgonetkaPlugin\Repository\FurgonetkaPickupPointRepository;
use Imagine\Exception\Exception as ImagineException;
use Liip\ImagineBundle\Service\FilterService;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

final class OrderNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const NORMALIZER_ALREADY_CALLED = 'furgonetka_order_normalizer_already_called';

    public function __construct(
        private readonly FilterService $liipImagineFilterService,
        private readonly FurgonetkaPickupPointRepository $pickupPointRepository,
    ) {
    }

    /**
     * @param OrderInterface $object
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     * @throws ExceptionInterface
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        Assert::isInstanceOf($object, OrderInterface::class);

        $context[self::NORMALIZER_ALREADY_CALLED] = true;

        return $this->normalizeOrder(
            $object,
            $this->normalizer->normalize($object, $format, $context)
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if ($context[self::NORMALIZER_ALREADY_CALLED] ?? false) {
            return false;
        }

        if (!$data instanceof OrderInterface) {
            return false;
        }

        return in_array('admin:furgonetka:order:index', $context['groups'] ?? [], true);
    }

    /**
     * NOTE: "false" indicates that class could be supported, but that support should not be cached
     */
    public function getSupportedTypes(?string $format): array
    {
        return [OrderInterface::class => false];
    }

    private function normalizeOrder(OrderInterface $order, array $data): array
    {
        $data['furgonetkaOrderData'] = [
            'channel' => [
                'id' => $order->getChannel()?->getId(),
                'name' => $order->getChannel()?->getName(),
            ],
            'items' => array_map(
                fn (OrderItemInterface $item) => [
                    'id' => $item->getId(),
                    'product' => [
                        'id' => $item->getProduct()?->getId(),
                        'images' => array_map(
                            fn (ImageInterface $image) => [
                                'type' => $image->getType(),
                                'path' => $image->getPath(),
                                'url' => $this->getProductImageUrl($image->getPath()),
                            ],
                            $item->getProduct()?->getImages()->toArray() ?: []
                        ),
                    ],
                    'variant' => [
                        'id' => $item->getVariant()?->getId(),
                        'name' => $item->getVariant()?->getName(),
                        'weight' => $item->getVariant()?->getWeight(),
                        'images' => array_map(
                            fn (ImageInterface $image) => [
                                'type' => $image->getType(),
                                'path' => $image->getPath(),
                                'url' => $this->getProductImageUrl($image->getPath()),
                            ],
                            $item->getVariant()?->getImages()->toArray() ?: []
                        ),
                    ],
                ],
                $order->getItems()->toArray(),
            ),
            'payments' => array_map(
                static fn (PaymentInterface $payment) => [
                    'id' => $payment->getId(),
                    'method' => [
                        'id' => $payment->getMethod()?->getId(),
                        'name' => $payment->getMethod()?->getName(),
                    ],
                ],
                $order->getPayments()->toArray()
            ),
            'shipments' => array_map(
                static fn (ShipmentInterface $shipment) => [
                    'id' => $shipment->getId(),
                    'method' => [
                        'id' => $shipment->getMethod()?->getId(),
                        'name' => $shipment->getMethod()?->getName(),
                    ],
                ],
                $order->getShipments()->toArray()
            ),
            'customer' => [
                'id' => $order->getCustomer()?->getId(),
                'email' => $order->getCustomer()?->getEmail(),
            ],
            'pickupPoint' => $this->normalizePickupPoint($order),
        ];

        return $data;
    }

    /**
     * Pickup point chosen on the checkout select-shipping step (null if none).
     *
     * @return array<string, string|null>|null
     */
    private function normalizePickupPoint(OrderInterface $order): ?array
    {
        $pickupPoint = $this->pickupPointRepository->findOneByOrder($order);

        if ($pickupPoint === null) {
            return null;
        }

        return [
            'shippingMethodCode' => $pickupPoint->getShippingMethodCode(),
            'code' => $pickupPoint->getPointCode(),
            'name' => $pickupPoint->getPointName(),
            'type' => $pickupPoint->getPointType(),
        ];
    }

    /**
     * Get product image URL (absolute)
     */
    private function getProductImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        try {
            return $this->liipImagineFilterService->getUrlOfFilteredImage($path, 'sylius_admin_product_original');
        } catch (ImagineException $e) {
            return null;
        }
    }
}
