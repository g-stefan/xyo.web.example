<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Library {

    defined("XYO_WEB") or die("Forbidden");

    require_once ("./_site/web.php");

    class XYOWebExampleCSS extends \XYO\Web\Component
    {

        protected static $name = "xyo-web-example-css";

        public function init(){
            $this->view->cssLinks->removeGroup("xyo-web-css");
            $this->view->cssLinks->set(self::$name, $this->site."_site/library/xyo-web-example.css");
        }        

    }

}
