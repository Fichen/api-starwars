<?php

if (! function_exists('getIDFromURL')) {
    /**
     * Helper for extracting id item from specific item (vehicle or starship)
     */
    function getIDFromURL($url)
    {
        return basename(parse_url($url, PHP_URL_PATH));
    }
}
