<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

defined("XYO_WEB") or die("Forbidden");

require_once ("./_site/web.php");
require_once ("./_site/library/lucide-icons.php");
use \XYO\Library\LucideIcons;

class ComponentAjaxNested extends \XYO\Web\ComponentAJAX
{

    public function init()
    {
        if ($this->isAJAX() && $this->isPost()) {
            $this->sessionSet("count", $this->sessionGet("count", 0) + 1);
        }

        LucideIcons::register($this, "icons");
    }

    protected function renderAJAX()
    { ?>

        <div class="min-w-[480px] bg-white mb-3 p-0 shadow-xl ring-1 ring-slate-900/5 sm:mx-auto sm:max-w-lg sm:rounded-lg sm:px-10">
            <div class="p-6 flex flex-row items-center justify-between space-y-0 pb-2">
                <h3 class="tracking-tight text-sm font-medium">Nested AJAX component</h3>
                <?php $this->renderComponent("icons", "cpu"); ?>
            </div>
            <div class="p-6 pt-0">
                <div class="text-2xl font-bold">Counter: <?php echo $this->sessionGet("count", 0); ?></div>
                <p class="text-xs text-muted-foreground">This component is using ajax.</p>
            </div>
        </div>

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
                }, 2000);

            </script>
        <?php }, "load");
    }

}
