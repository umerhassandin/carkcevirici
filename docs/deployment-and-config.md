# Deployment & configuration notes

This file exists because **this session has no WordPress or SSH access to
carkcevirici.com.** There is no `WP_APP_PASSWORD`, no SSH/SFTP host, and no
WP-CLI connection anywhere in this environment — only this empty git
repository. Everything in Sections 0–10 of the brief that requires logging
into WP admin, running WP-CLI over SSH, or clicking through the Cloudflare
dashboard could **not** be executed here. What follows is (a) the code that
*can* be version-controlled and handed to WP-CLI/SFTP once access exists,
and (b) the exact steps and settings a human (or a future session with
credentials) needs to apply by hand.

## What is actually in this repo right now

```
wp-content/
  plugins/carkcevirici-tools/   — the core product (Phase 2)
  themes/kadence-child/         — child theme skeleton (Phase 1, code part)
robots.txt                      — reference file, see "robots.txt" below
docs/deployment-and-config.md   — this file
```

Nothing else from the brief has been built yet: no live site backup (can't
be taken without server access), no Rank Math/LiteSpeed settings applied
(admin UI, not code), no Cloudflare configuration (separate dashboard, no
API token provided), no homepage/tool-page/hazır-çark/rehber *content*
(Phases 3–6 — those are WordPress posts/pages that need to be created
through wp-admin or the REST API with real credentials).

## 1. Before anything else: back up

Whoever has SSH/SFTP and WP admin access must take a full files + database
backup **before** installing the plugin or theme, and record where it's
stored. This was not possible to do from here.

## 2. Deploying the plugin and child theme

Once you have SSH/SFTP or WP-CLI access to the server:

```bash
# from a checkout of this repo
rsync -av wp-content/plugins/carkcevirici-tools/  user@host:/path/to/site/wp-content/plugins/carkcevirici-tools/
rsync -av wp-content/themes/kadence-child/         user@host:/path/to/site/wp-content/themes/kadence-child/

# or, with WP-CLI on the server:
wp plugin activate carkcevirici-tools
wp theme activate kadence-child
wp rewrite flush   # required once — registers /embed/, /llms.txt, /llms-full.txt
```

After activating, verify:
- `https://carkcevirici.com/embed/?preset=isim-carki` renders a bare wheel, `noindex`.
- `https://carkcevirici.com/llms.txt` returns Markdown.
- Ayarlar → Çark Hazır Listeler (Settings → Çark Hazır Listeler in wp-admin) shows the 13 built-in presets.
- Place `[cark preset="anasayfa"]` on a draft page and confirm the wheel spins, keyboard (Space/Enter) works, and the `<ol>` entry list is present in "View Source" even with JS disabled.

## 3. Rank Math checklist

Apply by hand in Rank Math's settings screens (Titles & Meta, Sitemap, General):

