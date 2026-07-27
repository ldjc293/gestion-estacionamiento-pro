<?php
$dir = __DIR__ . '/app';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        
        $original = $content;
        
        // REVERSE THE DAMAGE
        // 1. Revert upCAST(...)
        $content = str_replace('upCAST(array $data AS DATE)', 'update(array $data)', $content);
        $content = str_replace('upCAST(array $data AS DATE): bool', 'update(array $data): bool', $content);
        
        // 2. Revert any accidental function renaming like update( to upCAST(
        // Actually, let's just restore all CAST(... AS DATE) to DATE(...) safely first.
        $content = preg_replace('/CAST\((.*?) AS DATE\)/', 'DATE($1)', $content);
        
        // 3. Revert TO_CHAR back to DATE_FORMAT for now (we'll fix SQL strings properly later)
        $content = preg_replace('/TO_CHAR\((.*?), \'(.*?)\'\)/', 'DATE_FORMAT($1, \'$2\')', $content);
        
        // 4. Specific known broken methods
        $content = str_replace('upDATE', 'update', $content); // if any
        $content = str_replace('valiDATE', 'validate', $content); // if any
        $content = str_replace('upCAST', 'update', $content); // if any
        $content = str_replace('upDATE_FORMAT', 'update_format', $content); // if any

        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Restored: $path\n";
        }
    }
}
echo "System restore complete.\n";
