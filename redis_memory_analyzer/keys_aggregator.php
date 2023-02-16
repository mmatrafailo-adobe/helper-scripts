<?php

declare(strict_types=1);


$csvFilePath = $argv[1] ?? __DIR__ .'/memory.csv';
$handle = fopen($csvFilePath, 'r');
$header = fgetcsv($handle, 1000, ",");
$aggregatedData = [];
while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    $db = $data[0];
    $key = 'database' . $db . '_' . extractKey($data[2]);

    $parts = explode('_', $key);
    aggregateData($aggregatedData, $parts, $data[3]);
}
fclose($handle);

$aggregatedData = filterRecursive($aggregatedData);

$aggregatedData = toFlatArray($aggregatedData);


$data = [];

foreach ($aggregatedData as $key => $item) {
    $data[] = [
        'type' => 'prefix',
        'name' => $key,
        'count_keys' => $item['count'],
        'count_keys_human' => number_format($item['count']),
        'size' => $item['sum'],
        'size_human' => humanFileSize($item['sum']),
        // @TODO
//        'last_expiration' => $prefixData['expiry']->format('Y-m-d H:i:s'),
//        'min_expiration' => $prefixData['min_expiry']->format('Y-m-d H:i:s')
    ];
}


$htmlStatFile = __DIR__ . '/redis_statistics.html';

$html = '<html>
<head>
    <script
            src="https://code.jquery.com/jquery-1.12.4.min.js"
            integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ="
            crossorigin="anonymous"></script>
    <link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid.min.css" />
    <link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid-theme.min.css" />

    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid.min.js"></script>
</head>
<body>
<div id="jsGrid"></div>

<script>
    var clients = '.json_encode($data).';


    $("#jsGrid").jsGrid({
        width: "100%",
        height: "100%",

        inserting: false,
        editing: false,
        sorting: true,
        paging: false,

        data: clients,






        fields: [
            { name: "type", type: "text", width: 150, validate: "required" },
            { name: "name", type: "text", width: 150, validate: "required" },
            { name: "count_keys", type: "number", width: 50 },
            { name: "count_keys_human", type: "text", width: 150 },
            { name: "size", type: "number", width: 50 },
            { name: "size_human", type: "text", width: 150 }
//            ,
//            { name: "last_expiration", type: "text", width: 150 },
//            { name: "min_expiration", type: "text", width: 150 }
        ]
    });
</script>
</body>
</html>';

file_put_contents($htmlStatFile, $html);

//file_put_contents('result.json', json_encode($aggregatedData, JSON_PRETTY_PRINT));



function toFlatArray($array){
    $results = [];
    foreach ($array as $key => $item) {
        if ($item['children']) {
            $children = toFlatArray($item['children']);
            foreach ($children as $childKey => $value) {
                $results[$key . '_' . $childKey] = $value;
            }

            continue;
        }

        $results[$key] = $item;
    }

    if (!$results) {
        return $array;
    }

    return $results;
}
function filterRecursive($array, $parentCount = 1) {
    foreach ($array as $key => $item) {

        if (!isset($item['count'])) {
            var_dump('NO COUNT ' .  $key);
        }

        if (!isset($item['children'])) {

            var_dump('NO CHILDREN ' .  $key);
        }
        $percent = $item['count'] / $parentCount * 100;

        // @TODO calculate percent
        if ($percent < 5) {
            unset($array[$key]);
            continue;
        }
        $array[$key]['children'] = filterRecursive($item['children'], $item['count']);
    }

    return $array;
}

function aggregateData(&$array, $pathInArray, $sumValue) {

    $key = array_shift($pathInArray);
    if (!isset($array[$key])) {
        $array[$key] = [
            'count' => 0,
            'sum' => 0,
            'children' => []
        ];
    }

    $array[$key]['count']++;
    $array[$key]['sum'] += $sumValue;

    if (count($pathInArray) > 0) {
        aggregateData($array[$key]['children'], $pathInArray, $sumValue);
    }
}

function extractKey($key) {
    $key = str_replace(':hash', '', $key);
    $parts = explode(':', $key);

    if (count($parts) > 1) {
        $prefix = end($parts);
    } else {
        $prefix = $parts[0];
    }

    return $prefix;
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