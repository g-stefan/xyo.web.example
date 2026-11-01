<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\OverlayScrollbars;

defined("XYO_WEB") or die("Forbidden");

class OverlayScrollbars extends \XYO\Web\Component
{

    protected static $name = "overlay-scrollbars";

    public function init($options = null)
    {
        $this->view->cssLinks->set(self::$name, $this->site . "_site/xyo/overlay-scrollbars/overlayscrollbars.min.css");
        $this->view->cssLinks->set(self::$name, $this->site . "_site/xyo/overlay-scrollbars/overlayscrollbars.mod.css");
        $this->view->jsLinks->set(self::$name, $this->site . "_site/xyo/overlay-scrollbars/overlayscrollbars.browser.es6.min.js", "defer");
        $this->view->jsLinks->set(self::$name, $this->site . "_site/xyo/overlay-scrollbars/overlayscrollbars.js", "defer");
        // overlayscrollbars.js applies the scrollbars to this class on load.
        $this->view->bodyClasses->set("overlay-scrollbars");
        // Hides the native scrollbars of <html> / <body> until the overlay
        // scrollbars are initialized (no flicker while the deferred JS loads).
        $this->view->htmlAttributes->set("data-overlayscrollbars-initialize", true);
        $this->view->bodyAttributes->set("data-overlayscrollbars-initialize", true);
    }

}

