<?php
declare(strict_types=1);

namespace Vonage\Video\Entity;

use Laminas\Diactoros\Request;
use Vonage\Entity\Filter\KeyValueFilter;
use Vonage\Entity\IterableAPICollection as EntityIterableAPICollection;

class IterableAPICollection extends EntityIterableAPICollection
{
    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var int
     */
    protected $count = 50;

    /**
     * Override to return items from the 'items' key instead of HAL structure
     * 
     * @return array<int, mixed>
     */
    public function getResourceRoot(): array
    {
        // Handle empty responses
        if (empty($this->pageData)) {
            return [];
        }

        // Items are always under the 'items' key for this API
        return $this->pageData['items'] ?? [];
    }

    /**
     * Override to check for more pages based on the current count vs total count
     */
    public function valid(): bool
    {
        // Can't be valid if there's no page data
        if (!isset($this->pageData)) {
            return false;
        }

        // If no items in current page, we're done
        if (!count($this->getResourceRoot())) {
            return false;
        }

        // Reset current index if needed
        if ($this->current < 0) {
            $this->current = 0;
        }

        // If we're past the current page items and auto-advance is enabled
        if ($this->getAutoAdvance() && !isset($this->getResourceRoot()[$this->current])) {
            // Check if there are more items to fetch
            $totalItems = $this->pageData['count'] ?? 0;
            
            if ($this->offset < $totalItems) {
                // Fetch next page
                $this->fetchPage($this->getApiResource()->getBaseUri());
                $this->current = 0;
                
                return count($this->getResourceRoot()) > 0;
            }
            
            // No more items to fetch
            return false;
        }

        // Check if current item exists
        return isset($this->getResourceRoot()[$this->current]);
    }

    /**
     * Override to use count from response instead of HAL structure
     */
    public function count(): int
    {
        if (!isset($this->pageData)) {
            $this->rewind();
        }

        if (isset($this->pageData)) {
            // Force counting the items for legacy reasons
            if ($this->getNaiveCount()) {
                return count($this->getResourceRoot());
            }

            // Use count from the API response
            if (array_key_exists('count', $this->pageData)) {
                return $this->pageData['count'];
            }

            // Fallback to counting items
            return count($this->getResourceRoot());
        }

        return 0;
    }

    /**
     * Override to handle offset-based pagination instead of HAL links
     * @param string|null $absoluteUri
     */
    protected function fetchPage($absoluteUri): void
    {
        if (is_null($absoluteUri)) {
            throw new \RuntimeException('Cannot determine URI to use for page fetch');
        }

        // Use filter if no query provided
        if (false === strpos($absoluteUri, '?')) {
            $query = [];

            if (isset($this->filter)) {
                $query = array_merge($this->filter->getQuery(), $query);
            }

            $query['offset'] = $query['offset'] ?? $this->offset;
            $query['count'] = $query['count'] ?? $this->count;

            $absoluteUri .= '?' . http_build_query($query);
        }

        $requestUri = $absoluteUri;

        if (filter_var($absoluteUri, FILTER_VALIDATE_URL) === false) {
            $requestUri = $this->getApiResource()->getBaseUrl() . $absoluteUri;
        }

        $cacheKey = md5($requestUri);
        if (array_key_exists($cacheKey, $this->cache)) {
            /** @var array<string, mixed> $cachedData */
            $cachedData = $this->cache[$cacheKey];
            $this->pageData = $cachedData;
            return;
        }

        $request = new Request($requestUri, 'GET');

        if ($this->getApiResource()->getAuthHandlers()) {
            $request = $this->getApiResource()->addAuth($request);
        }

        $response = $this->client->send($request);

        $this->getApiResource()->setLastRequest($request);
        $this->response = $response;
        $this->getApiResource()->setLastResponse($response);

        $body = $this->response->getBody()->getContents();
        $json = json_decode($body, true);

        if (!is_array($json)) {
            $json = [];
        }

        $this->cache[md5($requestUri)] = $json;
        $this->pageData = $json;

        // Update offset for next page based on items received
        if ($this->pageData && isset($this->pageData['items']) && is_array($this->pageData['items'])) {
            $this->offset += count($this->pageData['items']);

            // Update filter with new offset for potential next page
            $filterQuery = $this->filter ? $this->filter->getQuery() : [];
            $filterQuery['offset'] = $this->offset;
            $filterQuery['count'] = $this->count;
            $this->setFilter(new KeyValueFilter($filterQuery));
        }

        if ((int)$response->getStatusCode() !== 200) {
            throw $this->getException($response);
        }
    }
}
