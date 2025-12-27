<?php

$file = 'd:\Shahriar Him\Hometex\hometex_backend\app\Services\ProductService.php';
$content = file_get_contents($file);

// Fix the ambiguous column issue in tags relationship
$pattern = "/(\$with\['tags'\] = function\(\$q\) \{\s+\$q->select\()'id', 'name', 'slug'(\);)/";
$replacement = "$1'product_tags.id', 'product_tags.name', 'product_tags.slug'$2";

$content = preg_replace($pattern, $replacement, $content);

file_put_contents($file, $content);

echo "Fixed ambiguous column issue in ProductService.php\n";
echo "Changed: select('id', 'name', 'slug')\n";
echo "To: select('product_tags.id', 'product_tags.name', 'product_tags.slug')\n";
