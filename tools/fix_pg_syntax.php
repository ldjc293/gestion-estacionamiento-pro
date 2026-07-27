<?php
$dir = __DIR__ . '/app';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        
        $original = $content;
        
        // 1. DATE_FORMAT -> TO_CHAR
        $content = preg_replace("/DATE_FORMAT\(([^,]+),\s*'%d\/%m\/%Y %H:%i'\)/i", "TO_CHAR($1, 'DD/MM/YYYY HH24:MI')", $content);
        $content = preg_replace("/DATE_FORMAT\(([^,]+),\s*'%d\/%m\/%Y'\)/i", "TO_CHAR($1, 'DD/MM/YYYY')", $content);

        // 2. MySQL DATE(column) -> CAST(column AS DATE) or column::date
        $content = preg_replace("/DATE\s*\(([^,)]+)\)/i", "CAST($1 AS DATE)", $content);

        // 3. MySQL CONCAT with multiple args (Postgres CONCAT is fine, but sometimes people use it differently).
        // Postgres: CONCAT(a, b, c) is fine. 

        // 4. Hardcoded booleans in specific project lines
        $content = str_replace('VALUES (?, ?, NOW(), 1, ?, NOW())', 'VALUES (?, ?, NOW(), TRUE, ?, NOW())', $content);

        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "Fixed: $path\n";
        }
    }
}
echo "PostgreSQL compatibility fix complete.\n";
