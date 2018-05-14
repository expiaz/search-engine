<?php

namespace SearchEngine\Core\Document;

class Url
{
    private $raw;
    private $url;
    private $uri;

    private $scheme;
    private $host;
    private $path;
    private $query;
    private $fragment;
    private $port;

    private $context;

    /**
     * Url constructor.
     * @param $url
     * @param Url|null $context
     */
    public function __construct(string $url, ?Url $context = null)
    {
        $parts = parse_url($url);
        if (null !== $context) {
            $parts += $context->getParts();
        }

        $this->scheme = $parts['scheme'] ?? null;
        $this->host = $parts['host'] ?? null;
        $this->path = $parts['path'] ?? null;
        $this->query = $parts['query'] ?? null;
        $this->fragment = $parts['fragment'] ?? null;
        $this->port = $parts['port'] ?? null;

        $this->raw = $url;
        $this->context = $context;
        $this->url = "{$this->scheme}://{$this->host}";
        $this->uri = "{$this->scheme}://{$this->host}";
        if ($this->port && (int) $this->port !== 80) {
            $this->url .= ":{$this->port}";
            $this->uri .= ":{$this->port}";
        }
        if ($this->path) {
            if ($this->path[0] === '/') {
                $this->path = substr($this->path, 1);
            }
            $this->url .= "/{$this->path}";
            $this->uri .= "/{$this->path}";
        }
        if ($this->query) {
            $this->url .= "?{$this->query}";
            $this->uri .= "?{$this->query}";
        }
        if ($this->fragment) {
            $this->url .= "#{$this->fragment}";
        }
    }

    /**
     * @return null|string
     */
    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    /**
     * @return null|string
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return null|string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @return null|string
     */
    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * @return null|string
     */
    public function getFragment(): ?string
    {
        return $this->fragment;
    }

    /**
     * @return null|int
     */
    public function getPort(): ?int
    {
        return (int) $this->port;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function equals(Url $url)
    {
        return $this->host === $url->getHost() &&
            $this->path === $url->getPath() &&
            (
                $this->query === $url->getQuery() ||
                false !== strpos($url->getQuery(), $this->query)
            );
    }

    /**
     * @param Document[] $traversed
     * @param Document[] $pageLinks
     * @return bool does the crawler should explore this ressource
     */
    public function shouldFollow(?array $traversed = [], ?array $pageLinks = []): bool
    {
        // a page is identified by it's domain, path and query
        // the fragment does not identify a different page

        // no scheme or host, can't identify the page
        if (!$this->scheme || !$this->host) {
            return false;
        }

        if (null !== $this->context) {
            // same page as the page where link is
            // or query contains the actual page and will
            // gives redundant informations
            if ($this->equals($this->context)) {
                return false;
            }
        }

        // only follow http pages, discards tel:, mail:, javascript:, flash: ...
        if (!in_array($this->scheme, ['http', 'https'])) {
            return false;
        }

        // excludes malformed domains
        if (false === preg_match(
            '#([\\w.\\-~!$&\'()+]+\.)?[\w.\\-~!$&\'()+]+\\.([a-z]+)#',
            $this->host
        )) {
            return false;
        }

        foreach ($pageLinks as $traversed) {
            // the url is redundant in the document
            if ($this->equals($traversed->getUrl())) {
                return false;
            }
        }

        if (array_key_exists($this->getUri(), $traversed)) {
            // already accessed before
            echo "\tfollow {$this->getUrl()} [CACHE]\n";
            return true;
        }

        // try to fetch the page
        // and verify statusCode and contentType
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
//        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_NOBODY, 1);

        $content = curl_exec($curl);
        $err = curl_error($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $ctype = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        curl_close($curl);

        if ($err) {
            return false;
        }

        if (200 !== $status) {
            return false;
        }

        if (false === strrpos($ctype, 'text/html')) {
            return false;
        }

        echo "\tfollow {$this->getUrl()} [FETCH]\n";
        return false !== $content;
    }

    /**
     * @return null|string the body of the ressource
     */
    public function getRessource(): ?string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);

        $content = curl_exec($curl);
        $err = curl_errno($curl);
        curl_close($curl);

        if ($err) {
            return null;
        }
        return $content;
    }

    public function getParts(): array
    {
        return [
            'scheme' => $this->scheme,
            'host' => $this->host,
            'path' => $this->path,
            'query' => $this->query,
            'fragment' => $this->fragment,
            'port' => $this->port
        ];
    }

    public function __toString(): string
    {
        return $this->url;
    }

}