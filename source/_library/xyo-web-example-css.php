<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Library;

defined("XYO_WEB") or die("Forbidden");

class XYOWebExampleCSS extends \XYO\Web\Component
{

    protected static $name = "xyo-web-example-css";

    public function init($options = null)
    {
        $this->view->cssLinks->removeGroup("xyo-web-css");
        $this->view->cssLinks->set(self::$name, $this->site . "_library/xyo-web-example.css");
    }

}

