<?php
/**
 * Purpose: Check, if the pimcore system is in a working state
 * Call this script periodically via Pingdom/StatusCake and test for the SUCCESS string
 */

try {

    // explicitly: do not cache test!
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

    include_once("pimcore/config/startup.php");

    // === CONFIG ===

    try {
        $config = Pimcore_Config::getSystemConfig();

    } catch (Exception $e) {

        throw new Exception('Unable to read system config.');
    }

    // === ROBOTS.TXT ===
    try{

        $robotsArray = array();
        $robotsTxt = fopen($_SERVER["DOCUMENT_ROOT"].'/robots.txt', 'r');
        while (!feof($robotsTxt)) {
            $robotsArray[] = fgets($robotsTxt);
        }
        fclose($robotsTxt);

    } catch (Exception $e){
        throw new Exception("Can't read robots.txt");
    }

    foreach($robotsArray as $robotsString){
        if(strtolower(str_replace(' ', '', $robotsString)) === 'disallow:/'){
            throw new Exception("robots.txt disallows whole domain");
        }
    }

    // === MYSQL-DB ===

    try {

        /** @var $db Zend_Db_Adapter_Mysqli */
        $db = Pimcore_Resource_Mysql::get();

    } catch (Exception $e) {

        throw new Exception('Unable to get database handle.');
    }

    try {
        // just a simple sql statement
        $num = $db->fetchOne(
            "select count(*) from users where `name`='admin'"
        );

    } catch (Exception $e) {

        throw new Exception('Unable to query database. ['.$e->getCode().']');
    }

    // be careful, the number is returned as a string .. so no "!=="!
    if ($num != 1) {
        throw new Exception("Database query faulty response.");
    }

    // === FILESYSTEM ===

    try {

        $putData = sha1(time());
        file_put_contents(PIMCORE_TEMPORARY_DIRECTORY.'/check_write.tmp', $putData);
        $getData = file_get_contents(PIMCORE_TEMPORARY_DIRECTORY.'/check_write.tmp');
        unlink(PIMCORE_TEMPORARY_DIRECTORY.'/check_write.tmp');

    } catch (Exception $e) {

        throw new Exception('Unable to read/write a file. ['.$e->getCode().']');
    }

    if ($putData !== $getData) {

        throw new Exception('Error writing/reading a file.');
    }

    // === CACHE ===

    try {

        Pimcore_Model_Cache::setForceImmediateWrite(true);

        Pimcore_Model_Cache::save(
            $putData,
            $putData,
            array('check_write')
        );

        $getData = Pimcore_Model_Cache::load($putData);

        Pimcore_Model_Cache::clearTag("check_write");

    } catch (Exception $e) {

        throw new Exception('Pimcore cache error ['.$e->getCode().']');
    }

    if ($putData !== $getData) {

        throw new Exception('Pimcore cache failure - content mismatch.');
    }

    echo "SUCCESS";

} catch (Exception $e) {

    echo "FAILURE: ".$e->getMessage();

}
