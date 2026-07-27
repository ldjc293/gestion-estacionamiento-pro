<?php
function testConnection($host, $port, $user, $pass) {
    echo "Testing $host:$port with user $user... ";
    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=postgres", $user, $pass, [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "SUCCESS!\n";
        return true;
    } catch (PDOException $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        return false;
    }
}

$ref = 'feoijalccdmdcpufjuda';
$user = "postgres." . $ref;
$pass = "Impulso$$29";

testConnection("aws-0-us-east-1.pooler.supabase.com", "6543", $user, $pass);
testConnection("aws-0-us-east-2.pooler.supabase.com", "6543", $user, $pass);
testConnection("aws-0-sa-east-1.pooler.supabase.com", "6543", $user, $pass);
