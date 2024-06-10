<?php
use XYO\Library\XUIScrollbars;

// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

defined("XYO_WEB") or die("Forbidden");

require_once ("./site/web.php");
require_once ("./site/library/lucide-icons.php");
require_once ("./site/library/xyo-web-logo.php");
require_once ("./site/library/overlayscrollbars.php");
require_once ("./example/component-server.php");
require_once ("./example/component-ajax.php");
require_once ("./example/component-ajax-db.php");
require_once ("./example/query-user.php");
use \XYO\Library\LucideIcons;
use \XYO\Library\XYOWebLogo;
use \XYO\Library\OverlayScrollbars;

class Test extends \XYO\Web\Page
{

    public function init()
    {
        $this->setTitle("Web");

        LucideIcons::register($this, "icons");
        XYOWebLogo::register($this, "logo");
        OverlayScrollbars::register($this, "overlayscrollbars");
        ComponentServer::register($this, "server");
        ComponentAjax::register($this, "ajax");
        ComponentAjaxDB::register($this, "ajax-db");

        $tableCounter = new TableCounter();
        $tableCounter->_connector->createStorage();

        $tableUser = new TableUser();
        $tableUser->_connector->createStorage();

        /*

        $tableUser=new TableUser();        
        $tableUser->id=1;
        if (!$tableUser->load(0, 1)) {
            $tableUser->clear();
            $tableUser->counter_id=1;
            $tableUser->name = "Hello";
            $tableUser->save();
        }

        $queryUser=new QueryUser();
        for($queryUser->load();$queryUser->loadValid();$queryUser->loadNext()){
            echo "Id: ".$queryUser->id."<br>";
            echo "CId: ".$queryUser->counter_id."<br>";
            echo "Name: ".$queryUser->name."<br>";
            echo "Count: ".$queryUser->count."<br>";
        }
        
        */
    }

    public function render(&$options = null)
    { ?>
        <div class="relative flex min-h-screen flex-col justify-center overflow-hidden bg-stone-300 py-6 sm:py-12">
            <div class="relative y-900/5 sm:mx-auto sm:max-w-lg sm:px-10">

                <div class="min-w-[480px] p-6 bg-white rounded-xl border bg-card text-card-foreground shadow mb-3">
                    <div class="flex flex-row items-center justify-center pb-2">
                        <?php $this->renderComponent("logo"); ?>
                    </div>
                </div>

                <?php $this->renderComponent("server"); ?>
                <?php $this->renderComponent("ajax"); ?>
                <?php $this->renderComponent("ajax-db"); ?>


            </div>

        </div>

    <?php }

}

return Test::class;
