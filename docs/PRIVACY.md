# Privacy

WC Campaign does not require an external SaaS service for campaign operation or reporting and does not intentionally transmit store or customer data to a third-party service as part of its core functionality.

## Campaign data

Campaign configuration is stored in the WordPress/WooCommerce site database.

## Orders and attribution

Campaign attribution is stored on WooCommerce order items. WooCommerce remains the order and financial authority.

## External live report

The optional external report is hosted by the merchant's own WordPress site. It is designed to expose aggregate campaign metrics and product-level performance only.

The public report does not intentionally expose customer names, email addresses, phone numbers, postal addresses, or order numbers.

## Passwords

The public authentication flow uses a WordPress password hash. An encrypted recoverable copy may be stored so authorized WooCommerce administrators can view/copy the share password in the campaign editor. The plaintext password is not intentionally emitted in the public report HTML or report data endpoint.

Site owners remain responsible for their overall WordPress privacy policy, hosting configuration, backups, logs, analytics, security plugins, and any third-party extensions installed alongside WC Campaign.
