# Panth Live Activity & Social Proof for Magento 2

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange)]()
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue)]()
[![Hyva Compatible](https://img.shields.io/badge/Hyva-Compatible-green)]()
[![Luma Compatible](https://img.shields.io/badge/Luma-Compatible-green)]()

**Real-time social proof notifications** for Magento 2 -- display live
customer activity (purchases, cart additions, wishlist adds, trending
products, low-stock alerts, and live viewer counts) to create urgency
and boost conversions. Works with both Hyva and Luma themes
out-of-the-box.

---

## Why this extension

| | Typical FOMO tools | **Panth Live Activity** |
|---|---|---|
| Data source | Simulated only | **Real AND simulated** -- tracks genuine orders, cart adds, wishlists via Magento events |
| Notification types | 1-2 | **6 types** -- purchases, cart adds, wishlists, live viewers, trending, low stock |
| Theme support | Luma only | **Hyva + Luma** with dedicated CSS for each |
| Mobile | Often broken | **Fully responsive** with safe-area inset support, reduced-motion media query, landscape handling |
| Configuration depth | Basic | Deep -- fake names, fake locations, featured product picker, category exclusion, animation styles, custom CSS, per-store-view overrides |
| Product selection | Random only | **Featured product picker** (admin modal with search) OR random from catalog |
| Privacy | Names exposed | Built-in **name anonymisation** ("John D." format) |

---

## Features

### 6 notification types
- **Recent purchases** -- "John D. from New York purchased this 5 minutes ago"
- **Cart additions** -- "Sarah M. added this to cart 2 minutes ago"
- **Wishlist additions** -- "Emma A. added this to wishlist"
- **Live viewers** -- "15 people are viewing this product right now"
- **Trending** -- "Trending: 50 views in last hour"
- **Low stock alerts** -- "Only 3 left in stock!"

### Real + simulated data
- Real data tracked via Magento observers (`sales_order_place_after`,
  `checkout_cart_product_add_after`, `wishlist_add_product`)
- Simulated data for low-traffic or new stores with configurable
  fake names and locations
- Both can run simultaneously for richer notifications

### Fully configurable
- Notification position (4 corners)
- Display delay, duration, and interval between notifications
- 4 animation styles (slide, fade, bounce, scale)
- Product image display toggle
- Activity type icon toggle
- Custom CSS field for minor overrides
- CSS variables driven by `theme-config.json` for Hyva Theme Customizer
- Category exclusion (multiselect with hierarchical tree)
- Featured product picker with AJAX search modal
- Configurable fake names and locations with add/remove/reset UI
- Mobile enable/disable toggle
- Per-store-view configuration

### Admin integration
- Menu under Panth Extensions with direct link to configuration
- ACL resource for granular admin permissions
- Product picker with AJAX search, pagination, and multi-select
- Fake names and locations managers with Alpine.js UI

### Database
- `panth_live_activity` table for real activity events
- `panth_live_activity_stats` table for product-level statistics
- Indexed on product_id, created_at, and activity_type

---

## Installation

### Via Composer (recommended)

```bash
composer require mage2kishan/module-live-activity
bin/magento module:enable Panth_Core Panth_LiveActivity
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Via uploaded zip

1. Download the extension zip from the Marketplace
2. Extract to `app/code/Panth/LiveActivity`
3. Make sure `app/code/Panth/Core` is also installed
4. Run the same commands above starting from `module:enable`

---

## Requirements

| | Required |
|---|---|
| Magento | 2.4.4 -- 2.4.8 (Open Source / Commerce / Cloud) |
| PHP | 8.1 / 8.2 / 8.3 / 8.4 |
| `mage2kishan/module-core` | ^1.0 (installed automatically as a composer dependency) |

---

## Configuration

Open **Stores > Configuration > Panth Extensions > Live Activity & Social Proof**.

### General Settings
- Enable/disable the module
- Notification position (bottom-left, bottom-right, top-left, top-right)
- Display delay, duration, and interval
- Maximum notifications per page view

### Activity Types
- Toggle each notification type independently
- Purchases, cart adds, wishlists, live viewers, trending, low stock

### Data Source
- Use real data, simulated data, or both
- Activity time range (1 hour to 7 days)
- Name anonymisation toggle
- Featured product picker (AJAX modal with search)
- Configurable fake names and locations

### Appearance
- Animation style (slide, fade, bounce, scale)
- Product image toggle
- Activity icon toggle
- Custom CSS field

### Advanced
- Category exclusion (multiselect)
- Mobile enable/disable

---

## Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | https://kishansavaliya.com |
| WhatsApp | +91 84012 70422 |

Response time: 1-2 business days for paid licenses.

---

## License

Commercial -- see `LICENSE.txt`. One license per Magento production
installation. Includes 12 months of free updates and email support.

---

## About the developer

Built and maintained by **Kishan Savaliya** -- https://kishansavaliya.com.
Builds high-quality Magento 2 extensions and themes for both Hyva and
Luma storefronts.
