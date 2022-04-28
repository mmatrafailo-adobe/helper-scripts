<?php

//var_dump(getDomainsFromEnv(__DIR__));
//echo PHP_EOL;
//die;
$command = $argv[1] ?? null;

if ($command === 'env') {
    replaceConfig(__DIR__);
} elseif($command === 'db') {
    replaceDb(__DIR__);
}

if (!$command) {
    die("Command not specified. For replacing app/etc/env.php run php replace-scripts.php env");
}

function getDomainsFromEnv($path) {
    $config = include $path . '/app/etc/env.php';

    $configPatcher = new ConfigPatcher($config);

    if (!$configPatcher->exists('system')) {
        return;
    }

    $domains = [];
    if (!empty($config['system']['default']['web']['unsecure']['base_url'])) {
        $domains[] = getDomain($config['system']['default']['web']['unsecure']['base_url']);
    }

    if (!empty($config['system']['default']['web']['secure']['base_url'])) {
        $domains[] = getDomain($config['system']['default']['web']['secure']['base_url']);
    }

    foreach ($config['system'] as $scope => $scopeConfig) {
        if ($scope === 'default') {
            continue;
        }

        foreach ($scopeConfig as $scopeCode => $scopeValues) {
            if (!empty($scopeValues['web']['unsecure']['base_url'])) {
                $domains[] = getDomain($scopeValues['web']['unsecure']['base_url']);
            }

            if (!empty($scopeValues['web']['secure']['base_url'])) {
                $domains[] = getDomain($scopeValues['web']['secure']['base_url']);
            }
        }

    }

    return array_unique($domains);
}

function getConfigsFromEnv($path) {

    $config = include $path . '/app/etc/env.php';

    if (empty($config['system'])) {
        return [];
    }


}

function replaceDb($path) {

    echo "Replacing db values..." . PHP_EOL;

    $config = include $path . '/app/etc/env.php';


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

    $envContent = file_get_contents($path . '/app/etc/env.php');
    echo "Domains found and replaced" . PHP_EOL;

    printTable($uniqueDomains, ["Domain", "Replacement"]);
    foreach ($uniqueDomains as $domain => $replacement) {
        $db->query("UPDATE core_config_data SET value = REPLACE(value, '{$domain}', '{$replacement}') WHERE path IN({$patchWhere})");
        $magentoVarsContent = str_replace($domain, $replacement, $magentoVarsContent);
        $envContent = str_replace($domain, $replacement, $magentoVarsContent);
    }

    if ($magentoVarsContent) {
        echo "Saved magento-vars-local.php file for multiple domains" . PHP_EOL;
        echo "Please include magento-vars-local.php into pub/index.php" . PHP_EOL;
        file_put_contents(__DIR__ . '/magento-vars-local.php', $magentoVarsContent);
    }

    echo PHP_EOL . PHP_EOL . "=========================" . PHP_EOL;
    $domains = implode(" ", $uniqueDomains);

    echo 'HOSTS_COMMAND=echo "127.0.0.1 '.$domains.'" | sudo tee -a /etc/hosts';
    echo PHP_EOL . "=========================" . PHP_EOL;
}
function replaceConfig($path) {

    echo "Replacing app/etc/env.php" . PHP_EOL;

    $envPath = $path . '/app/etc/env.php';
    $autobackupPath = $envPath . '.autobackup';
    if (!is_file($envPath)) {
        echo "\r\n";
        echo ('Incorrect run directory, it should be home magento directory. Unable to locate env.php ' . $envPath . "\r\n");

        return;
    }

    if (is_file($autobackupPath)) {
        echo "\r\n";
        echo ('Autobackup already exists! I can\'t run ' . $autobackupPath . "\r\n");
        return;
    }

    copy($envPath, $autobackupPath);
    $config = include $envPath;



    $configPatcher = new ConfigPatcher($config);
    $configPatcher->replaceIfExists('backend.frontName', 'admin');

    $db = [
        'host' => 'db',
        'username' => 'magento',
        'dbname' => 'magento',
        'password' => 'magento',
    ];


    $configPatcher->replaceIfExists('db.connection.default', $db);
    $configPatcher->replaceIfExists('db.connection.indexer', $db);

    $configPatcher->replaceIfExists('queue.amqp', [
        'host' => 'rabbitmq',
        'port' => '5672',
        'user' => 'guest',
        'password' => 'guest',
        'virtualhost' => '/'
    ]);
    $configPatcher->replaceIfExists(
        'lock.config.path',
        '/var/www/html/' . ltrim($config['lock']['config']['path'] ?? '', '/')
    );

    $redisSettings = [
        'server' => 'redis',
        'port' => '6379'
    ];
    if ($configPatcher->exists('cache.frontend.default.backend_options.remote_backend_options')) {
        $configPatcher->replaceIfExists('cache.frontend.default.backend_options.remote_backend_options', $redisSettings);
    } else {
        $configPatcher->replaceIfExists('cache.frontend.default.backend_options', $redisSettings);
    }
    $configPatcher->replaceIfExists('cache.frontend.page_cache.backend_options', $redisSettings);
    $configPatcher->replaceIfExists('system.default.smile_elasticsuite_core_base_settings.es_client.servers', 'elasticsearch:9200');
    $configPatcher->replaceIfExists('system.default.catalog.search.elasticsearch7_server_hostname', 'elasticsearch');


    $config = $configPatcher->getConfigArray();

    if (isset($config['session']['save']) && $config['session']['save'] === 'redis') {
        $config['session']['redis']['host'] = 'redis';
        $config['session']['redis']['port'] = '6379';
    }

    unset($config['db']['slave_connection']);
    unset($config['cache']['frontend']['default']['backend_options']['load_from_slave']);
    unset($config['cache']['frontend']['page_cache']['backend_options']['load_from_slave']);
    unset($config['cache']['frontend']['default']['backend_options']['remote_backend_options']['load_from_slave']);
    unset($config['cache']['frontend']['page_cache']['backend_options']['remote_backend_options']['load_from_slave']);

//$config['http_cache_hosts'] = [
//    [
//      'host' => 'varnish',
//      'port' => '80'
//    ]
//];
    file_put_contents($envPath, "<?php\r\nreturn " . varexport($config, true) . ';');
}

