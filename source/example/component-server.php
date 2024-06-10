<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

defined("XYO_WEB") or die("Forbidden");

require_once ("./site/web.php");
require_once ("./site/library/lucide-icons.php");
use \XYO\Library\LucideIcons;

class ComponentServer extends \XYO\Web\Component
{

    public function init()
    {

        if ($this->isPost()) {
            $this->sessionSet("count", $this->sessionGet("count", 0) + 1);
        }

        LucideIcons::register($this, "icons");
    }

    public function render(&$options = null)
    { ?>

        <div class="min-w-[480px] bg-white rounded-xl border bg-card text-card-foreground shadow mb-3">
            <div class="p-6 flex flex-row items-center justify-between space-y-0 pb-2">
                <h3 class="tracking-tight text-sm font-medium">Server component</h3>
                <?php $this->renderComponent("icons", "server"); ?>
            </div>
            <div class="p-6 pt-0">
                <div class="text-2xl font-bold">Counter: <?php echo $this->sessionGet("count", 0); ?></div>
                <p class="text-xs text-muted-foreground">This component is rendered on page every time.</p>
            </div>
            <div class="flex justify-center items-center p-4">
                <form name="example" method="POST">
                    <button type="submit"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Submit</button>
                    <?php $this->view->renderTokenAsFormInput() ?>
                </form>
            </div>
        </div>

    <?php }

}
