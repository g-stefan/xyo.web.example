<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

defined("XYO_WEB") or die("Forbidden");

require_once ("./_site/web.php");
require_once ("./_site/library/xyo-web-example-css.php");
require_once ("./_site/library/lucide-icons.php");
require_once ("./_site/library/xyo-web-logo.php");
require_once ("./_site/library/overlayscrollbars.php");
require_once ("./example/component-server.php");
require_once ("./example/component-ajax.php");
require_once ("./example/component-ajax-db.php");
require_once ("./example/query-user.php");
use \XYO\Library\LucideIcons;
use \XYO\Library\XYOWebLogo;
use \XYO\Library\OverlayScrollbars;
use \XYO\Library\XYOWebExampleCSS;

class Test extends \XYO\Web\Page
{

    public function init()
    {
        $this->setTitle("Web");

        XYOWebExampleCSS::register($this);
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
        <div class="relative flex min-h-screen flex-col justify-center overflow-hidden bg-slate-300 py-6 sm:py-12">
            <div class="relative y-900/5 sm:mx-auto sm:max-w-lg sm:px-10">

                <div class="min-w-[480px] bg-white mb-3 p-3 shadow-xl ring-1 ring-slate-900/5 sm:mx-auto sm:max-w-lg sm:rounded-lg sm:px-10">
                    <div class="flex flex-row items-center justify-center">
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
