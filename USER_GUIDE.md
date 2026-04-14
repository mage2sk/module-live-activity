# Panth Live Activity & Social Proof -- User Guide

This guide walks a Magento store administrator through every screen
and setting of the Panth Live Activity extension. No coding required.

---

## Table of contents

1. [Installation](#1-installation)
2. [Verifying the extension is active](#2-verifying-the-extension-is-active)
3. [General settings](#3-general-settings)
4. [Activity types](#4-activity-types)
5. [Data source settings](#5-data-source-settings)
6. [Featured product picker](#6-featured-product-picker)
7. [Fake names and locations](#7-fake-names-and-locations)
8. [Appearance settings](#8-appearance-settings)
9. [Advanced settings](#9-advanced-settings)
10. [How notifications work](#10-how-notifications-work)
11. [Troubleshooting](#11-troubleshooting)

---

## 1. Installation

### Composer (recommended)

```bash
composer require mage2kishan/module-live-activity
bin/magento module:enable Panth_Core Panth_LiveActivity
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual zip

1. Download the extension package zip
2. Extract to `app/code/Panth/LiveActivity`
3. Make sure `app/code/Panth/Core` is also present
4. Run the same `module:enable ... cache:flush` commands above

---

## 2. Verifying the extension is active

After installation, two things should be true:

1. **Configuration page exists** -- Stores > Configuration > Panth Extensions > Live Activity & Social Proof is reachable
2. **Frontend notifications appear** -- visit any storefront page and wait for the configured display delay

If the configuration page is missing, run `bin/magento setup:di:compile`
and `bin/magento cache:flush`.

---

## 3. General settings

Navigate to **Stores > Configuration > Panth Extensions > Live Activity & Social Proof > General Settings**.

| Setting | Default | What it does |
|---|---|---|
| **Enable Live Activity** | Yes | Master switch for the entire module |
| **Notification Position** | Bottom Left | Where notifications appear (4 corners) |
| **Display Delay (seconds)** | 5 | Wait time before first notification (0-60) |
| **Notification Duration (seconds)** | 5 | How long each notification stays visible (3-30) |
| **Interval Between Notifications (seconds)** | 8 | Time between notifications (5-120) |
| **Maximum Notifications Per Page** | 10 | Max notifications per page view (0 = unlimited) |

---

## 4. Activity types

Toggle each notification type independently. All are enabled by default.

| Type | Example message |
|---|---|
| **Recent Purchases** | "John D. from New York purchased this 5 minutes ago" |
| **Cart Additions** | "Sarah M. added this to cart 2 minutes ago" |
| **Wishlist Additions** | "Emma A. added this to wishlist" |
| **Live Viewers** | "15 people are viewing this product right now" |
| **Trending** | "Trending: 50 views in last hour" |
| **Low Stock** | "Only 3 left in stock!" |

---

## 5. Data source settings

| Setting | Default | What it does |
|---|---|---|
| **Use Real Activity Data** | Yes | Track and display real customer activity from Magento events |
| **Use Simulated Activity** | Yes | Generate realistic fake activity for low-traffic stores |
| **Activity Time Range** | Last 24 Hours | How far back to pull real activity data |
| **Anonymize Customer Names** | Yes | Show "John D." format instead of full names |

Both real and simulated can run simultaneously. For new stores with no
order history, enable simulated data to show activity immediately.

---

## 6. Featured product picker

By default, the extension picks random enabled products from your
catalog for simulated notifications. To control which products appear:

1. In the **Data Source** group, find **Featured Product IDs**
2. Click **Select Products** to open the product picker modal
3. Search by product name, SKU, or ID
4. Click products to select/deselect them
5. Click **Done** to confirm your selection

Selected products will be used for all simulated notifications instead
of random products from the catalog.

---

## 7. Fake names and locations

### Fake names
- Pre-loaded with 15 default names in "FirstName L." format
- Add custom names using the text input
- Remove individual names with the X button
- Reset to defaults with one click
- Names are used for simulated activity notifications

### Fake locations
- Pre-loaded with 15 default cities (global mix)
- Add custom city names
- Remove or reset as needed
- Locations are used for both real (guest) and simulated notifications
- Override per store view for different geographic markets

---

## 8. Appearance settings

| Setting | Default | What it does |
|---|---|---|
| **Animation Style** | Slide In | Entrance animation (Slide, Fade, Bounce, Scale) |
| **Show Product Image** | Yes | Display product thumbnail in notification |
| **Show Activity Icon** | Yes | Display icon for activity type |
| **Custom CSS** | (empty) | CSS overrides targeting `.live-activity-notification` etc. |

The extension uses CSS variables for theming. Hyva stores can customise
colours via the Theme Customizer through `theme-config.json` variables:

- `--live-activity-bg` -- notification background
- `--live-activity-highlight` -- accent colour
- `--live-activity-text` -- primary text colour
- `--live-activity-text-secondary` -- secondary text colour

---

## 9. Advanced settings

| Setting | Default | What it does |
|---|---|---|
| **Exclude Categories** | (none) | Products in these categories will not appear in notifications |
| **Enable on Mobile** | Yes | Show/hide notifications on mobile devices |

The category exclusion field shows a hierarchical tree of all active
categories in your store.

---

## 10. How notifications work

1. The page loads and waits for the configured **display delay**
2. An AJAX request fetches activity data from `liveactivity/ajax/getactivity`
3. The response contains a mix of real and/or simulated activities
4. Notifications are shown one at a time with the configured **duration**
5. After each notification, the system waits for the configured **interval**
6. This continues until the **maximum notifications** limit is reached
7. Each notification has a close button and a progress bar
8. Clicking a notification with a product link navigates to the product page

On product pages, the extension also passes the current product ID so
that product-specific activity (viewers, this-product purchases) can be
prioritised.

---

## 11. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| No notifications appear | Module disabled or JS error | Check Configuration > General > Enable Live Activity. Check browser console for errors. |
| Notifications show but no product data | No enabled products in catalog | Ensure you have visible, enabled products. Check category exclusions. |
| Only simulated data, no real data | Real data disabled or no recent orders | Enable "Use Real Activity Data" and check the time range setting. |
| Notifications look broken on Hyva | Theme Customizer CSS variable conflict | Check your `theme-config.json` for conflicting variable names. |
| Mobile notifications not showing | Mobile disabled in config | Check Advanced > Enable on Mobile. |
| Product picker not loading | AJAX error | Check browser console. Ensure admin routes are configured and DI is compiled. |

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422

Response time: 1-2 business days for paid licenses.
