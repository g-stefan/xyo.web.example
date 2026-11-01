// Created by Grigore Stefan <g_stefan@yahoo.com>
// Public domain (Unlicense) <http://unlicense.org>
// SPDX-FileCopyrightText: 2023-2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Unlicense

Fabricare.include("vendor");

// ---

messageAction("make [" + Project.name + "]");

Shell.removeDirRecursivelyForce("output");
Shell.mkdirRecursivelyIfNotExists("output");
Shell.mkdirRecursivelyIfNotExists("temp");

// ---

exitIf(Shell.system("7z x vendor/xyo.web.v"+Solution.vendor["xyo.web"]+".zip -ooutput"));
exitIf(!Shell.copyDirRecursively("source", "output"));

// ---

Fabricare.include("make.tailwindcss");

