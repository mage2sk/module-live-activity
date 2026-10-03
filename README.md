# Magento 2 Live Activity

Live Activity shows small notification cards on the storefront that describe recent customer activity: purchases, cart additions, wishlist additions, a live viewer count, a trending view count and a low stock alert. Each card can show a product thumbnail and an activity icon, and clicking or tapping it opens the product page.

The numbers come from two sources that can be used together. Real purchase, cart and wishlist events are recorded by event observers and read back from a database table. Simulated notifications are generated on every request with random names, locations, timestamps and counts. The live viewer count and the trending view count are always random; the low stock quantity uses the real stock quantity when it is between 1 and 10 and a random value otherwise. The module is used by store owners who want social proof messages on their storefront, and it ships one template with separate CSS rules for Hyva and Luma themes.

Product page: [kishansavaliya.com/magento-2-live-activity.html](https://kishansavaliya.com/magento-2-live-activity.html)

## Features

- Six notification types: recent purchases, cart additions, wishlist additions, live viewers count, trending products and low stock alerts, each of which can be switched on or off.
- Real activity tracking through observers on the `sales_order_place_after`, `checkout_cart_product_add_after` and `wishlist_add_product` events, stored in the `panth_live_activity` table with the store ID and a created timestamp.
- Simulated activity generated per request from a configurable list of fake customer names and fake customer locations, with a random product from the catalogue or from a list of featured products.
- Activity time range for real data: last 1, 6, 12 or 24 hours, last 2 days or last 7 days.
- Real customer names shortened to first name plus last initial; no email address, surname or address is shown.
- Featured product picker in the admin configuration, with AJAX search by ID, SKU or name.
- Category exclusion for simulated notifications.
- Four positions (bottom left, bottom right, top left, top right) and four animation styles (slide, fade, bounce, scale). Bottom positions follow the shared floating stack: the toast adds `--panth-bottom-bar-offset`, the floating button slots of its side and `--panth-float-edge`, and below 1024 px it leaves room for the floating buttons on the other side (`--panth-float-reserve-right` / `-left`).
- Display delay, notification duration, interval between notifications and a maximum number of notifications per page view.
- Product image and activity icon can each be hidden.
- Custom CSS field, plus CSS variables registered with `Panth_Core` through `etc/theme-config.json`.
- Mobile display can be switched off; the template includes responsive rules, safe-area insets, a dark colour scheme block and a `prefers-reduced-motion` block.
- Notifications are loaded with a single `fetch()` call to a JSON endpoint after the page has loaded; no jQuery, Knockout or RequireJS is used on the storefront.

## Compatibility

| Platform | Versions |
|---|---|
| Magento Open Source | 2.4.4 to 2.4.8 (as published on the product page) |
| Adobe Commerce | 2.4.4 to 2.4.8 (as published on the product page) |
| PHP | 8.1, 8.2, 8.3, 8.4 (from `composer.json`) |
| Themes | Hyva and Luma |

Composer constraints on Magento packages: `magento/framework` ^103.0, `magento/module-catalog` ^104.0, `magento/module-sales` ^103.0, `magento/module-checkout` ^100.4, `magento/module-wishlist` ^101.0, `magento/module-store` ^101.1, `magento/module-backend` ^102.0, `magento/module-config` ^101.2, `magento/module-catalog-inventory` ^100.4.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.4 to 2.4.8.
- PHP 8.1, 8.2, 8.3 or 8.4.
- `mage2kishan/module-core` ^1.0 (module `Panth_Core`), which provides the "Panth Extensions" admin menu group and the theme configuration view model this module registers with.
- The Magento modules listed above; `composer.json` declares no suggested packages.

## Installation

```bash
composer require mage2kishan/module-live-activity
bin/magento module:enable Panth_Core Panth_LiveActivity
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

`setup:di:compile` is only needed in production mode. `setup:static-content:deploy` is listed because the module ships an admin JavaScript file under `view/adminhtml/web/js/`.

Check the result with:

```bash
bin/magento module:status Panth_LiveActivity
```

## Configuration

Go to Stores > Configuration > Panth Extensions > Live Activity & Social Proof. The tab label in `etc/adminhtml/system.xml` is "Panth Extensions" with a rocket emoji in front of it, which is omitted here. The section is also reachable from the admin menu under Panth Extensions > Live Activity > Configuration. All fields can be set at default, website and store view scope.

### General Settings

| Setting | Default | What it does |
|---|---|---|
| Enable Live Activity | Yes | Master switch. When set to No the block renders nothing, the AJAX endpoint returns a "disabled" response and observers stop recording activity. |
| Notification Position | Bottom Left | Corner of the viewport where cards appear. Shown when the module is enabled. |
| Display Delay (seconds) | 5 | Wait before the first notification (0 to 60). |
| Notification Duration (seconds) | 5 | How long each card stays on screen (3 to 30). |
| Interval Between Notifications (seconds) | 8 | Pause between one card closing and the next opening (5 to 120). |
| Maximum Notifications Per Page | 10 | Cap per page view; 0 means unlimited. |

### Activity Types to Display

| Setting | Default | What it does |
|---|---|---|
| Show Recent Purchases | Yes | Shows "<name> from <location> purchased <time ago>". Real entries have no location, so " from <location>" is left out. |
| Show Cart Additions | Yes | Shows "<name> from <location> added to cart <time ago>". Real entries have no location, so " from <location>" is left out. |
| Show Wishlist Additions | Yes | Shows "<name> from <location> added to wishlist <time ago>". Real entries have no location, so " from <location>" is left out. |
| Show Live Viewers Count | Yes | Shows "<n> people are viewing this right now". Simulated only. |
| Show Trending Products | Yes | Shows "Trending: <n> views in the last hour". Simulated only. |
| Show Low Stock Alerts | Yes | Shows "Stock Alert: Only <n> items left!". Simulated only, with the real stock quantity substituted when it is between 1 and 10. |

### Data Source Settings

| Setting | Default | What it does |
|---|---|---|
| Use Real Activity Data | Yes | Records purchase, cart and wishlist events and includes them in the feed. |
| Use Simulated Activity | Yes | Adds 3 to 7 generated notifications to every feed response. |
| Activity Time Range | Last 24 Hours | How far back real events are read. Shown when Use Real Activity Data is Yes. |
| Anonymize Customer Names | Yes | No longer changes the output. Logged-in customers are always stored and shown as first name plus last initial (for example "John D."), whatever this setting is. |
| Featured Product IDs | empty | Comma-separated product IDs chosen with the product picker. When set, simulated notifications use only these products; when empty, up to 50 enabled, visible products are used. |
| Fake Customer Names | 15 sample names | JSON list of names used for simulated notifications. Shown when Use Simulated Activity is Yes. |
| Fake Customer Locations | 15 sample cities | JSON list of locations used for simulated notifications only. Shown when Use Simulated Activity is Yes. |

### Appearance Settings

| Setting | Default | What it does |
|---|---|---|
| Animation Style | Slide In | Entrance animation: Slide In, Fade In, Bounce In or Scale Up. |
| Show Product Image | Yes | Shows a 90x90 product thumbnail when the product has an image. |
| Show Activity Icon | Yes | Shows an SVG icon for the activity type. |
| Custom CSS | empty | Appended to the inline style block of the template. Any `<` character is written as the CSS escape `\3C ` so the value cannot close the style tag. |

### Advanced Settings

| Setting | Default | What it does |
|---|---|---|
| Exclude Categories | empty | Products in these categories are left out of the simulated product pool. Real events are not filtered. |
| Enable on Mobile | Yes | When No, the script exits on viewports narrower than 768px and on touch devices with a mobile user agent. |

Config paths:

- `live_activity/general/enabled`, `live_activity/general/position`, `live_activity/general/display_delay`, `live_activity/general/notification_duration`, `live_activity/general/interval`, `live_activity/general/max_notifications`
- `live_activity/activity_types/show_purchases`, `live_activity/activity_types/show_cart_adds`, `live_activity/activity_types/show_wishlist_adds`, `live_activity/activity_types/show_live_viewers`, `live_activity/activity_types/show_trending`, `live_activity/activity_types/show_low_stock`
- `live_activity/data_source/use_real_data`, `live_activity/data_source/use_simulated_data`, `live_activity/data_source/time_range`, `live_activity/data_source/anonymize_names`, `live_activity/data_source/featured_products`, `live_activity/data_source/fake_names`, `live_activity/data_source/fake_locations`
- `live_activity/appearance/animation_style`, `live_activity/appearance/show_product_image`, `live_activity/appearance/show_icon`, `live_activity/appearance/custom_css`
- `live_activity/advanced/exclude_categories`, `live_activity/advanced/mobile_enabled`

Default behaviour after installation: the module is enabled, all six types are on, real and simulated data are both on, and notifications appear bottom left on every storefront page except checkout after 5 seconds.

## Usage

### Where notifications appear

The block `live.activity.notifications` is added to the `after.body.start` container by `view/frontend/layout/default.xml`, so the container is present on every storefront page except checkout: `checkout_index_index.xml` and `multishipping_checkout.xml` remove the block, and a CSS rule hides it on `body.checkout-index-index`, so it never covers the Place Order button. On a product page the block reads the `id` request parameter and passes it to the endpoint, so real events are limited to that product (plus events with no product); on other pages all real events for the current store are used.

### Notification types and data sources

| Type | Real data | Simulated data |
|---|---|---|
| Purchase | One row per visible order item on `sales_order_place_after`. | Random name, location and time 5 to 180 minutes ago. |
| Cart addition | Recorded on `checkout_cart_product_add_after`. | Same as above. |
| Wishlist addition | Recorded on `wishlist_add_product`. | Same as above. |
| Live viewers | Not recorded. | Random count between 3 and 25. |
| Trending | Not recorded. | Random count between 30 and 150 views "in the last hour". |
| Low stock | Not recorded. | Random quantity 1 to 5, replaced by the real stock quantity when that is between 1 and 10. |

For real events the stored customer name is the logged-in customer's first name plus last initial or, for guests, a random first name from a fixed list plus a random initial. No location is stored for real events; the module does not read the customer's address. Names already stored in full by earlier versions are shortened to first name plus last initial when the feed is built, but locations already stored with older real events are returned as stored until those events fall outside the time range.

What is simulated: every entry with `"is_real": false` in the feed is invented, including its name, location, time and product choice. The live viewer count, the trending view count and the `stats` numbers (`current_viewers`, `views_today`, `cart_adds_today`, `purchases_today`) are always random. The low stock quantity is random unless the real stock quantity is between 1 and 10. Only entries with `"is_real": true` come from real purchases, cart additions and wishlist additions.

### Feed and refresh interval

The endpoint `liveactivity/ajax/getactivity` (GET, optional `product_id`) returns up to 10 real events within the configured time range (the cutoff and the age of each event are calculated by the database, so PHP and database time zones cannot drift apart) plus 3 to 7 simulated entries, sorted by timestamp and cut to 20 entries. The storefront script fetches this feed once per page view (after `requestIdleCallback`, or 500 ms after DOM ready), waits for the display delay, then shows one card at a time for the configured duration, pauses for the configured interval and moves to the next entry. When it reaches the end of the list it starts again from the beginning until the maximum per page is reached. There is no polling; new data is only fetched on the next page load. Each card has a close button and a progress bar that runs for the duration; the timer and the progress bar pause while the pointer is over the card or a control inside it has keyboard focus, and resume with the remaining time.

### Caching

The module does not store the feed in any Magento cache. The JSON endpoint returns a plain JSON result without layout, so it is not served from the full page cache. The product collection used for simulated notifications is fetched once per request. Product thumbnails are requested through the standard catalogue image helper at 90x90 pixels.

### Templates and styling

- Storefront template: `view/frontend/templates/notifications.phtml`, referenced as `Panth_LiveActivity::notifications.phtml`. It contains the container, the inline CSS and the JavaScript. Override it in your theme at `Panth_LiveActivity/templates/notifications.phtml`.
- Admin templates: `view/adminhtml/templates/system/config/product_picker.phtml`, `fake_names.phtml` and `fake_locations.phtml`. These use Alpine.js from `view/adminhtml/web/js/alpine.min.js`.
- CSS variables from `etc/theme-config.json`: `live-activity-bg`, `live-activity-shadow`, `live-activity-highlight`, `live-activity-text`, `live-activity-text-secondary`, `live-activity-text-muted`, `live-activity-border`. The template also reads `--live-activity-icon-bg`, `--live-activity-progress`, `--live-activity-close` and dark-scheme variants with built-in fallbacks.
- Rules prefixed with `body:not([class*="hyva"])` apply the Luma layout; the base rules apply on Hyva.
- Message text is assembled in JavaScript in English and the "x minutes ago" strings are built in PHP; neither passes through Magento translation.

## Developer Notes

- Module name: `Panth_LiveActivity`
- Composer package: `mage2kishan/module-live-activity` (version 1.0.8)
- Namespace: `Panth\LiveActivity`
- Load sequence: after `Panth_Core`, `Magento_Catalog`, `Magento_Sales`, `Magento_Checkout` and `Magento_Wishlist`.

Key classes:

- `Panth\LiveActivity\Model\ActivityProvider`: `getRecentActivity(?int $productId): array` builds the feed; `getViewerStats(int $productId): array` returns random counts that are included in the JSON response as `stats` but not used by the storefront script.
- `Panth\LiveActivity\Model\ActivityTracker`: `track(string $activityType, array $data): void` saves a real event.
- `Panth\LiveActivity\Observer\TrackOrderPlacement`, `TrackCartAdd`, `TrackWishlistAdd`: observers declared in `etc/events.xml` (global scope).
- `Panth\LiveActivity\Controller\Ajax\GetActivity`: frontend JSON endpoint, route `liveactivity`.
- `Panth\LiveActivity\Controller\Adminhtml\Product\Search` and `GetByIds`: admin endpoints `liveactivity/product/search` and `liveactivity/product/getbyids` for the product picker, protected by the ACL resource below.
- `Panth\LiveActivity\Block\Activity`: `isEnabled()`, `getConfigJson()`, `getAjaxUrl()`, `getCurrentProductId()`, `getCustomCss()`.
- `Panth\LiveActivity\Helper\Config`: config path constants, `getFrontendConfig()`, `getFeaturedProductIds()`, `getExcludedCategoryIds()`, `getEnabledFakeNames()`, `getFakeLocations()`.
- `Panth\LiveActivity\Model\Activity` with type constants `purchase`, `cart_add`, `wishlist_add`, `live_viewers`, `trending`, `low_stock` (and an unused `view`).
- Source models: `Model\Config\Source\Position`, `Animation`, `TimeRange`, `Category`.

Dependency injection: `etc/di.xml` is empty. `etc/frontend/di.xml` adds `Panth_LiveActivity` to the `registeredModules` argument of `Panth\Core\ViewModel\ThemeConfig`. No plugins or preferences are declared.

ACL resource: `Panth_LiveActivity::config` ("Panth Live Activity Configuration") under `Magento_Config::config`.

Database tables from `etc/db_schema.xml`:

- `panth_live_activity`: `activity_id`, `activity_type`, `product_id`, `product_name`, `customer_name`, `customer_location`, `created_at`, `store_id`, `is_real`. Written by the tracker and read by the provider.
- `panth_live_activity_stats`: `stat_id`, `product_id`, `current_viewers`, `views_today`, `cart_adds_today`, `wishlist_adds_today`, `purchases_today`, `last_view`, `updated_at`. Declared but not read or written by any class in this version.

## Uninstallation

```bash
bin/magento module:disable Panth_LiveActivity
composer remove mage2kishan/module-live-activity
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

The tables `panth_live_activity` and `panth_live_activity_stats` and the `live_activity/*` rows in `core_config_data` are not removed by these commands; drop or delete them manually if you no longer need them. `mage2kishan/module-core` stays installed if other modules depend on it.

## Support

- Product page: [kishansavaliya.com/magento-2-live-activity.html](https://kishansavaliya.com/magento-2-live-activity.html)
- Contact form: [kishansavaliya.com/contact](https://kishansavaliya.com/contact)
- Email: kishansavaliyakb@gmail.com
- GitHub issues: [github.com/mage2sk/module-live-activity/issues](https://github.com/mage2sk/module-live-activity/issues)

## Documentation

[USER_GUIDE.md](USER_GUIDE.md) walks an administrator through installation, verifying the extension is active, each configuration group, the featured product picker, fake names and locations, how notifications work and troubleshooting.

## License

Commercial software license. See [LICENSE.txt](LICENSE.txt) in this repository.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Links

- Website: [kishansavaliya.com](https://kishansavaliya.com)
- All extensions: [kishansavaliya.com/magento-extensions.html](https://kishansavaliya.com/magento-extensions.html)
- GitHub: [github.com/mage2sk/module-live-activity](https://github.com/mage2sk/module-live-activity)
- Packagist: [packagist.org/packages/mage2kishan/module-live-activity](https://packagist.org/packages/mage2kishan/module-live-activity)
