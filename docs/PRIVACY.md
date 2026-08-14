# Privacy

NOW Campaign Storefronts does not require an external SaaS service for campaign operation or reporting and does not intentionally transmit store or customer data to a third-party service as part of its core functionality.

## Campaign data

Campaign configuration is stored in the WordPress/WooCommerce site database.

## Orders and attribution

Campaign attribution is stored on WooCommerce order items. WooCommerce remains the order and financial authority.

## External live report

The optional external report is hosted by the merchant's own WordPress site. It is designed to expose aggregate campaign metrics and product-level performance only.

The public report does not intentionally expose customer names, email addresses, phone numbers, postal addresses, or order numbers.

## Report password

External report access uses WordPress Core password-protected-content behavior. NOW Campaign Storefronts creates an internal, non-public WordPress record whose `post_password` value acts as the report sharing password, and WordPress manages the unlocked browser session through its standard `wp-postpass_*` cookie.

This is a **sharing password**, not a WordPress user-account password. WordPress stores post/page protection passwords in the post record so they can be managed as protected-content passwords. Store owners should therefore use a dedicated report sharing password and should not reuse an administrator, customer, payment, email, or other sensitive account credential.

The sharing password is available to authorized WooCommerce managers in the Campaign editor so it can be copied and shared with the intended report viewer. It is not intentionally emitted in the public report HTML or report data response.

Pre-release/private builds that used the earlier custom report-authentication mechanism may migrate a recoverable legacy report password to the WordPress-native password record and remove the obsolete custom-auth credential metadata.

Site owners remain responsible for their overall WordPress privacy policy, hosting configuration, backups, logs, analytics, security plugins, and any third-party extensions installed alongside NOW Campaign Storefronts.
