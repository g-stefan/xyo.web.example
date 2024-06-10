<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

defined("XYO_WEB") or die("Forbidden");

require_once ("./site/web.php");
require_once ("./example/component-ajax-nested.php");
require_once ("./site/library/lucide-icons.php");
use \XYO\Library\LucideIcons;

class ComponentAjax extends \XYO\Web\ComponentAJAX
{

    public function init()
    {
        if ($this->isAJAX() && $this->isPost()) {
            $this->sessionSet("count", $this->sessionGet("count", 0) + 1);
        }

        LucideIcons::register($this, "icons");
        ComponentAjaxNested::register($this, "ajax-nested");
    }

    protected function renderAJAX()
    { ?>

        <div class="min-w-[480px] bg-white rounded-xl border bg-card text-card-foreground shadow mb-3">
            <div class="p-6 flex flex-row items-center justify-between space-y-0 pb-2">
                <h3 class="tracking-tight text-sm font-medium">AJAX component</h3>
                <?php $this->renderComponent("icons", "component"); ?>
            </div>
            <div class="p-6 pt-0">
                <div class="text-2xl font-bold">Counter: <?php echo $this->sessionGet("count", 0); ?></div>
                <p class="text-xs text-muted-foreground">This component is using ajax.</p>
            </div>
        </div>
        <?php $this->renderComponent("ajax-nested"); ?>

    <?php }

    protected function renderContainer(&$options = null)
    {
        echo "<div id=\"" . $this->id . "\">";
        $this->renderAJAX();
        echo "</div>";

        $this->view->jsSource(function () { ?>
            <script>

                setInterval(function () {
                    <?php $this->renderAJAXRequestPost(); ?>
                }, 10000);

            </script>
        <?php }, "load");
    }
}
