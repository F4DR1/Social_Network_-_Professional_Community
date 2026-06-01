<?php
    // page_path.php

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = strtok($_SERVER['REQUEST_URI'], '?');

    
    define('PROTOCOL', $protocol);
    define('HOST', $host);
    define('PATH', $path);

    

    
    
    /**
     * Преобразует IDN-домен (Punycode) в Unicode (кириллица, арабский и т.д.).
     * Если домен не требует преобразования (например, localhost), возвращает исходную строку.
     *
     * @param string $host Доменное имя (например, "xn--h1aakjefb7a.xn--p1ai")
     * @return string Декодированное доменное имя (например, "мирпрофи.рф")
     */
    function decodeHost(string $host): string {
        if (function_exists('idn_to_utf8')) {
            $decoded = idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($decoded !== false) {
                return $decoded;
            }
        }
        // Если не удалось декодировать (или функция недоступна), возвращаем как есть
        return $host;
    }

    function decodeUrl(string $url): string {
        $parts = parse_url($url);
        if (isset($parts['host'])) {
            $parts['host'] = decodeHost($parts['host']);
        }
        $result = ($parts['scheme'] ?? 'https') . '://' . $parts['host'] . ($parts['path'] ?? '') . ($parts['query'] ?? '') . ($parts['fragment'] ?? '');
        return $result;
    }
?>
