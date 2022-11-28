<?php
$csvFilePath = __DIR__ .'/memory.csv';

$handle = fopen($csvFilePath, 'r');

$prefixPartsCount = 2;

$now = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
//var_dump($now->sub(new DateInterval('P1M')));die;
$template = [
    'name' => null,
    'count' => 0,
    'size' => 0,
    'expiry' => $now->sub(new DateInterval('P1M')),
];
$usage = [
    'session' => $template
];
$header = fgetcsv($handle, 1000, ",");

$idPrefixes = ['4da'];

$singleKeys = ['BLOCK', 'SERVICEINTERFACEMETHODSMAP', 'APP'];

$sessionDb = null;

$expirationWarning = [];



$maxExpiry = $now;
$maxExpiryKey = '';
$oneWeek = $now->add(new \DateInterval('P1W'));
//var_dump($oneWeek);die;
while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
    $db = $data[0];
    $key = extractKey($data[2]);
    $expiry = new DateTimeImmutable($data[7], new \DateTimeZone('UTC'));

    if ($maxExpiry < $expiry) {
        $maxExpiry = $expiry;
        $maxExpiryKey = $key;
    }

    if ($expiry > $oneWeek) {
        $expirationWarning[$key] = $expiry->format('Y-m-d');
    }
    if (strpos($key, 'sess_') !== false) {
        if ($sessionDb !== null && $sessionDb !== $db) {
            writeln("ERRROR: found multiple DBs with session data. Key: {$key}, db: {$db}");
        } else {
            $sessionDb = $db;
        }
    } elseif ($sessionDb !== null && $sessionDb === $db) {
        writeln("ERRROR: key is not session, but in session db! Key: {$key}, db: {$db}");
    }

    foreach ($idPrefixes as $idPrefix) {
        if (strpos($key, $idPrefix. '_') === 0) {
            $key = str_replace($idPrefix . '_', '', $key);
            break;
        }
    }

    if ($db === $sessionDb) {
        $usage['session']['name'] = $db;
        $usage['session']['count']++;
        $usage['session']['size'] += $data[3];
        if ($usage['session']['expiry'] < $expiry) {
            $usage['session']['expiry'] = $expiry;
        }

        continue;
    }

    $dbKey = 'db' . $db;

    if (!isset($usage[$dbKey])) {
        $usage[$dbKey] = $template;
        $usage[$dbKey]['name'] = $db;
        $usage[$dbKey]['prefixes'] = [];
    }

    $usage[$dbKey]['count']++;
    $usage[$dbKey]['size'] += $data[3];

    if ($usage[$dbKey]['expiry'] < $expiry) {
        $usage[$dbKey]['expiry'] = $expiry;
    }

    $currentPrefixPartsCount = $prefixPartsCount;

    $firstPrefix = getPrefix($key, 1);

    if (in_array($firstPrefix, $singleKeys)) {
        $currentPrefixPartsCount = 1;
    }

    $prefix = getPrefix($key, $currentPrefixPartsCount);


    if (!isset($usage[$dbKey]['prefixes'][$prefix])) {
        $usage[$dbKey]['prefixes'][$prefix] = $template;
        $usage[$dbKey]['prefixes'][$prefix]['name'] = $prefix;
    }

    $usage[$dbKey]['prefixes'][$prefix]['count']++;
    $usage[$dbKey]['prefixes'][$prefix]['size'] += $data[3];
    if ($usage[$dbKey]['prefixes'][$prefix]['expiry'] < $expiry) {
        $usage[$dbKey]['prefixes'][$prefix]['expiry'] = $expiry;
    }
}

fclose($handle);


echo "MAX EXPIRY DATA: {$maxExpiry->format('Y-m-d H:i:s')} key: {$maxExpiryKey}" . PHP_EOL;
echo PHP_EOL;
//var_dump($maxExpiry);die;


$data = [];
foreach ($usage as $dbKey => &$dbData) {
    echo "DATABASE ";
    printStatistics($dbData);

    $data[] = [
        'type' => 'database',
        'name' => $dbData['name'],
        'count_keys' => $dbData['count'],
        'count_keys_human' => number_format($dbData['count']),
        'size' => $dbData['size'],
        'size_human' => humanFileSize($dbData['size']),
        'last_expiration' => $dbData['expiry']->format('Y-m-d H:i:s')
    ];
    if ($dbKey === 'session') {
        continue;
    }
    uasort($dbData['prefixes'], static function($item1, $item2) {
        return $item2['size'] <=> $item1['size'];
    });


    foreach ($dbData['prefixes'] as $prefixData) {
        printStatistics($prefixData);


        $data[] = [
            'type' => 'prefix',
            'name' => $prefixData['name'],
            'count_keys' => $prefixData['count'],
            'count_keys_human' => number_format($prefixData['count']),
            'size' => $prefixData['size'],
            'size_human' => humanFileSize($prefixData['size']),
            'last_expiration' => $prefixData['expiry']->format('Y-m-d H:i:s')
        ];
    }
    echo PHP_EOL;
    echo PHP_EOL;
}

$htmlStatFile = __DIR__ . '/stats.html';

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
            { name: "size_human", type: "text", width: 150 },
            { name: "last_expiration", type: "text", width: 150 }
        ]
    });
</script>
</body>
</html>';

file_put_contents($htmlStatFile, $html);



//var_dump($usage);


function printStatistics($stat) {
    echo $stat['name'] . ' keys count: ' . number_format($stat['count']) . ' keys size: ' . humanFileSize($stat['size']) . ' MAX expiration: ' . $stat['expiry']->format('Y-m-d H:i:s');
    echo PHP_EOL;
}


function extractKey($key) {
    $parts = explode(':', $key);

    if (count($parts) > 1) {
        $prefix = end($parts);
    } else {
        $prefix = $parts[0];
    }

    return $prefix;
}
function writeln($line) {
    echo $line . PHP_EOL;
}
function getPrefix($prefix, $prefixPartsCount) {
    $parts = explode(':', $prefix);

    if (count($parts) > 1) {
        $prefix = end($parts);
    } else {
        $prefix = $parts[0];
    }
    $parts = explode('_', $prefix);
    $countParts = count($parts);
    if ($countParts < $prefixPartsCount) {
        return implode('_', $parts);
    }
    return implode('_', array_slice($parts, 0, $prefixPartsCount));
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