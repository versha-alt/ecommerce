<?php
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator('apps/backend/vendor',FilesystemIterator::SKIP_DOTS));
foreach($iterator as $file){if($file->getExtension()==='php'&&@file_get_contents($file->getPathname())===false)echo $file->getPathname().PHP_EOL;}
