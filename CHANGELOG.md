# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.14] - 2026-10-03

### Fixed
- The admin product picker search no longer fails with a server error when `query`, `page` or `limit` is sent as an array; such values fall back to the defaults. The page number is at least 1 and the page size is limited to 1 to 20 results.
- Custom fake names saved with the enabled flag as 1 or "1" (instead of a JSON true) are now used; before, they were dropped and the default names were shown.
- On a product page, simulated activity now names the product being viewed instead of random catalog products. If that product cannot be loaded, the random product pool is used as before.
