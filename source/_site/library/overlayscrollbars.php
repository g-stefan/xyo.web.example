<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Library {

    defined("XYO_WEB") or die("Forbidden");

    require_once ("./_site/web.php");

    class OverlayScrollbars extends \XYO\Web\Component
    {

        protected static $name = "overlayscrollbars";

        public function init()
        {
            $this->view->cssLinks->set(self::$name, $this->site . "_site/library/overlayscrollbars.min.css");
            $this->view->jsLinks->set(self::$name, $this->site . "_site/library/overlayscrollbars.browser.es6.min.js", "defer");
            $this->view->jsLinks->set(self::$name, $this->site . "_site/library/overlayscrollbars.js", "defer");
            $this->view->htmlClasses->set("data-overlayscrollbars-initialize");
            $this->view->bodyClasses->set("data-overlayscrollbars-initialize");
            $this->view->bodyClasses->set("overlay-scrollbars");
        }

    }

}
