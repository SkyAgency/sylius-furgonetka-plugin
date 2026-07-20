<?php

declare(strict_types=1);

namespace Furgonetka\SyliusFurgonetkaPlugin\Service;

use Doctrine\ORM\NonUniqueResultException;
use Furgonetka\SyliusFurgonetkaPlugin\Entity\FurgonetkaSettingsEntity;
use Furgonetka\SyliusFurgonetkaPlugin\Repository\FurgonetkaSettingsRepository;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaAuthTokenStructure;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaOAuthAuthenticationStructure;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaSettingsStructure;
use Furgonetka\SyliusFurgonetkaPlugin\Structure\FurgonetkaSettingStructure;

class FurgonetkaSettingsService
{
    public const OAUTH_CLIENT_ID = 'clientId';
    public const OAUTH_CLIENT_SECRET = 'clientSecret';
    public const ENVIRONMENT = 'environment';
    public const ACCESS_TOKEN = 'accessToken';
    public const REFRESH_TOKEN = 'refreshToken';
    public const ACCESS_TOKEN_EXPIRES = 'accessTokenExpires';
    public const INTEGRATION_UUID = 'integrationUuid';
    public const ACCOUNT_TYPE = 'accountType';
    public const MAP_API_KEY = 'mapApiKey';
    public const MAP_CONFIGURATION = 'mapConfiguration';

    /** Never exposed through the read endpoint; protects OAuth credentials and tokens. */
    private const HIDDEN_SETTINGS = [
        self::OAUTH_CLIENT_SECRET,
        self::ACCESS_TOKEN,
        self::REFRESH_TOKEN,
    ];

    public function __construct(
        private readonly FurgonetkaSettingsRepository $furgonetkaSettingRepository,
    ) {
    }

    /**
     * Insert-or-update a setting, keyed on the unique name.
     *
     * @throws NonUniqueResultException
     */
    public function save(string $name, string $value): void
    {
        $this->furgonetkaSettingRepository->save($name, $value);
    }

    /**
     * @return FurgonetkaSettingStructure[]
     */
    public function getPublicSettings(): array
    {
        $settings = array_filter(
            $this->furgonetkaSettingRepository->findAll(),
            static fn (FurgonetkaSettingsEntity $setting): bool
                => !in_array($setting->getName(), self::HIDDEN_SETTINGS, true),
        );

        return array_values(array_map(
            static fn (FurgonetkaSettingsEntity $setting): FurgonetkaSettingStructure
                => new FurgonetkaSettingStructure((string) $setting->getName(), (string) $setting->getValue()),
            $settings,
        ));
    }

    public function getPublicSettingByName(string $name): ?FurgonetkaSettingStructure
    {
        if (in_array($name, self::HIDDEN_SETTINGS, true)) {
            return null;
        }

        return $this->getSettingByName($name);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function removeSetting(string $name): void
    {
        $this->furgonetkaSettingRepository->removeSetting($name);
    }

    public function getSettingByName(string $name): ?FurgonetkaSettingStructure
    {
        try {
            $setting = $this->furgonetkaSettingRepository->getSettingByName($name);
        } catch (NonUniqueResultException $e) {
            return null;
        }

        return $setting ? new FurgonetkaSettingStructure($setting->getName(), $setting->getValue()) : null;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getOAuthAuthentication(): ?FurgonetkaOAuthAuthenticationStructure
    {
        $clientId = $this->furgonetkaSettingRepository->getSettingByName(self::OAUTH_CLIENT_ID);
        $clientSecret = $this->furgonetkaSettingRepository->getSettingByName(
            self::OAUTH_CLIENT_SECRET
        );

        if (null === $clientId || null === $clientSecret) {
            return null;
        }

        return new FurgonetkaOAuthAuthenticationStructure($clientId->getValue(), $clientSecret->getValue());
    }

    /**
     * @throws NonUniqueResultException
     */
    public function setOAuthAuthentication(FurgonetkaOAuthAuthenticationStructure $oAuthAuthentication): void
    {
        $this->furgonetkaSettingRepository->save(
            self::OAUTH_CLIENT_ID,
            $oAuthAuthentication->clientId,
        );
        $this->furgonetkaSettingRepository->save(
            self::OAUTH_CLIENT_SECRET,
            $oAuthAuthentication->clientSecret,
        );
    }

    /**
     * @throws NonUniqueResultException
     */
    public function setAuthToken(FurgonetkaAuthTokenStructure $authToken): void
    {
        $this->furgonetkaSettingRepository->save(
            self::ACCESS_TOKEN,
            $authToken->accessToken,
        );
        $this->furgonetkaSettingRepository->save(
            self::REFRESH_TOKEN,
            $authToken->refreshToken,
        );
        $this->furgonetkaSettingRepository->save(
            self::ACCESS_TOKEN_EXPIRES,
            (string)$authToken->expiresIn,
        );
    }

    public function getSettings(): FurgonetkaSettingsStructure
    {
        $settings = $this->furgonetkaSettingRepository->findAll();
        $settings = array_map(
            fn(FurgonetkaSettingsEntity $setting) => ['name' => $setting->getName(), 'value' => $setting->getValue()],
            $settings
        );
        $settings = array_column($settings, 'value', 'name');

        return FurgonetkaSettingsStructure::fromArray($settings);
    }

    public function resetSettings(bool $skipMapConfiguration = false): void
    {
        $exceptNames = $skipMapConfiguration ? [self::MAP_CONFIGURATION, self::MAP_API_KEY] : [];

        $this->furgonetkaSettingRepository->removeAllSettings($exceptNames);
    }
}
