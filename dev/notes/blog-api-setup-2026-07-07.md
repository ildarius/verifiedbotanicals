# Blog API Setup (2026-07-07)

## Goal
Provide a narrow authenticated API for external scripts to create and update Magefan blog posts, including SEO metadata, without exposing the raw Magefan blog REST contract directly.

## Module
- Custom module: `Local_BlogApi`
- Path: `app/code/Local/BlogApi/`

## Endpoint
- Route: `POST /rest/V1/local/blog/post/upsert`
- Web API config: `app/code/Local/BlogApi/etc/webapi.xml`
- ACL resource: `Local_BlogApi::post_upsert`

## Authentication
- The route is not anonymous.
- Requests must include a valid Magento bearer token.
- Current recommended use is a dedicated restricted integration principal with access only to `Local_BlogApi::post_upsert`.
- Do not use a general-purpose admin token in production unless there is no better option.

Admin token example:

```bash
curl -k -X POST 'https://magento.ddev.site/rest/V1/integration/admin/token' \
  -H 'Content-Type: application/json' \
  -d '{"username":"<admin-username>","password":"<admin-password>"}'
```

## IP Whitelist
- Requests are filtered before any upsert logic runs.
- Implementation: `app/code/Local/BlogApi/Model/IpWhitelist.php`
- Wiring: `app/code/Local/BlogApi/etc/di.xml`
- Current allowed range:

```text
24.157.155.*
```

If a request comes from any other IP, the endpoint returns `403` with a clear message:

```json
{
  "message": "Client IP \"172.18.0.7\" is not allowed to access this endpoint. Allowed IP ranges: 24.157.155.*."
}
```

### Important reverse-proxy caveat
- The whitelist currently uses Magento’s detected client IP via `Magento\Framework\HTTP\PhpEnvironment\RemoteAddress`.
- If production sits behind Cloudflare, nginx proxy, a load balancer, or another reverse proxy, Magento may see the proxy IP instead of the true caller IP.
- If that happens, the whitelist must be paired with correct trusted-proxy / forwarded-header handling, or the API will either block valid callers or whitelist the wrong address.

## Request Contract
The service reads JSON from the request body directly and supports either:
- a wrapped body with `postData`
- or a plain top-level object

Current preferred shape:

```json
{
  "postData": {
    "identifier": "example-post",
    "title": "Example Post",
    "content": "<p>Body</p>",
    "short_content": "Short intro",
    "content_heading": "Example Heading",
    "meta_title": "Example Meta Title",
    "meta_keywords": "example,blog,seo",
    "meta_description": "Example meta description",
    "og_title": "Example OG Title",
    "og_description": "Example OG Description",
    "og_type": "article",
    "publish_time": "2026-07-07 12:00:00",
    "end_time": "2026-07-21 12:00:00",
    "is_active": 1,
    "author_id": 1,
    "store_ids": [0],
    "category_ids": [3],
    "category_identifiers": ["news"],
    "tag_ids": [5],
    "tag_identifiers": ["guides"],
    "tag_titles": ["Kratom", "Guides"]
  }
}
```

## Upsert Behavior
- External key: `identifier`
- If the identifier does not exist for the supplied store scope, a post is created.
- If the identifier already exists, the post is updated.
- On create, `title` and `content` are required.
- If `publish_time` is omitted on create, it defaults to current GMT time.
- If `store_ids` is omitted, it defaults to `[0]`.
- Categories are only replaced if category fields are present in the request.
- Tags are only replaced if tag fields are present in the request.
- `tag_titles` auto-creates missing Magefan blog tags.

## Fields Currently Supported
Mapped direct fields:
- `title`
- `content`
- `short_content`
- `content_heading`
- `meta_title`
- `meta_keywords`
- `meta_description`
- `og_title`
- `og_description`
- `og_type`
- `author_id`
- `is_active`
- `featured_img_alt`
- `featured_list_img_alt`

Additional handled fields:
- `identifier`
- `publish_time`
- `end_time`
- `store_ids`
- `category_ids`
- `category_identifiers`
- `tag_ids`
- `tag_identifiers`
- `tag_titles`

## Response Contract
The endpoint returns a typed Web API data object, not a raw positional array.

Example successful response:

```json
{
  "status": "updated",
  "post_id": 20,
  "identifier": "local-blog-api-test-1783456402",
  "title": "Local Blog API Test Post",
  "post_url": "https://magento.ddev.site/blog/post/local-blog-api-test-1783456402",
  "store_ids": [0],
  "category_ids": [],
  "tag_ids": [8],
  "publish_time": "2026-07-07 12:00:00",
  "is_active": 0,
  "meta_title": "Updated Meta Title",
  "meta_keywords": "api,test,updated",
  "meta_description": "Updated meta description from authenticated API test",
  "og_title": "Updated OG Title",
  "og_description": "Updated OG Description",
  "og_type": "article"
}
```

## Example Call

```bash
TOKEN='...'

curl -k -X POST 'https://magento.ddev.site/rest/V1/local/blog/post/upsert' \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "postData": {
      "identifier": "red-bali-guide",
      "title": "Red Bali Guide",
      "content": "<p>Article body</p>",
      "short_content": "Short intro",
      "meta_title": "Red Bali Guide | Site Name",
      "meta_keywords": "red bali, kratom",
      "meta_description": "Guide to Red Bali.",
      "og_title": "Red Bali Guide",
      "og_description": "Guide to Red Bali.",
      "og_type": "article",
      "publish_time": "2026-07-07 10:00:00",
      "is_active": 1,
      "store_ids": [0],
      "category_identifiers": ["news"],
      "tag_titles": ["Kratom", "Guides"]
    }
  }'
```

## Security Notes
- The route is safer than exposing the raw Magefan REST API because it narrows the write surface to one operation and one mapped field set.
- It still trusts the caller to send HTML content.
- A leaked bearer token still allows blog post mutation from allowed IPs.
- Current hardening in place:
  - authenticated route
  - narrow ACL resource
  - IP whitelist
- Additional hardening worth considering later:
  - request audit logging
  - HMAC request signing
  - reverse-proxy-aware IP trust configuration
  - disabling automatic tag creation if taxonomy drift becomes a problem

## Validation Performed
After the module work on `2026-07-07`:

```bash
docker exec -u 1000 ddev-magento-web php bin/magento setup:upgrade
docker exec -u 1000 ddev-magento-web php bin/magento setup:di:compile
```

Verified behaviors:
- unauthenticated request returns `401`
- authenticated request from a non-whitelisted IP returns `403` with the custom invalid-IP message
- authenticated request from an allowed context successfully:
  - created a disposable test post
  - updated its SEO metadata
  - returned the typed JSON response above
  - allowed cleanup of the disposable test post

## Relevant Files
- `app/code/Local/BlogApi/etc/webapi.xml`
- `app/code/Local/BlogApi/etc/acl.xml`
- `app/code/Local/BlogApi/etc/di.xml`
- `app/code/Local/BlogApi/Api/BlogPostSyncManagementInterface.php`
- `app/code/Local/BlogApi/Api/Data/BlogPostSyncResultInterface.php`
- `app/code/Local/BlogApi/Model/BlogPostSyncManagement.php`
- `app/code/Local/BlogApi/Model/Data/BlogPostSyncResult.php`
- `app/code/Local/BlogApi/Model/IpWhitelist.php`