- **Titles & Meta → Global Meta**: `og:locale` = `tr_TR`, site language already `tr-TR` via WP's own setting.
- **Titles & Meta → Taxonomies/Archives**: noindex tag archives, date archives, author archives (single-author site).
- **Titles & Meta → Media**: attachments → noindex + redirect to parent (the child theme's `functions.php` already 301s attachment pages to their parent as a second layer).
- **Titles & Meta → Misc**: noindex internal search results (`/?s=`).
- **Sitemap**: include only `page`, `post`, and (once created) `hazir_cark`; exclude `/embed/` and any `?w=` shared-wheel URLs from indexing — they're fragment-based (`#w=...`) so they never reach the server/sitemap by construction, but double-check no plugin/theme accidentally turns them into real query params.
- **General → Links**: enable IndexNow (covers Bing + Yandex, which matters for the Turkish market).
- **Schema**: add Organization schema (name "Çark Çevirici", logo, URL) and WebSite schema site-wide. Per-page `WebApplication`/`FAQPage`/`Article`/`BreadcrumbList` JSON-LD is Phase 3+ content work (not yet built) — when those pages are created, verify Rank Math isn't also emitting a competing schema block for the same page (duplicate schema check from Section 9).
- **Canonicals**: on by default in Rank Math; confirm after content exists that shared `#w=` URLs still resolve to the plain page canonical (they will, since the fragment never hits the server).
- **robots.txt**: paste the contents of `/robots.txt` in this repo into Rank Math's robots.txt editor (General → Edit robots.txt), or drop the file at the site root if Rank Math's virtual robots.txt is disabled.

## 4. LiteSpeed Cache checklist

- Page cache: on.
- CSS/JS Minify: on.
- CSS/JS Combine: **test carefully** — combining can reorder `cark-confetti` / `cark-wheel` / `cark-tools` relative to each other; the plugin enqueues them with explicit dependencies (`cark-wheel` depends on `cark-confetti`) so a correct build tool will preserve order, but verify a spin still triggers confetti after enabling combine.
- Defer JS: the plugin's own scripts are already registered with `strategy => 'defer'`, so this is more about the theme/other plugins.
- Lazy-load images below the fold: on. The wheel's own canvas is never lazy-loaded (it must render its static first frame immediately for LCP).
- Image optimization: WebP/AVIF on.
- Browser cache + object cache: on per LiteSpeed's own recommended TTLs.

## 5. Cloudflare (once DNS is pointed at Cloudflare)

- SSL/TLS mode: **Full (strict)**.
- Always Use HTTPS: on.
- Auto Minify: leave CSS/JS off if LiteSpeed is already minifying (avoid double-processing); HTML minify can stay on.
- Brotli: on.
- Caching level: Standard; respect origin cache headers (LiteSpeed sets these).
- Page Rule or Cache Rule: bypass cache for `/wp-admin/*` and `/wp-login.php`.
- Rocket Loader: **off** — it rewrites `<script>` tags and is a common cause of canvas/JS tools breaking; the wheel's scripts are already `defer`d correctly without it.
- Under Attack Mode: off by default, available if the `/embed/` or `/llms.txt` endpoints ever get hit by abusive traffic.

## 6. www vs non-www, http vs https

Pick one canonical host (brief doesn't specify; `https://carkcevirici.com` — non-www — is assumed throughout this repo's code, e.g. the embed credit link). Set the 301 at Cloudflare or in WordPress's own site URL setting, not both, to avoid a redirect loop.

## 7. Known gaps / not implemented in this pass

- **Service worker / offline support / web app manifest** — explicitly marked optional/phase-2 in the brief; not built. Needs a manifest.json with real icon assets, which belongs with the logo/favicon work (also not done — no design assets were provided).
- **Per-segment images** — explicitly marked phase 2 in the brief; not built.
- **JS/CSS gzip budget (≤35 KB / ≤10 KB)** — written to be lean (no dependencies, no unused code paths) but the actual gzip size can only be measured by shipping it; verify with `gzip -c wheel.js | wc -c` etc. after deploy and with real Lighthouse runs, which need a live URL.
- **Randomness test (100k simulated spins)** — the picking algorithm (`pickWinnerIndex` in `wheel.js`) is a standard weighted-cumulative selection over `crypto.getRandomValues()`; it's mathematically unbiased, but the brief asks for an actual simulation summary to publish on `/nasil-calisir/`. That page doesn't exist yet (Phase 7 content); run the simulation once content work starts.
- **Logo/favicon, OG images, screenshots** — design assets, not code; need either real assets from the user or a separate design pass.
- **All page content** (homepage copy, 16 tool pages, 40 hazır çark entries as CPT posts, 12 guides, legal pages) — this is Phases 3–6, and creating it means writing real WordPress posts/pages, which needs REST API or WP-CLI access this session doesn't have.

## 8. Suggested next session

Once SSH/SFTP or an Application Password is available, the fastest path is:
1. Deploy this repo's plugin + child theme (Section 2 above).
2. Apply the Rank Math / LiteSpeed / Cloudflare checklists (Sections 3–6).
3. Resume at Phase 3 (homepage) with real WP access, since the tool itself is ready to embed.
