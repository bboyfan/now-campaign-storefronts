# Contributing to WC Campaign

Thank you for helping improve WC Campaign.

## Principles

WC Campaign must not create a second source of truth for WooCommerce products, inventory, carts, discounts, orders, or refunds. Changes should preserve WooCommerce as the commerce and financial authority.

## Before opening a pull request

- Keep changes focused and avoid unrelated refactors.
- Sanitize input, verify nonces/capabilities, and escape output according to WordPress standards.
- Preserve WooCommerce HPOS compatibility.
- Test simple products and purchasable variations.
- Test Campaign Price together with WooCommerce coupons and store pricing rules when relevant.
- Test cart/session isolation, checkout, attribution, reporting, and partial refunds when relevant.
- Check responsive storefront behavior when changing presentation code.
- Do not include customer data, production databases, credentials, paid plugins, themes, or local environment files.

## Syntax checks

Run PHP syntax checks over plugin PHP files and `node --check` over JavaScript files before submitting changes.

## Pull requests

Describe the problem, the scope of the change, and how you verified it. Keep one pull request focused on one concern whenever practical.

## Security issues

Do not open a public issue for a suspected vulnerability. Follow [SECURITY.md](SECURITY.md).

By contributing, you agree that your contribution is licensed under the same GPL-2.0-or-later license as the project.
