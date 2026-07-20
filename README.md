# Furgonetka Sylius

Installation from **Packagist** via Composer.

## Compatibility

A single plugin codebase supports **Sylius 1.14** and **Sylius 2.0 – 2.2**. The plugin detects the
installed Sylius major version at compile time (`FurgonetkaSyliusFurgonetkaExtension`) and automatically
loads the matching admin view integration:

| Sylius | Admin UI | View injection | API Platform | PHP |
|--------|----------|----------------|--------------|-----|
| 1.14   | Semantic UI | `sylius_ui` template events | 2.7 | 8.1+ |
| 2.0–2.2| Bootstrap 5 | Twig Hooks (`sylius_twig_hooks`) | 4.x | 8.2+ |

The view config (`sylius_ui` / `sylius_twig_hooks`, `sylius_grid`) and the API Platform resource mapping
are registered automatically by the plugin — no manual import is required for them. Only the routes
(admin and shop) need to be wired in the application (see step 3), because their prefixes depend on the
shop's admin path / locale.

## Installation

These steps install the plugin on an existing Sylius shop.

### 1. Require

```
composer require furgonetka/sylius-furgonetka-plugin
```

### 2. Register the bundle

Add the bundle to `./config/bundles.php` (no Flex recipe ships with the plugin):
```php
Furgonetka\SyliusFurgonetkaPlugin\FurgonetkaSyliusFurgonetkaPlugin::class => ['all' => true],
```

### 3. Routes

Add the admin routes to `./config/routes/sylius_admin.yaml`
```yaml
furgonetka_sylius_furgonetka_plugin_admin:
    resource: "@FurgonetkaSyliusFurgonetkaPlugin/Resources/config/admin_routing.yaml"
    prefix: '/%sylius_admin.path_name%'
```

Add the shop routes to `./config/routes/sylius_shop.yaml` (needed for the checkout pickup-point
selector — without them the select-shipping step throws "route does not exist"):
```yaml
furgonetka_sylius_furgonetka_plugin_shop:
    resource: "@FurgonetkaSyliusFurgonetkaPlugin/Resources/config/shop_routing.yaml"
    prefix: /{_locale}
    requirements:
        _locale: ^[A-Za-z]{2,4}(_([A-Za-z]{4}|[0-9]{3}))?(_([A-Za-z]{2}|[0-9]{3}))?$
```

> The admin view integration (menu, order grid action, order sidebar, modal, assets) and the
> `GET /admin/furgonetka/orders` API Platform export endpoint are configured automatically by the plugin.

### 4. Migrations

Run: `bin/console doctrine:migrations:migrate`

> The plugin registers its own migration namespace automatically (`PrependDoctrineMigrationsTrait`
> in `FurgonetkaSyliusFurgonetkaExtension`), so no `doctrine_migrations.yaml` change is needed.

### 5. Assets

Run: `bin/console assets:install public`

### 6. Cache clear

Run `bin/console cache:clear` in the php container of the Sylius shop

### 7. Sylius API

Check if Sylius API is enabled
   ```yaml
   # config/packages/_sylius.yaml

   sylius_api:
       enabled: true
   ```

The plugin talks to the shop through the Sylius API, which needs a JWT keypair. If your shop
doesn't have one yet, generate it:
```
bin/console lexik:jwt:generate-keypair
```
