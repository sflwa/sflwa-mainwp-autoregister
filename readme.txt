=== SFLWA MainWP Auto-Register Bridge ===
Contributors: sflwa
Tags: mainwp, automation, cli, wp-cli, registration
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.3
Stable tag: 1.8.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Advanced bridge for MainWP Child that automates site registration to a central Dashboard via WP-CLI.

== Description ==

The SFLWA MainWP Auto-Register Bridge is a developer-focused tool designed to streamline the connection process between Child sites and a MainWP Dashboard. It specifically addresses handshake authentication issues and automates the assignment of Groups and Clients.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure 'MainWP Child' is also installed and active.

== CLI Commands ==

= Registration =
Connect the site to the Dashboard.
`wp sflwa-mainwp register --group=3,1 --client=5 --user=1`

= Pre-Register Health Check =
Audit the local database for settings that might block a handshake.
`wp sflwa-mainwp pre-register --user=1`

= Lookups =
Retrieve IDs from the Dashboard for use in registration.
`wp sflwa-mainwp groups`
`wp sflwa-mainwp clients`

== Changelog ==

= 1.8.1 =
* Timeout Resilience: Increased API request timeout to 90 seconds.
* Enhanced Logging: CLI now outputs targeted User, Groups, and Client ID at the start of execution.
* Success Mapping: Extracted MainWP Site ID from Dashboard response for success messaging.

= 1.8.0 =
* Targeted User Registration: Added --user flag to specify which administrator account to use for the handshake.
* User Meta Bypass: Specifically targets 'mainwp_child_user_enable_passwd_auth_connect' in User Meta.

= 1.7.5 =
* Auth Scrubbing: Added logic to wipe 'mainwp_child_auth' to prevent "Credential Fallback" errors.
* Handshake Optimization: Added automated clearing of stale public keys before registration attempts.

= 1.6.4 =
* Multi-Group Support: Enabled comma-separated string handling for groupids.
* JSON Normalization: Added support for associative JSON responses from MainWP API.
* Sorting: Implemented numeric ID sorting for CLI tables.

= 1.6.0 =
* Added support for client_id mapping.
* Added 'clients' CLI subcommand.

= 1.5.0 =
* Added Auto-Provisioning layer to force child settings.

= 1.4.0 =
* Added 'groups' CLI subcommand.
* Support for dynamic --group parameter.

= 1.0.0 =
* Initial release.
