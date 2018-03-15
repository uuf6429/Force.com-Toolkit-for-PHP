<?php

$file = __DIR__ . '/../.git/hooks/pre-commit';

file_put_contents(
    $file,
    <<<'SH'
#!/bin/sh
echo "Running pre-commit hook..."
[ -f contrib/pre-commit.php ] && php contrib/pre-commit.php
exit 0
SH
);

chmod($file, 0755);
