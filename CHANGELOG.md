# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] -- Initial release

### Added -- 6 notification types
- **Recent purchases** -- "John D. from New York purchased this 5 minutes ago"
- **Cart additions** -- "Sarah M. added this to cart 2 minutes ago"
- **Wishlist additions** -- tracked via `wishlist_add_product` event
- **Live viewers** -- simulated viewer count per product
- **Trending products** -- view count in configurable time period
- **Low stock alerts** -- real stock qty when available, simulated fallback

### Added -- real-time activity tracking
- Observer on `sales_order_place_after` tracks order placements
- Observer on `checkout_cart_product_add_after` tracks cart additions
- Observer on `wishlist_add_product` tracks wishlist additions
- All tracked data stored in `panth_live_activity` database table
- Name anonymisation ("John D." format) for privacy compliance

### Added -- simulated activity
- Configurable fake customer names (add/remove/reset UI)
- Configurable fake customer locations (add/remove/reset UI)
- Featured product picker with AJAX search modal in admin
- Random product selection from catalog as fallback
- Category exclusion to prevent unwanted products in notifications

### Added -- frontend notifications
- Fixed-position notification popup with close button and progress bar
- 4 animation styles: slide, fade, bounce, scale
- 4 position options: bottom-left, bottom-right, top-left, top-right
- Product image thumbnail display
- Activity type icon display
- Click-to-navigate to product page
- Fully responsive mobile design with safe-area insets
- Reduced-motion media query support
- Dark theme support via CSS variables
- Dedicated CSS for both Hyva and Luma themes
- CSS variables driven by `theme-config.json` for Hyva Theme Customizer

### Added -- admin configuration
- Stores > Configuration > Panth Extensions > Live Activity & Social Proof
- 5 configuration groups: General, Activity Types, Data Source, Appearance, Advanced
- ACL resource `Panth_LiveActivity::config` for granular permissions
- Admin menu under Panth Extensions with direct config link
- Product picker modal with search, pagination, multi-select
- Fake names manager with Alpine.js interactive UI
- Fake locations manager with Alpine.js interactive UI

### Added -- database
- `panth_live_activity` table with indexes on product_id, created_at, activity_type
- `panth_live_activity_stats` table with unique constraint on product_id

### Quality
- Constructor injection only -- zero `ObjectManager::getInstance()` usage
- All PHP files pass MEQP (Magento2 coding standard) with zero errors
- Full PHPDoc coverage on all public and protected methods
- `declare(strict_types=1)` on all PHP files

### Compatibility
- Magento Open Source / Commerce / Cloud 2.4.4 -- 2.4.8
- PHP 8.1, 8.2, 8.3, 8.4
- Hyva Theme compatible
- Luma Theme compatible

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