function varexport($expression, $return=FALSE) {
    $export = var_export($expression, TRUE);
    $patterns = [
        "/array \(/" => '[',
        "/^([ ]*)\)(,?)$/m" => '$1]$2',
        "/=>[ ]?\n[ ]+\[/" => '=> [',
        "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
    ];
    $export = preg_replace(array_keys($patterns), array_values($patterns), $export);
    if ((bool)$return) return $export; else echo $export;
}

class ConfigPatcher
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config) {

        $this->config = $config;
    }

    public function exists($path) {
        $keys = explode('.', $path);

        $current = $this->config;
        foreach ($keys as $key) {

            if (!array_key_exists($key, $current)) {
                return false;
            }

            $current = $current[$key];
        }

        return true;
    }

    public function replaceIfExists($path, $replacement)
    {
//        if (!$this->exists($path)) {
//            return false;
//        }

        $keys = explode('.', $path);
        $current = &$this->config;
        foreach ($keys as $key) {

            if (!array_key_exists($key, $current)) {
                return false;
            }

            $current = &$current[$key];
        }

        if (is_array($replacement)) {
            foreach ($replacement as $key => $value) {
                $current[$key] = $value;
            }
        } else {
            $current = $replacement;
        }

//        if (is_array($replacement)) {
//            foreach ($replacement as $key => $value) {
//                $this->replaceIfExists($path . '.' . $key, $value);
//            }
//        } else {
//            $keys = explode('.', $path);
//
//            $current = &$this->config;
//            foreach ($keys as $key) {
//
//                if (!array_key_exists($key, $current)) {
//                    return false;
//                }
//
//                $current = &$current[$key];
//            }
//            $current = $replacement;
//        }

        return true;
    }

    public function getConfigArray(): array
    {
        return $this->config;
    }

    public function get($path) {

    }

    public function remove($path) {

    }
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