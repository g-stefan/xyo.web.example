<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\LucideIcons;

defined("XYO_WEB") or die("Forbidden");

class LucideIcons extends \XYO\Web\Component
{

    protected static $name = "lucide-icons";

    public function init($options = null)
    {
        $this->view->cssLinks->set(self::$name, $this->site . "_site/xyo/lucide-icons/lucide-icons.min.css");
    }

    public function render($icon = null)
    {
        echo "<i class=\"lucide-" . $icon . "\"></i>";
    }

}

