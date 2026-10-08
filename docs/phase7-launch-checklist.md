# Phase 7 — Final QA, Launch Checklist, SEO Setup, Content Plan

This is the wrap-up deliverable for Section 10 ("Phase 7 — Final QA + launch
checklist") of the original brief. Everything here is a checklist or a plan
for *you* to execute once the site has live content — none of it could be
run from this session (no WordPress admin access, no browser/Lighthouse
tooling, no way to submit to Search Console on your behalf).

## 1. QA checklist (Section 9) — run once pages are published

Device/browser matrix:
- [ ] Chrome Android (mid-range and low-end device, or emulated)
- [ ] iOS Safari
- [ ] Desktop Chrome, Firefox, Edge
- [ ] A 1366×768 laptop screen
- [ ] Full-screen mode on a 1920×1080 display (classroom/"smart board" use case)

Lighthouse (mobile), run on at minimum:
- [ ] Homepage
- [ ] 3 tool pages (suggest: İsim Çarkı, Kura Çekme, Çark Çevir)
- [ ] 1 hazır çark page (suggest: Türkiye İlleri — longest entry list, worst case)
- [ ] 1 rehber guide (suggest: Çark Çevirme Nasıl Yapılır — longest guide)
- Targets from the brief: LCP < 2.0s, CLS < 0.05, INP < 150ms, Performance ≥ 95,
  Accessibility ≥ 95, SEO 100. If any page misses a target, the wheel plugin's
  CSS/JS (already measured at ~8.9KB and ~2.7KB gzipped, well under budget) is
  unlikely to be the cause — check Kadence/Kadence Pro's own asset weight and
  LiteSpeed's cache/minify settings first.

Structured data:
- [ ] Validate every page type's JSON-LD with Google's Rich Results Test —
      homepage, a tool page, a hazır çark page, a rehber guide.
- [ ] Confirm Rank Math isn't emitting a second, competing schema block on the
      same page (check page source for duplicate `@type` entries).

Content/technical checks:
- [ ] Titles ≤ 60 characters (every page's SEO meta is specified in the
      instruction header of its own content file in `/content/`).
- [ ] Meta descriptions 140–160 characters.
- [ ] Exactly one H1 per page (the WP Page Title field is the H1 for every
      page built in this project — none of the pasted content blocks include
      a second `<h1>`).
- [ ] No broken internal links — the pages reference each other extensively
      (tool ↔ hazır çark ↔ rehber); a full click-through pass after
      publishing everything is worth doing once.
- [ ] All images have Turkish alt text (no images were part of this content
      delivery — screenshots/OG images are a separate design task, see
      Section 7 below).
- [ ] Sitemap includes pages/posts and (once hazır çark content is live)
      excludes `/embed/` and any `?w=` style URLs — the plugin's embed
      endpoint already sends `noindex`, and the share-URL feature uses a URL
      *fragment* (`#w=...`), which never reaches the server or a sitemap.
- [ ] Turkish proofreading pass — I wrote and re-read every page for ı/i,
      ğ/ş/ç/ö/ü correctness and natural phrasing, but a native speaker's
      final pass before publishing is still worth doing, especially on the
      legal pages.

Randomness test:
- [x] **Already done** — see `/nasil-calisir/`'s content file
      (`content/19-nasil-calisir.html`). Real 100,000-spin simulation run
      against the exact algorithm in `wheel.js`, both equal-weight and
      weighted cases. Not re-needed unless the selection algorithm changes.

## 2. Search engine setup (do this once the site has live, approved content)

**Google Search Console**
1. [search.google.com/search-console](https://search.google.com/search-console) → Add property → use the domain property (covers http/https/www variants) → verify via DNS TXT record (ask your registrar/Hostinger for DNS access) or via the HTML file method if DNS isn't convenient.
2. Submit the sitemap: Sitemaps → enter `sitemap_index.xml` (Rank Math's default URL) → Submit.
3. Request indexing for the homepage and a couple of key tool pages manually after publishing, to speed up the first crawl.

**Bing Webmaster Tools**
1. [bing.com/webmasters](https://www.bing.com/webmasters) → Add site → you can import directly from Google Search Console (fastest) or verify separately.
2. Submit the same sitemap URL.
3. Bing also serves Yahoo search, so this covers both.

**Yandex Webmaster** (relevant for Turkey's search mix)
1. [webmaster.yandex.com](https://webmaster.yandex.com) → Add site → verify via meta tag or DNS.
2. Submit the sitemap.
3. Rank Math's IndexNow integration (mentioned in `docs/deployment-and-config.md`, Section 3) auto-pings Bing *and* Yandex on publish/update once enabled — turn that on in Rank Math's General settings so you don't have to manually resubmit every time content changes.

## 3. Backlink / outreach — a note on how I approached this

The brief asks for "30 Turkish backlink/outreach targets (teacher blogs,
education forums, eTwinning resources, streamer communities)." I searched
for this rather than inventing it, and the honest result is: I could not
verify 30 currently-active, specific Turkish teacher blogs or forums from
here. Several things that turned up were a decade-old news portal section of
uncertain current status, academic papers (not outreach targets), and job
listing pages. Publishing a list of 30 named sites I can't confirm are real
or currently active would be the kind of "invented information" the brief
explicitly told me to avoid — so instead, here's an outreach **strategy**
you (or whoever runs outreach) can execute with real, current results:

**Verified, genuinely real channels to start with:**
- **eTwinning Türkiye** (the official national platform, run under MEB's
  Yenilik ve Eğitim Teknolojileri Genel Müdürlüğü) — not a blog you pitch,
  but the teachers active on it are exactly your audience; look for Turkish
  eTwinning project coordinators who blog or post independently about
  classroom tools.
- **EBA (Eğitim Bilişim Ağı)** — MEB's own platform; not a backlink target
  itself (it's a government platform), but worth knowing as the ecosystem
  teachers already use, for positioning ("works alongside what you use in
  EBA/akıllı tahta").

**Self-service method to build the real list** (15–30 minutes of searching, with specific query strings that work better than the ones I tried):
1. `site:.com.tr "öğretmen blogu"` and `"sınıf yönetimi" blog` — Google
   site-search operators surface currently-live Turkish teacher blogs far
   better than a general web search does.
2. Search Facebook/Instagram for "öğretmenler grubu", "sınıf öğretmenleri",
   "okul öncesi öğretmenleri" — these groups often have tens of thousands of
   members and admins who accept relevant tool recommendations; a mention in
   the group, or from an admin's own blog, is a realistic win.
3. Search YouTube for Turkish teacher/classroom-management channels and
   Twitch/Kick for Turkish streamers who run giveaways — comment sections
   and channel descriptions are common backlink opportunities once you've
   built a relationship (e.g., offering them a free embeddable wheel for
   their own channel via `/sitene-ekle/`).
4. eTwinning's project database (publicly browsable) lists real project
   coordinators by name and school — several maintain their own sites.
5. Once 2–3 months of hazır çarklar and rehber content is live and indexed,
   outreach gets considerably easier: you have 40+ genuinely useful pages to
   point to instead of a bare homepage, which matters for response rates.

If you'd rather have a concrete list even with the verification caveat, I
can run a deeper, slower research pass (the "extended" web search mode) —
just say so and I'll follow up with named candidates, flagged by how
confident I am each is current and active.

## 4. Three-month content plan

Everything through Phase 6 (homepage, 16 tool pages, hub pages, 40 hazır
çarklar, 12 rehber guides, legal/trust pages) is already written and
delivered as of this session. This plan covers what comes *after* initial
launch, per the brief's own roadmap (Section 7.3: "Scale to 150+ over the
next months" and Section 7.4: "Later: 2–4 guides/month").

**Month 1 — Launch + immediate expansion**
- Publish everything already delivered; run the QA checklist above.
- Submit to Search Console/Bing/Yandex (Section 2).
- Add 15–20 more hazır çarklar using real Turkish search queries: check
  Google Autocomplete and Search Console's "Queries" report (once it has
  data) for what people actually type after "çark" — this beats guessing.
  Good next batches: more spor (basketbol takımları, voleybol takımları),
  more eğitim (fiiller, zamirler for Turkish grammar practice), seasonal
  (if launching near a holiday).
- Start outreach per Section 3 once 2-3 weeks of indexing has happened.

**Month 2 — Seasonal + deepening**
- Seasonal content called out in the brief: yılbaşı çekilişi, yılbaşı hediye
  çarkı (if timed near year-end) or 23 Nisan etkinlikleri / ramazan iftar
  menüsü çarkı (if timed near those dates) — pick whichever is actually
  upcoming when you reach this month, not a fixed date.
- 2–4 new rehber guides: good next topics per the brief's own hint list —
  a guide specifically for streamers/content creators on running on-stream
  giveaways, a guide on using the wheel for office/team decisions, a deeper
  "sınıf yönetimi" (classroom management) guide linking several tool pages.
- Review Search Console's Performance report: pages with impressions but low
  CTR need better titles/meta; pages with neither need more internal links
  pointing to them.

**Month 3 — Optimize + fill gaps**
- By now you'll have real Search Console query data — use it to find
  "near-miss" hazır çarklar (searches close to but not matching an existing
  wheel) and build pages for them specifically.
- Revisit the legal pages once a lawyer has reviewed them (flagged in each
  file as "hukuki inceleme gerekir").
- If outreach landed any backlinks, check which referring pages/anchor text
  performed best and lean into that angle for further outreach.
- Consider the deferred Phase 2 items noted in
  `docs/deployment-and-config.md` (web app manifest, service worker,
  per-segment images) if usage data shows demand (e.g., many mobile users
  "adding to home screen" manually would justify the manifest work).

---

*This document, like the rest of this session's work, lives in the git repo
on branch `claude/carkcevirici-turkish-wheel-tq18ww` for `umerhassandin/carkcevirici`.*
