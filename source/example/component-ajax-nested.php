<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace Example;

defined("XYO_WEB") or die("Forbidden");

use \XYO\LucideIcons\LucideIcons;

class ComponentAjaxNested extends \XYO\Web\Component
{
    protected $count;

    public function init($options = null)
    {
        if ($this->isAJAX() && $this->isPost()) {
            $this->session->set("nested-count", $this->session->get("nested-count", 0) + 1);
        }
        $this->count = $this->session->get("nested-count", 0);

        LucideIcons::register($this, "icons");
    }

    public function renderAJAX($options = null)
    { ?>

        <div
            class="min-w-[480px] bg-white mb-3 p-0 shadow-xl ring-1 ring-slate-900/5 sm:mx-auto sm:max-w-lg sm:rounded-lg sm:px-10">
            <div class="p-6 flex flex-row items-center justify-between space-y-0 pb-2">
                <h3 class="tracking-tight text-sm font-medium">Nested AJAX component</h3>
                <?php $this->renderComponent("icons", "cpu"); ?>
            </div>
            <div class="p-6 pt-0">
                <div class="text-2xl font-bold">Counter: <?php echo $this->count; ?></div>
                <p class="text-xs text-muted-foreground">This component is using ajax.</p>
            </div>
        </div>

    <?php }

    public function renderContainer($options = null)
    {
        echo "<div id=\"" . $this->id . "\">";
        $this->renderAJAX();
        echo "</div>";

        $this->view->jsSource(function () { ?>
            <script>

                setInterval(function () {
                    <?php $this->renderJSRequestPost(); ?>
                }, 2000);

            </script>
        <?php }, "load");
    }

}
