# Blog Restyle Progress

Date: 2026-07-24
Scope: Magefan Blog cleanup and restyle on `Sm/market`

## Goals

- Remove broken/demo blog data from the local preview and provide a portable remote patch.
- Restyle the blog listing and post pages through theme/module overrides, not content hardcoding.
- Verify the result with Playwright before handoff.

## Findings

- Local blog archive currently renders with no published posts visible on 2026-07-24.
- `magefan_blog_post.post_id = 1` is scheduled for 2026-07-28, so it does not appear yet.
- Sidebar categories currently include zero-count/demo entries through `Magefan_Blog::sidebar/categories.phtml`.
- Sidebar recent posts explicitly renders `Magefan_Blog::images/default-no-image.png` when a post has no image.
- Existing theme overrides already live under `app/design/frontend/Sm/market/Magefan_Blog/`.

## Work Log

- 2026-07-24: Started audit of Magefan Blog templates, local blog data, and current storefront output.
- 2026-07-24: Seeded three local preview posts:
  - `how-we-package-botanical-orders-for-canadian-shipping`
  - `a-short-guide-to-reading-botanical-product-labels`
  - `kratom-in-montreal-what-buyers-ask-before-ordering`
- 2026-07-24: Added theme overrides under `app/design/frontend/Sm/market/Magefan_Blog/` for:
  - single-post header/layout
  - shared post meta line
  - list-card markup
  - sidebar category/recent-post rendering
  - blog typography and spacing in `web/css/blog-custom.css`
- 2026-07-24: Added remote-executable cleanup script:
  - `dev/tools/blog_cleanup_patch.php`
  - removes exact demo categories listed by the user
  - strips `lorem ipsum` paragraphs from matching `Kratom in Montreal` posts if present
- 2026-07-24: Ran local cleanup script with `--apply`, which removed the demo `Fashion` category from the local DB.
- 2026-07-24: Ran:
  - `docker exec -u 1000 ddev-magento-web php bin/magento setup:static-content:deploy -f --theme Sm/market en_US`
  - `docker exec -u 1000 ddev-magento-web php bin/magento cache:clean block_html full_page layout`
- 2026-07-24: Captured local staging preview artifacts with Playwright:
  - `.playwright/artifacts/blog-restyle-20260724/blog-list-desktop.png`
  - `.playwright/artifacts/blog-restyle-20260724/blog-post-desktop.png`
  - `.playwright/artifacts/blog-restyle-20260724/blog-list-mobile.png`
  - `.playwright/artifacts/blog-restyle-20260724/blog-post-mobile.png`
  - `.playwright/artifacts/blog-restyle-20260724/summary.json`

## Pending

- Review the local preview with the user before porting to the remote server.
- If approved for remote, run `php dev/tools/blog_cleanup_patch.php --apply` there, then deploy the updated theme assets and clean caches.

## Hand-off

- Current verified local state:
  - post page is now a real desktop 2-column layout
  - main article column is left-aligned in the main content area
  - right sidebar is present beside the article, not below it
  - sidebar blocks currently shown on post pages:
    - search
    - categories
    - recent posts
    - archive
- Last verified desktop artifact:
  - `.playwright/artifacts/blog-restyle-20260724-two-column-fixed/shipping-post-desktop-viewport.png`
- Last verified geometry summary:
  - `.playwright/artifacts/blog-restyle-20260724-two-column-fixed/summary.json`
  - main column ends at `1065px`
  - sidebar starts at `1105px`
  - both top edges align
- Files most relevant for the next session:
  - `app/design/frontend/Sm/market/Magefan_Blog/web/css/blog-custom.css`
  - `app/design/frontend/Sm/market/Magefan_Blog/layout/blog_default.xml`
  - `app/design/frontend/Sm/market/Magefan_Blog/layout/blog_post_view.xml`
  - `app/design/frontend/Sm/market/Magefan_Blog/templates/sidebar/categories.phtml`
  - `app/design/frontend/Sm/market/Magefan_Blog/templates/sidebar/recent.phtml`
- Remaining issue to address next:
  - the right sidebar widgets still look too soft and overdesigned relative to the cleaner article column
  - search box currently reads as a large green pill/button treatment instead of a normal search field
  - categories and recent-post cards still have too much radius, shadow, and padding
  - category counts are visually detached at the far right edge
  - vertical spacing between sidebar widgets should be tightened and normalized

### Resume Prompt

Continue the Magento Magefan Blog restyle work in `/home/ildar/projects/magento` without redoing the already-fixed post/article typography or 2-column layout. The current local post page has a verified right sidebar beside the article, and the latest verified screenshot is `.playwright/artifacts/blog-restyle-20260724-two-column-fixed/shipping-post-desktop-viewport.png`. The remaining issue is the sidebar styling. Please clean up the right sidebar widgets only:

- Search box: It currently renders as a huge green pill button with no visible input field, inside an oversized padded card. Make it a normal search bar: a visible text input with placeholder `Search the blog`, a reasonably sized search icon/button, and much less padding around it. It should look like a standard search field, not a giant button.
- Categories and Recent Posts boxes: Reduce the heavy rounded corners, drop shadows, and excess internal padding. They look like puffy floating bubbles. Aim for flat or lightly bordered cards with tighter, consistent spacing.
- Category counts: The `(2)` and `(1)` numbers are pushed to the far right edge, detached from their labels. Align them closer to the category name or use a small muted badge right after the label.
- Spacing: Tighten and normalize the vertical spacing between the three sidebar boxes.
- Overall goal for the sidebar: flat, minimal, tightly spaced, matching the clean look of the article column.

Use the existing theme overrides under `app/design/frontend/Sm/market/Magefan_Blog/`, verify the result with Playwright, and check the actual screenshot before concluding that the sidebar looks correct.
