<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Validation\ValidationException;

class AssistantHtml
{
    private const TAGS = [
        'p', 'h1', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u', 's', 'del',
        'blockquote', 'ul', 'ol', 'li', 'a', 'img', 'br', 'hr', 'code', 'pre',
    ];

    public function ensureEditable(string $html): void
    {
        if (mb_strlen($html) > 12000) {
            throw ValidationException::withMessages(['content' => 'Текст слишком длинный для одного запроса.']);
        }
        if (preg_match('~<(div|table|iframe|section|figure|video|audio|svg|script|style|form|object|embed)\b|\s(class|style|data-[\w-]+)\s*=~i', $html)) {
            throw ValidationException::withMessages(['content' => 'Поле содержит нестандартную HTML-разметку. Для безопасности отредактируйте её вручную в режиме HTML.']);
        }
        $document = $this->document($html);
        foreach ($document->getElementsByTagName('*') as $element) {
            if (strtolower($element->tagName) !== 'img' && ($element->hasAttribute('width') || $element->hasAttribute('height'))) {
                throw ValidationException::withMessages(['content' => 'Поле содержит нестандартную HTML-разметку. Для безопасности отредактируйте её вручную в режиме HTML.']);
            }
        }
    }

    public function clean(string $html, string $original): string
    {
        if (mb_strlen($html) > 20000) {
            throw ValidationException::withMessages(['content' => 'Ответ ИИ слишком длинный.']);
        }

        $document = $this->document($html);
        $root = $document->documentElement;
        foreach (iterator_to_array($root->childNodes) as $child) $this->cleanNode($child);
        $result = '';
        foreach ($root->childNodes as $child) $result .= $document->saveHTML($child);
        $result = trim($result);
        if (trim(strip_tags($result)) === '') {
            throw ValidationException::withMessages(['content' => 'ИИ вернул пустой текст.']);
        }
        foreach (['a' => 'href', 'img' => 'src'] as $tag => $attribute) {
            if ($this->urls($original, $tag, $attribute) !== $this->urls($result, $tag, $attribute)) {
                throw ValidationException::withMessages(['content' => 'ИИ изменил ссылки или изображения. Повторите запрос с указанием сохранить их без изменений.']);
            }
        }

        return $this->restoreImageDimensions($result, $original);
    }

    private function document(string $html): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument('1.0', 'UTF-8');
            $document->loadHTML('<?xml encoding="utf-8"?><div id="assistant-root">'.$html.'</div>', LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED | LIBXML_NONET);
            return $document;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function cleanNode(DOMNode $node): void
    {
        if (! $node instanceof DOMElement) {
            if ($node->nodeType !== XML_TEXT_NODE) $node->parentNode?->removeChild($node);
            return;
        }
        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'form'], true)) {
            $node->parentNode?->removeChild($node);
            return;
        }
        foreach (iterator_to_array($node->childNodes) as $child) $this->cleanNode($child);
        if (! in_array($tag, self::TAGS, true)) {
            while ($node->firstChild) $node->parentNode?->insertBefore($node->firstChild, $node);
            $node->parentNode?->removeChild($node);
            return;
        }
        foreach (iterator_to_array($node->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);
            $safe = ($tag === 'a' && $name === 'href' && $this->safeUrl($value, false))
                || ($tag === 'img' && in_array($name, ['src', 'alt'], true) && ($name === 'alt' || $this->safeUrl($value, true)));
            if (! $safe) $node->removeAttributeNode($attribute);
        }
    }

    private function safeUrl(string $url, bool $image): bool
    {
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) return true;
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array(strtolower((string) $scheme), $image ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'], true);
    }

    private function restoreImageDimensions(string $html, string $original): string
    {
        $sizes = [];
        foreach ($this->document($original)->getElementsByTagName('img') as $image) {
            $source = $image->getAttribute('src');
            $dimensions = [];
            foreach (['width', 'height'] as $attribute) {
                $value = $image->getAttribute($attribute);
                if ($value !== '' && ctype_digit($value)) $dimensions[$attribute] = $value;
            }
            $sizes[$source][] = $dimensions;
        }
        if ($sizes === []) return $html;

        $document = $this->document($html);
        foreach ($document->getElementsByTagName('img') as $image) {
            $source = $image->getAttribute('src');
            foreach (array_shift($sizes[$source]) ?? [] as $attribute => $value) {
                $image->setAttribute($attribute, $value);
            }
        }
        $result = '';
        foreach ($document->documentElement->childNodes as $child) $result .= $document->saveHTML($child);
        return trim($result);
    }

    private function urls(string $html, string $tag, string $attribute): array
    {
        $document = $this->document($html);
        $values = [];
        foreach ($document->getElementsByTagName($tag) as $element) {
            if ($element->hasAttribute($attribute)) $values[] = $element->getAttribute($attribute);
        }
        sort($values);
        return $values;
    }
}
