<?php

declare(strict_types=1);

$filePath = dirname(__DIR__) . '/dump.sql';
$target = dirname(__DIR__) . '/dump_fixed.sql';
$leftTable = dirname(__DIR__) . '/dump_ignored.sql';

$action = $argv[1] ?? 'analyze';
//$action = 'fix';


switch ($action) {
    case 'analyze':
        echo "STarting analyzing dump \r\n";
        $data = analyzeDumpsFile($filePath);
        echo "TOTAL SIZE: " . humanFileSize($data['total_size']) . "\r\n";
        echo "INSERT SIZE: " . humanFileSize($data['insert_size']) . "\r\n";


        foreach ($data['table_sizes'] as $table => $size) {
            echo '  ' . str_pad($table, 32, ' ', STR_PAD_RIGHT) . humanFileSize($size) . "\r\n";
        }
        break;

    case 'fix':
        processDumps($filePath, $target, ['catalog_data_exporter_products'], $leftTable);
        break;
}




function processDumps($inFile, $outFilePath, $ignoreTables, $ignoreFilePath = null) {

    $in = getFileIterator($inFile);

    $target = fopen($outFilePath, 'w+');

    $ignoreFile = null;
    if ($ignoreFilePath) {

        $ignoreFile = fopen($ignoreFilePath, 'w+');
    }


    $placeholders = [];

    foreach ($ignoreTables as $table) {
        $placeholders[] = "INSERT INTO `{$table}`";
        $placeholders[] = "INSERT IGNORE INTO `{$table}`";
    }

    foreach ($in as $line) {
        $skip = false;
        if (strpos($line, 'INSERT ') === 0) {
            foreach ($placeholders as $placeholder) {
                if (strpos($line, $placeholder) !== false) {
                    $skip = true;
                    break;
                }
            }
        }


        if ($skip) {
            if ($ignoreFile) {
                fwrite($ignoreFile, $line . PHP_EOL);
            }
            continue;
        }


        fwrite($target, $line . PHP_EOL);
    }

    fclose($target);
}
function analyzeDumpsFile($filePath) {


    $size = 0;
    $lines = 0;
    $statements = [];

    $rememberMode = false;
    $statement = '';
    $insertMode = false;

    $insertSize = 0;

    $tableSizes = [];

    foreach (getFileIterator($filePath) as $line) {
        $lines++;
        $lineSize = strlen($line);
        $size += $lineSize;

        $skip = false;

        if (empty(trim($line))) {
            continue;
        }

        //foreach (['/*', '--'] as )
        if (strpos($line, '/*') === 0 ||
            strpos($line, '--') === 0
        ) {
            continue;
        }

        if (strpos($line, 'LOCK TABLES') === 0 ||
            strpos($line, 'UNLOCK TABLES') === 0
        ) {
            continue;
        }

        if (strpos($line, 'DROP TABLE') === 0) {
            $statements[] = $line;
            continue;
        }

        if (strpos($line, 'CREATE TABLE') === 0) {
            $rememberMode = true;
            $statement .= $line;
            continue;
        }

        // end of create table
        if (strpos($line, ';') !== false && strpos($line, 'ENGINE=')) {
            $rememberMode = false;
            $statement .= $line;
            $statements[] = $statement;
            $statement = '';
            continue;
        }

        if ($rememberMode) {
            $statement .= $line;
            continue;
        }


        if (
            strpos($line, 'INSERT INTO ') === 0 ||
            strpos($line, 'INSERT IGNORE INTO ') === 0

        ) {
            // INSERT
            $insertSize += $lineSize;

            $table = substr($line, 0, 200);
            if (preg_match('/INSERT (IGNORE )?INTO `(.*)` /isU',$table, $matches)) {

                $table = $matches[2];

                $tableSizes[$table] = $tableSizes[$table] ?? 0;
                $tableSizes[$table] += $lineSize;
            }

            else {
                var_dump($table);
            }

            continue;
        }

    }

    arsort($tableSizes, SORT_NUMERIC);

    return [
        'total_size' => $size,
        'insert_size' => $insertSize,
        'table_sizes' => $tableSizes
    ];
}



function getFileIterator(string $filePath): \Generator
{
    $file = fopen($filePath, 'r');

    while (($line = fgets($file)) !== false) {
        yield $line;
    }

    fclose($file);
}
class FileIterator {
    private $filePath;

    private  function __construct($filePath) {
        $this->filePath = $filePath;
    }

    public function get(): \Generator
    {
        $file = fopen($this->filePath);

        while (($line = fgets($file)) !== false) {
            yield $line;
        }

        fclose($file);
    }
}



function humanFileSize($size, $precision = 1, $show = "")
{
    $b = $size;
    $kb = round($size / 1024, $precision);
    $mb = round($kb / 1024, $precision);
    $gb = round($mb / 1024, $precision);

    if($kb == 0 || $show == "B") {
        return $b . " bytes";
    } else if($mb == 0 || $show == "KB") {
        return $kb . "KB";
    } else if($gb == 0 || $show == "MB") {
        return $mb . "MB";
    } else {
        return $gb . "GB";
    }
}
