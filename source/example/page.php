<?php

// XYO.Web Example
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

defined("XYO_WEB") or die("Forbidden");

require_once(XYO_WEB_PATH . "_library/xyo-web-example-css.php");
require_once(XYO_WEB_PATH . "_site/xyo/lucide-icons/lucide-icons.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/library/xyo-web-logo.php");
require_once(XYO_WEB_PATH . "_site/xyo/overlay-scrollbars/overlayscrollbars.php");
require_once(XYO_WEB_PATH . "_site/datasource/query-user.php");
require_once(XYO_WEB_PATH . "example/component-server.php");
require_once(XYO_WEB_PATH . "example/component-ajax.php");
require_once(XYO_WEB_PATH . "example/component-ajax-db.php");
require_once(XYO_WEB_PATH . "example/component-form.php");
use \XYO\LucideIcons\LucideIcons;
use \XYO\Web\Library\XYOWebLogo;
use \XYO\OverlayScrollbars\OverlayScrollbars;
use \XYO\Library\XYOWebExampleCSS;

class Test extends \XYO\Web\Page
{

    public function init($options = null)
    {
        $this->setTitle("Web");

        XYOWebExampleCSS::register($this);
        LucideIcons::register($this, "icons");
        XYOWebLogo::register($this, "logo");
        OverlayScrollbars::register($this, "overlayscrollbars");
        ComponentServer::register($this, "server");
        ComponentAjax::register($this, "ajax");
        ComponentAjaxDB::register($this, "ajax-db");
        ComponentForm::register($this, "ajax-form");

        $tableCounter = $this->getDataSource(TableCounter::class);
        $tableCounter->createStorage();

        $tableUser = $this->getDataSource(TableUser::class);
        $tableUser->createStorage();

        /*

        $tableUser->clear();
        $tableUser->id=1;
        if (!$tableUser->load(0, 1)) {
            $tableUser->clear();
            $tableUser->counter_id=1;
            $tableUser->name = "Hello";
            $tableUser->save();
        }

        $queryUser=$this->getDataSource(QueryUser::class);
        for($queryUser->load();$queryUser->loadIsValid();$queryUser->loadNext()){
            echo "Id: ".$queryUser->id."<br>";
            echo "CId: ".$queryUser->counter_id."<br>";
            echo "Name: ".$queryUser->name."<br>";
            echo "Count: ".$queryUser->count."<br>";
        }

        */
    }

    public function render($options = null)
    { ?>
        <div class="relative flex min-h-screen flex-col justify-center overflow-hidden bg-slate-300 py-6 sm:py-12">
            <div class="relative y-900/5 sm:mx-auto sm:max-w-lg sm:px-10">

                <div
                    class="min-w-[480px] bg-white mb-3 p-3 shadow-xl ring-1 ring-slate-900/5 sm:mx-auto sm:max-w-lg sm:rounded-lg sm:px-10">
                    <div class="flex flex-row items-center justify-center">
                        <?php $this->renderComponent("logo"); ?>
                    </div>
                </div>

                <?php $this->renderComponent("server"); ?>
                <?php $this->renderComponent("ajax"); ?>
                <?php $this->renderComponent("ajax-db"); ?>
                <?php $this->renderComponent("ajax-form"); ?>


            </div>

        </div>

    <?php }

}

return Test::class;
