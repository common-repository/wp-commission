# WP Commission

WP Commission is a simple but powerful tool for website owners to enhance commission revenue with Amazon. The team behind WP Commission is dedicated to helping independent creators profit from quality content.

WPC is a SaaS offering which charges a fixed yearly fee in exchange for providing, among other services:

- dynamic link rewriting to make sure all Amazon links are tagged with affiliate tags
- beautiful auto-generated carousels with eye-catching images of Amazon items

## Technology

When a WordPress user installs WP Commission, WPC phones home to the servers at wpcommission.com in order to verify the user's identity.

Once the user purchases a license and configures WPC to speak to their WordPress installation, WPC is enabled for their WordPress installation and carousels of Amazon items may be displayed on their WordPress instance through the `[wpcommission]` shortcode.

WPC's WordPress plugin passes information about which Amazon items live on which pages back to the server so that the server can provide a list of items with which to populate carousels. As part of this process, WPC's server makes calls to the Amazon product API in order to get metadata like the product name and imagery from an ASIN (Amazon identifier, which is encoded in product URLs). The server also handles:

- blacklisting and unblacklisting items (so they don't show up in carousels)
- pinning and unpinning items (so they always show up first)
- manually adding items (by URL, if they aren't mentioned in any posts)
- retrieving lists of Amazon items with metadata (name, image URL, etc) given a set of filters passed into the shortcode

## Contact

Please email if you have any questions: sku@wpcommission.com.
