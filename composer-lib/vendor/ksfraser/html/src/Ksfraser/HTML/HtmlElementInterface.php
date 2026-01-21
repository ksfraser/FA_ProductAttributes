<?php
/*
 * Copyright (c) 2025 Kevin Fraser
 *
 * See the file LICENSE.txt for copying permission.
 */

namespace Ksfraser\HTML;

/**
 * Interface for HTML elements
 * Provides methods for both direct output and string return of HTML
 */
interface HtmlElementInterface {
    /**
     * Returns HTML string representation
     */
    public function getHtml(): string;

    /**
     * Outputs HTML directly
     */
    /**
     * Render the element as HTML.
     *
     * Outputs the HTML representation of this element.
     * Implementations should emit the markup (for example using echo or print)
     * rather than returning it.
     *
     * @return void
     */
    public function toHtml(): void;
}
?>
