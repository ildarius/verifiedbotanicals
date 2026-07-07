<?php
declare(strict_types=1);

namespace Local\BlogApi\Api;

use Local\BlogApi\Api\Data\BlogPostSyncResultInterface;

interface BlogPostSyncManagementInterface
{
    /**
     * Create or update a Magefan blog post using its identifier as the external key.
     *
     * Request body example:
     * {
     *   "postData": {
     *     "identifier": "example-post",
     *     "title": "Example Post",
     *     "content": "<p>Body</p>",
     *     "meta_title": "Example Meta Title",
     *     "meta_description": "Example Meta Description",
     *     "store_ids": [0],
     *     "category_identifiers": ["news"],
     *     "tag_titles": ["Kratom", "Guides"]
     *   }
     * }
     *
     * @return \Local\BlogApi\Api\Data\BlogPostSyncResultInterface
     */
    public function upsert(): BlogPostSyncResultInterface;
}
