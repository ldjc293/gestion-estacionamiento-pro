<?php
function searchDir($dir) {
    $it = new RecursiveDirectoryIterator($dir);
    foreach (new RecursiveIteratorIterator($it) as $file) {
        if ($file->isFile() && $file->getExtension() == 'php') {
            $content = file_get_contents($file->getPathname());
            if (strpos($content, 'a.bloque') !== false && strpos($content, 'GROUP BY') !== false) {
                if ($file->getFilename() == 'find_grouping.php') continue;
                
                $lines = explode("\n", $content);
                foreach ($lines as $i => $line) {
                    if (strpos($line, 'a.bloque') !== false || strpos($line, 'GROUP BY') !== false) {
                        echo $file->getPathname() . " (Línea " . ($i + 1) . "): " . trim($line) . "\n";
                    }
                }
            }
        }
    }
}
searchDir(__DIR__ . '/app');
