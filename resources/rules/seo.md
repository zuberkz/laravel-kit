---
paths:
  - "app/Services/Seo/**/*.php"
  - "app/Seo/**/*.php"
  - "app/Http/Controllers/**/*.php"
  - "resources/views/pages/**"
  - "resources/views/components/layouts/**"
  - "routes/web.php"
---

# SEO

- Meta tags are set in one place: a `SeoService` filled by the controller or the page component
  (`$seo->page($title, $description)->image($url)`), rendered once in the layout. Templates do not
  write `<title>` or `<meta>` themselves.
- Every page: a unique `<title>` (main query first, up to ~60 characters), a
  `<meta name="description">` (140–160 characters, the benefit and a call to action), one `<h1>`,
  `og:title`, `og:description`, `og:image` (1200×630), `og:url`.
- Canonical and every absolute URL for search engines (canonical, `og:url`, sitemap, JSON-LD) are
  on the main domain from `APP_URL` (the kit's `CanonicalUrlSupport::current()`), even when the site is opened on `www`
  or a technical domain. Paginated pages get their own canonical with `?page=N`.
- Structured data (JSON-LD) through a `SchemaService` (spatie/schema-org): `Organization` /
  `LocalBusiness` on every page, `BreadcrumbList`, and the type of the page (`Product`, `Service`,
  `Article`, `FAQPage`). Only facts the page shows; `Product` only for real goods with offers.
- `robots.txt` and `sitemap.xml` are the kit's routes; there is no `public/robots.txt` (nginx would
  serve it before Laravel). Outside production `robots.txt` disallows everything and every
  response carries `X-Robots-Tag: noindex, nofollow`. The admin path is never listed in
  `robots.txt`. Extra disallowed paths — `config/kit.php` → `seo.robots_disallow`.
- A section of the sitemap is a class implementing `Zuber\Kit\Modules\Seo\Contracts\SitemapSourceInterface`
  in `app/Seo/Sitemap` (`urls()` yields `SitemapUrlData` of published records only), tagged
  `SitemapSourceInterface::TAG` in `AppServiceProvider`; a new section of the site gets its
  source in the same commit.
- Addresses: short, latin, hyphenated slugs (`/services/business-card`), no `.html`, no
  transliteration noise. A changed address gets a 301 from the old one (a redirects table checked
  by middleware before the 404); old URLs of a migrated site are mapped once and kept.
- Images have meaningful `alt`; the first-screen image is not lazy (rule `media.md`).
- Tests: every public page — status 200, its `<title>`, one `<h1>`, canonical on `APP_URL`; the
  sitemap lists every published record and nothing unpublished.
