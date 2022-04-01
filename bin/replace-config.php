<?php

$config = include __DIR__ . '/app/etc/env.php';


$db = new DBConnection($config);
$domainPatches = [
    'web/unsecure/base_url',
    'web/secure/base_url',
    'web/unsecure/base_link_url',
    'web/secure/base_link_url',
    'web/unsecure/base_media_url',
    'web/secure/base_media_url',
    'web/unsecure/base_static_url',
    'web/secure/base_static_url',
    'web/cookie/cookie_domain',
    'admin/url/custom',
];
$patchWhere = implode(',', array_map(static function($value) {return "'{$value}'";}, $domainPatches));

$configs = $db->getAllRows("SELECT * FROM core_config_data WHERE path IN({$patchWhere})");


//$stores = $db->getAllRows("SELECT * FROM store", 'store_id');
//$websites = $db->getAllRows("SELECT * FROM store_website", 'website_id');

$settingsObject = new DotEnv(__DIR__ . '/.env');
$settings = $settingsObject->get();
$replaceDomain = $settings['TRAEFIK_DOMAIN'];
$currentReplace = "https://{$settings['TRAEFIK_SUBDOMAIN']}.{$replaceDomain}/";

$scopes = [];

$uniqueDomains = [];
$updates = [];
foreach ($configs as $config) {
    $domain = getDomain($config['value']);
    if (!$domain || strpos($domain, '{{') === 0) {
        continue;
    }

    if (isset($uniqueDomains[$domain]) && $config['scope'] !== 'default') {
        continue;
    }


    if ($config['scope'] === 'default') {
        $newDomain = 'app.' . $replaceDomain;
    } else {

        $newDomain = str_replace('.', '-', $domain) . '.' . $replaceDomain;
    }

    $uniqueDomains[$domain] = $newDomain;
    //if (isset($uniqueDomains))
//    switch ($config['scope']) {
//        case 'default':
//            $scopes['default'] = $domain;
//            break;
//        case 'websites':
//            $scopes['websites'][$config['scope_id']] = $domain;
//            break;
//        case 'stores':
//            $scopes['stores'][$config['scope_id']] = $domain;
//            break;
//    }
}
$db->query("UPDATE core_config_data SET value = 'admin' WHERE path = 'admin/url/custom_path'");



echo "Backup configs" . PHP_EOL;
$backupPatchWhere = implode(',', array_map(static function($value) {return "'{$value}_backup'";}, $domainPatches));;
$db->query("DELETE FROM core_config_data WHERE path IN({$backupPatchWhere})");
$db->query("INSERT INTO core_config_data
SELECT NULL, scope, scope_id, CONCAT(path, '_backup'), value, updated_at FROM core_config_data WHERE path IN({$patchWhere})");


$magentoVarsContent = "";
if (is_file(__DIR__ . '/magento-vars.php')) {
    echo "Found magento-vars.php file" . PHP_EOL;
    $magentoVarsContent = file_get_contents(__DIR__ . '/magento-vars.php');
}
echo "Domains found and replaced" . PHP_EOL;

printTable($uniqueDomains, ["Domain", "Replacement"]);
foreach ($uniqueDomains as $domain => $replacement) {
    $db->query("UPDATE core_config_data SET value = REPLACE(value, '{$domain}', '{$replacement}') WHERE path IN({$patchWhere})");
    $magentoVarsContent = str_replace($domain, $replacement, $magentoVarsContent);
}

if ($magentoVarsContent) {
    echo "Saved magento-vars-local.php file for multiple domains" . PHP_EOL;
    echo "Please include magento-vars-local.php into pub/index.php" . PHP_EOL;
    file_put_contents(__DIR__ . '/magento-vars-local.php', $magentoVarsContent);
}


function getDomain($string) {

    $domain = str_replace(['http://', 'https://'], '', $string);

    $domain = explode('/', $domain);

    return $domain[0];
}

function printTable($keyValue, $headers) {
    $maxLength = 0;
    foreach ($keyValue as $key => $value) {
        $keyLen = strlen($key);
        $valLen = strlen($value);
        $maxLength = max($maxLength, $keyLen, $valLen);
    }

    $alignment = $maxLength+4;
    echo str_pad("", $alignment, "-") . '-' , str_pad("", $alignment, "-") . PHP_EOL;
    echo "|" . str_pad($headers[0], $alignment, " ", STR_PAD_BOTH) . '|' . str_pad($headers[1], $alignment, " ", STR_PAD_BOTH) . '|' . PHP_EOL;

    echo str_pad("", $alignment, "-") . '-' , str_pad("", $alignment, "-") . PHP_EOL;
    foreach ($keyValue as $key => $value) {
        echo '|' . str_pad("{$key}", $alignment, " ", STR_PAD_BOTH) . "|".str_pad("{$value}", $alignment, " ", STR_PAD_BOTH) . '|'. PHP_EOL;
    }
    echo str_pad("", $alignment, "-") . '-' , str_pad("", $alignment, "-") . PHP_EOL;
}

class DBConnection
{
    private $pdo;

    public function __construct(array $config)
    {
        $dbConfig = $config['db']['connection']['default'];
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function query($sql) {
//        var_dump($sql);
//        return ;
        return $this->pdo->query($sql);
    }


    public function getAllRows($sql, $indexColumn = null)
    {
        $stmt = $this->pdo->query($sql);
        if ($indexColumn === null) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = $row[$indexColumn];
            $rows[$id] = $row;
        }

        return $rows;
    }
}

class DotEnv
{
    /**
     * The directory where the .env file can be located.
     *
     * @var string
     */
    protected $path;


    public function __construct($path)
    {
        if(!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('%s does not exist', $path));
        }
        $this->path = $path;
    }

    public function get()
    {
        if (!is_readable($this->path)) {
            throw new \RuntimeException(sprintf('%s file is not readable', $this->path));
        }

        $envVariables = [];
        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {

            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            $envVariables[$name] = $value;
        }

        return $envVariables;
    }
}

