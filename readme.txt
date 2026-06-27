=== Mak8it BotLens ===
Contributors: mak8it
Donate link: https://mak8it.com/donate
Tags: generative engine optimization, robots, artificial intelligence, llms sitemap, seo
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Monitor and control AI search crawlers, generate llms.txt sitemaps, and manage crawler rules via robots.txt filters.

== Description ==

Mak8it BotLens is a lightweight, Generative Engine Optimization (GEO) suite designed to help WordPress site owners monitor, verify, and control how artificial intelligence crawlers and scrapers interact with their site content.

Features:
*   **IP-Verified Bot Detection**: Dynamically validates requests from popular AI crawlers (like GPTBot, Googlebot, and Applebot) against verified CIDR ranges and reverse DNS checks.
*   **llms.txt Generator**: Generates structured, compliant /llms.txt and /llms-full.txt virtual sitemaps to feed clean markdown summaries to AI engines.
*   **Smart robots.txt Integrations**: Injects Advisory AI Bot Control directives directly into robots.txt virtually via WordPress filters without editing physical files.
*   **SEO Bridges**: Directly integrates and fetches indexable page data, descriptions, and noindex instructions from Yoast, Rank Math, and AIOSEO.
*   **Admin Dashboard**: Clean and modern summary panel of all recent AI bot request crawls, separating verified bots from spoofed crawlers.

This plugin runs entirely locally and does not require third-party SaaS endpoints or API keys to operate.

== Installation ==

1. Upload the `mak8it-botlens` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the settings panel and activity tracker under the 'BotLens' menu in the sidebar.

== Frequently Asked Questions ==

= Does this plugin physically edit my robots.txt file? =
No. The plugin uses the native WordPress filter hook `robots_txt` to dynamically append advisory rules for specified AI bots. It will not write or overwrite any physical file.

= Where are /llms.txt sitemaps cached? =
Sitemap feeds are cached using WordPress Transients for 24 hours to maximize database performance. The cache is automatically cleared whenever posts are saved, status changes, or settings are saved.

= Which AI crawlers are supported? =
The plugin tracks and handles rules for OpenAI (GPTBot, OAI-SearchBot, ChatGPT-User), Anthropic (ClaudeBot), Google (Googlebot, Google-Extended), Apple (Applebot), Common Crawl (CCBot), and Meta (Meta-ExternalAgent).

= What external APIs or services are called? =
This plugin performs outbound requests to sync IP registry directories. The default endpoints fetched weekly:
*   openai.com/gptbot.json (IP range fetch)
*   openai.com/searchbot.json (IP range fetch)
*   openai.com/chatgpt-user.json (IP range fetch)
*   perplexity.ai/perplexitybot.json (IP range fetch)

== External Services ==

This plugin periodically connects to external servers in the background to fetch and synchronize the latest verified IP directories for known AI crawlers:
* OpenAI IP ranges: https://openai.com/gptbot.json
* OpenAI SearchBot ranges: https://openai.com/searchbot.json
* ChatGPT User-Agent ranges: https://openai.com/chatgpt-user.json
* Perplexity AI ranges: https://www.perplexity.ai/perplexitybot.json

== Screenshots ==

1. Dashboard with AI bot request metrics and crawl log summaries.
2. Settings screen with individual bot blocking toggles.
3. Active logs panel displaying crawler request history with verification badges.

== Changelog ==

= 1.0.0 =
* Initial release.
* IP verification checks for ChatGPT, Claude, Googlebot, and Applebot.
* robots.txt rule injection.
* Virtual routing for llms.txt and llms-full.txt.
