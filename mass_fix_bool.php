<?php
$dir = __DIR__ . '/app';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        
        $original = $content;
        
        // Replace activo = 1 with activo = TRUE (with varying spaces)
        $content = preg_replace('/activo\s*=\s*1\b/', 'activo = TRUE', $content);
        
        // Replace activo = 0 with activo = FALSE
        $content = preg_replace('/activo\s*=\s*0\b/', 'activo = FALSE', $content);

        // Replace au.activo = 1 with au.activo = TRUE
        $content = preg_replace('/au\.activo\s*=\s*1\b/', 'au.activo = TRUE', $content);
        $content = preg_replace('/au\.activo\s*=\s*0\b/', 'au.activo = FALSE', $content);

        // Replace u.activo = 1 with u.activo = TRUE
        $content = preg_replace('/u\.activo\s*=\s*1\b/', 'u.activo = TRUE', $content);
        $content = preg_replace('/u\.activo\s*=\s*0\b/', 'u.activo = FALSE', $content);

        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Fixed: $path\n";
        }
    }
}
echo "Replacement complete.\n";
