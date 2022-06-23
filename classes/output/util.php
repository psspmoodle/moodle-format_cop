<?php

namespace format_cop\output;

use DOMDocument;

class util
{

    /**
     *
     * Re mb_convert_encoding: DOMDocument treats text as ISO-8859-1
     * see https://stackoverflow.com/questions/8218230/php-domdocument-loadhtml-not-encoding-utf-8-correctly
     * Re LIBXML_HTML_NODEFDTD: this prevents a default doctype being added when one is not found
     * see https://stackoverflow.com/questions/4879946/how-to-savehtml-of-domdocument-without-html-wrapper
     *
     * @param $content
     * @return DOMDocument
     */
    public static function open_domdocument($content): DOMDocument
    {
        $domdoc = new DOMDocument();
        // libxml doesn't support HTML5 tags: <audio>, etc, will throw errors
        libxml_use_internal_errors(true);
        $domdoc->loadHTML('<html><body>' . mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8') . '</body></html>',
            LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        return $domdoc;
    }

    /**
     * Removes <html><body></body></html>
     *
     * @param DOMDocument $domdoc
     * @return false|string
     */
    public static function close_domdocument(DOMDocument $domdoc)
    {
        return substr(trim($domdoc->saveHTML()), 12, -14);
    }

}