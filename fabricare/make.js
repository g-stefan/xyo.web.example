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

exitIf(Shell.system("7zr x vendor/xyo.web-"+Solution.vendor["xyo.web"]+".7z -ooutput"));
exitIf(!Shell.copyDirRecursively("source", "output"));

// ---

Fabricare.include("make.tailwind");

