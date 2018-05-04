
<?php

class Keyword
{
    const TITLE = 1;
    const H1 = 0.9;
    const H2 = 0.8;
    const H3 = 0.7;
    const H4 = 0.6;
    const H5 = 0.5;
    const H6 = 0.4;
    const LINK = 0.2;
    const BODY = 0.1;

    private $raw;
    private $canonical;
    private $type;
    private $strength;

    public function __construct($value, $type)
    {
        $this->raw = $value;
        $this->canonical = $value;
        $this->type = $type;
        $this->strength = $type;
    }

    public function occurence($nb = 1, $type = self::BODY)
    {
        $this->strength += $nb * $type;
    }

    public function getStrength()
    {
        return $this->strength;
    }

}

class InvertedIndex implements ArrayAccess
{
    private $words;

    public function __construct()
    {
        $this->words = [];
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->words);
    }

    public function offsetGet($offset)
    {
        return $this->words[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (!$this->offsetExists($offset)) {
            $this->words[$offset] = [];
        }
        $this->words[$offset][] = $value;
        $value[] = $offset;
    }

    public function offsetUnset($offset)
    {
        unset($this->words[$offset]);
    }
}

class Link
{
    public function __construct($url, $from, $to)
    {

    }
}

class Crawler
{
    const LINK_REG = '#<a[^>]+href="([^#][\\w\\/\\.\\-&:#=%?]+)"[^>]+>([^<]+)<\\/a>#i';
    const TITLE_REG = '#<title>([^<]+)<\\/title>#i';
    const H_REG = '#<h(1|2|3|4|5|6)[^>]+>([^<]+)<\\/h\1>#i';

    private static $antiDico = [
        'le', 'la', 'j\'', 'l\'', 'elle', 'il', 'tu', 'nous', 'vous'
    ];

    private $queue;
    private $index;
    private $docs;

    public function __construct()
    {
        $this->queue = new SplQueue();
        $this->index = [];
        $this->docs = [];
    }

    public function crawl($url)
    {
        $this->queue->enqueue(new Document($url));
        while ($this->queue->count() > 0) {
            $this->parse($this->queue->dequeue());
        }
    }

    /**
     * we could have used DOMDocument but parsing a whole document
     * to fetch only some informations may be better with reg-exp
     * @param Document $document
     */
    private function parse(Document $document)
    {
        $html = file_get_contents($document->getUrl());
        if (false === $html) {
            return;
        }

        if (1 === preg_match(self::TITLE_REG, $html, $matches)) {
            $value = $matches[1]; // TODO lemme
            $word = new Keyword($value, Keyword::TITLE);
            $this->index[$word] = $document;
        }

        if (preg_match_all(self::H_REG, $html, $matches) > 0) {
            foreach ($matches as $match) {
                $type = $match[1];
                $value = $match[2]; // TODO lemme
                switch ($type) {
                    case '1':
                        $hType = Keyword::H1;
                        break;
                    case '2':
                        $hType = Keyword::H2;
                        break;
                    case '3':
                        $hType = Keyword::H3;
                        break;
                    case '4':
                        $hType = Keyword::H4;
                        break;
                    case '5':
                        $hType = Keyword::H5;
                        break;
                    case '6':
                    default:
                        $hType = Keyword::H6;
                        break;
                }
                $word = new Keyword($value, $hType);
                $this->index[$word] = $document;
            }
        }

        if (preg_match_all(self::LINK_REG, $html, $matches) > 0) {
            foreach ($matches as $match) {
                $link = $match[1];
                $value = $match[2]; // TODO lemme

                $url = new Url($link, $document->getUrl());
                if ($url->follow()) {
                    $linked = new Document($url);
                    $this->queue->enqueue($linked);
                    $document->linked($linked);

                    $word = new Keyword($value, Keyword::LINK);
                    $this->index[$word] = $document;
                }
            }
        }

        foreach ($document->words() as $word) {
            if (preg_match_all("#{$word->raw()}#i", $html, $matches) > 0) {
                $word->occurence(array_shift($matches));
            }
        }
    }

}

class Url
{
    const HTTP_SCHEME = 'http';

    private $raw;
    private $url;

    private $scheme = null;
    private $host = null;
    private $path = null;
    private $query = null;
    private $fragment = null;
    private $port = null;

    private $follow = true;

    /**
     * Url constructor.
     * @param $url
     * @param Url|null $context
     */
    public function __construct($url, Url $context = null)
    {
        list (
            'scheme' => $this->scheme,
            'host' => $this->host,
            'path' => $this->path,
            'query' => $this->query,
            'fragment' => $this->fragment,
            'port' => $this->port
            ) = parse_url($url) + $context->getParts() ?? [];

        $this->follow = $this->port && $this->port !== 80 || !!$this->host;

        $this->raw = $url;
        $this->url = "{$this->scheme}://{$this->host}";
        if ($this->path) {
            $this->url .= "/{$this->path}";
        }
        if ($this->query) {
            $this->url .= "/"
        }

        if (array_key_exists('scheme', $parts)) {
            // only take http
            if ($parts['scheme'] === 'https') {
                $this->scheme = self::HTTP_SCHEME;
            } else {
                $this->scheme = $parts['scheme'];
            }

            $this->url .= $this->scheme;
        }

        if (array_key_exists('host', $parts)) {
            $this->host = $parts['host'];
            $this->url .= $this->host;
        }

        if (array_key_exists('path', $parts)) {
            $this->host = $parts['host'];
            $this->url .= $this->host;
        }

    }
}

class Document
{
    private $url;
    private $words;
    private $referenceTo;
    private $referencedBy;

    public function __construct(Url $url)
    {
        $this->url = $url;
        $this->words = [];
        $this->referenceTo = [];
        $this->referencedBy = [];
    }


}

$link = '//meta.wikimedia.org/a/b?c#d';

var_dump(parse_url($link));