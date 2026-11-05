---
paths:
  - "app/Models/**/*.php"
  - "app/Services/**/*.php"
  - "config/filesystems.php"
  - "config/media-library.php"
---

# Images and files

## Storage
- Where files live is configuration, not code: uploads use a disk from `.env` (`public` locally,
  `s3` in production) — `config('filesystems.uploads')` for own uploads, `MEDIA_DISK` for
  spatie/laravel-medialibrary. Code never hard-codes `Storage::disk('public')`.
- Code works with any disk: no `->path()` (a remote disk has no local path) — process a file in a
  temporary local file, then `putFileAs()` to the disk; URLs through `->url()`, never by gluing
  `/storage/`.
- Moving existing files between disks is an artisan command (rule `commands.md`), not a
  migration.
- Upload limits must match on every level: the form (`maxSize`), PHP (`upload_max_filesize`,
  `post_max_size` — `public/.user.ini` on ISPmanager hosting) and nginx (`client_max_body_size`).
  A file over the PHP limit fails as a 422 on Livewire's `upload-file`, not with a clear error.

## Images (spatie/laravel-medialibrary)
- Image driver — Imagick (`IMAGE_DRIVER=imagick`): GD loads the whole bitmap into PHP memory and
  kills the worker on long screenshots.
- **Every conversion sets quality explicitly** (`->quality(85–90)`), otherwise Imagick writes WebP
  at 75 — images look soft and dull. Output format WebP (`->format('webp')`).
- Size by the displayed size **on retina**: a 300 px cell needs a 600 px image. A small preview
  has two conversions, 1x and 2x (`thumb`, `thumb-2x`), and the `<img>` gets
  `srcset="… 1x, … 2x"`; large images use responsive images with a correct `sizes`.
- After downscaling, a light `->sharpen(5)`; never upscale (`Fit::Max`), never stretch a small
  source.
- Long screenshots: `Fit::Max, 1920, 16383` — 16383 px is the WebP side limit.
- Images inside rich text are converted on upload (WebP, max width ≈ 2× the text column, quality
  ~90) by one service used by the admin editor and by any import.
- Conversions are queued on a separate queue (`media`, rule `queues.md`); a template shows the
  original until the conversion is ready (`hasGeneratedConversion()`), never a broken image.
- After changing a conversion, existing files are regenerated with
  `media-library:regenerate <morph alias> --only=…` — the alias as stored in `model_type`, not the
  class name.
- Every `<img>` has `alt`, `width`, `height`, `loading="lazy"` below the fold and
  `fetchpriority="high"` for the main image of the first screen.
