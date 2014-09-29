#!/usr/bin/php -f
<?php

include(dirname(__FILE__) . "/../../htdocs/pimcore/config/startup.php");

if ($argc != 5) {
    die('Usage: ./pimcore_install.php dbhost dbname dbuser dbpass' . "\n");
}

$dbhost = trim($argv[1]);
$dbname = trim($argv[2]);
$dbuser = trim($argv[3]);
$dbpass = trim($argv[4]);

$admin_username = 'admin';
$admin_password = 'password';

$errors = array();

// check permissions
$files = rscandir(PIMCORE_WEBSITE_VAR . "/");

foreach ($files as $file) {
    if (is_dir($file) && !is_writable($file)) {
        $errors[] = "Please ensure that the whole /" . PIMCORE_WEBSITE_VAR . " folder is writeable (recursivly)";
        break;
    }
}

if (count($errors) > 0) {

    echo "ERROR:\n";
    die(implode("\n", $errors));
}

// try to establish a mysql connection
try {

    $db = Zend_Db::factory(
        'Pdo_Mysql', // adapter name
        array(
           'host'     => $dbhost,
           'username' => $dbuser,
           'password' => $dbpass,
           'dbname'   => $dbname,
           "port"     => 3306 // port
        )
    );

    $db->getConnection();

    // check utf-8 encoding
    $result = $db->fetchRow('SHOW VARIABLES LIKE "character\_set\_database"');
    if ($result['Value'] != "utf8") {
        $errors[] = "Database charset is not utf-8";
    }
} catch (Exception $e) {
    $errors[] = "Couldn't establish connection to mysql: " . $e->getMessage();
}

if (empty($errors)) {

    $setup = new Tool_Setup();

    $setup->config(
        array(
             "database" => array(
                 "adapter" => 'Pdo_Mysql', // adapter name
                 "params"  => array(
                     'host'     => $dbhost,
                     'username' => $dbuser,
                     'password' => $dbpass,
                     'dbname'   => $dbname,
                     "port"     => 3306,
                 )
             ),
        )
    );

    $contentConfig = array(
        "username" => $admin_username,
        "password" => $admin_password
    );

    $setup->database();
    Pimcore::initConfiguration();
    $setup->contents($contentConfig);

    echo "PIMCORE INSTALL SUCCESSFUL\n";
    exit(0);

} else {
    echo "ERROR:\n";
    echo implode("\n", $errors);
    die();
}
