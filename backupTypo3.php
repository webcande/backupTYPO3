<?php
/*
 * Erdal Gök, https://www.webcan.de/ 
 * Script to backup TYPO3 projects: www and database
 * Finds creds on his own if not something exotic
 * tested on TYPO3 v4, v6, v7, v8.7 and v9.5
 * PHP 7
 * trigger it with 'php7 backupTypo3.php'
 * Needs gnu tar tool on windows
 * backups multiple projects at once - adjust array
 * also tested via cron
 
 * SystemEnvironmentBuilder.php exists in 6.2, 7.6 and 8.7
 * Had to make this change to differ between 7 and 8
 * But maybe very handy to change script in version detection
 */

 /*
 * v 0.95 - 2019.01.12
 * TYPO3 9.5 LTS
 * v 0.94 - 2018.10.10
 * minor fixes
 * v 0.93 - 2017.09.28
 * Add paths for TYPO3 8.7 LTS
 * some adjustment to debug information
 * v 0.92 - 2017.09.06
 * minor fixes
 * v 0.91 - 2017.08.28
 * Changed mysqldump to one with quotes 
 */
 
 
//#######################################################################
// CONFIG
//#######################################################################

/*
 * Please adjust array
	'Where to put backup into', 'document root', 'project name', 'charset'
*/
 
$backUpProjectsArr['0']=['/home/user/libs/backups/project_backups/project1/', '/home/user/www/', 'project1.de', 'utf8'];
$backUpProjectsArr['1']=['/home/user/libs/backups/project_backups/project2/', '/home/user/www/', 'project2.com', 'utf8'];
  
// some examples
//$backUpProjectsArr['2']=['/www/backups/', '/www/demos/', 'typo3_87lts', 'utf8'];
//$backUpProjectsArr['3']=['/backup/', '/html/', 'typo3', 'utf8'];
//$backUpProjectsArr['4']=['c:/xampp/backup/', 'c:/xampp/htdocs/', 'project3.local', 'utf8'];

// if mysldump is in env, leave var empty
//$mysqlBinPath='c:/xampp/mysql/bin/';
$mysqlBinPath='';

// turn on or off
$backupDB=true;
$backupWebspace=true;
$printDebug=false;

//#######################################################################
// SET SOME VAR
//#######################################################################

//error_reporting(E_ALL ^ E_WARNING);
ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');

$credsKeyArrayT4 = ['typo_db', 'typo_db_username', 'typo_db_password', 'typo_db_host'];
$credsKeyArrayT6 = ["'database'", "'username'", "'password'", "'host'"];
$credsKeyArrayT8 = ["'dbname'", "'user'", "'password'", "'host'"];
//$credsKeyArrayThisScript = ['dbName'=>'', 'dbUser'=>'', 'dbPassword'=>'', 'dbHost'=>''];

// May implement it once
$ignoreList = array('typo3temp');
$dbType = 'mysql';

//#######################################################################
// OVERALL FUNCTIONS
//#######################################################################

function extractStringBetweenTokens($firstChar, $secondChar, $string) {
    preg_match_all("/\\".$firstChar."(.*?)\\".$secondChar."/", $string, $matches);
    return $matches[1];
}

$credsKeyArrayThisScript = array();

function findCreds ($lineRest, $credsKeyArray) {
    
    global $credsKeyArrayThisScript;
    
    for ($i = 0; $i <= 3; $i++) {
        if (strstr($lineRest[0], $credsKeyArray[$i])) {
            $extractedArray=extractStringBetweenTokens("'", "'",$lineRest[1]);
            if (count($extractedArray) > 1) {
                die('You have same vars multiple times in your conf, please check');
            }   else    {
                $credsKeyArrayThisScript[$i]= $extractedArray[0];
            }
        }
    }
    return $credsKeyArrayThisScript;
}

function checkVersionIs8 ($thisTypo3Dir, $thisA) {
	$pathSystemEnvironmentBuilderPhp =  $thisTypo3Dir.'typo3/sysext/core/Classes/Core/SystemEnvironmentBuilder.php';
	$linesSystemEnvironmentBuilderPhp = file($pathSystemEnvironmentBuilderPhp);
	foreach ($linesSystemEnvironmentBuilderPhp as $line_numSystemEnvironmentBuilderPhp => $linesSystemEnvironmentBuilderPhp) {
		$lineRestSystemEnvironmentBuilderPhp = explode("define('TYPO3_branch', '", $linesSystemEnvironmentBuilderPhp);
		$resultRestSystemEnvironmentBuilderPhp = explode("');", $lineRestSystemEnvironmentBuilderPhp[1]);
		if ( ($resultRestSystemEnvironmentBuilderPhp[0]=="8.7") OR ($resultRestSystemEnvironmentBuilderPhp[0]=="9.5") )
			return true;
	}
	return false;
}

//#######################################################################
// PROCESS
//#######################################################################

$backUpProjectsArrLength=count($backUpProjectsArr);

if ($backUpProjectsArrLength>0) {
    

    for ($a = 0; $a < $backUpProjectsArrLength; $a++) {

        //#######################################################################
        // SET SOME VAR
        //#######################################################################

        $typo3Dir = $backUpProjectsArr[$a]['1'] . $backUpProjectsArr[$a]['2'] . "/";
        $typo3ConfigDir = $typo3Dir . 'typo3conf/';
        $configFile=$typo3ConfigDir . 'localconf.php';

        //#######################################################################
        // CHECK VERSION
        //#######################################################################

        if ( is_readable($configFile) )	{
            $t3Version = 't4';
            $toExplode =' = ';
            $credsKeyArray=$credsKeyArrayT4;
        } else {
            $configFile=$typo3ConfigDir. 'LocalConfiguration.php';
            if ( is_readable($configFile) )	{
                $t3Version = 't6'; // And of course v7
                $toExplode =' => ';
                $credsKeyArray=$credsKeyArrayT6;
				if (checkVersionIs8($typo3Dir, $a))
					$credsKeyArray=$credsKeyArrayT8;
            } else {
                die('config file not found, please check '. $configFile);
            }
        }

        //#######################################################################
        // PARSE LINES
        //#######################################################################

        $lines = file($configFile);
        foreach ($lines as $line_num => $line) {
            $lineRest = explode($toExplode, $line);
            $resultArray=findCreds($lineRest, $credsKeyArray);
        }

        //#######################################################################
        // DO ACTION
        //#########################################################################

        if ( count($resultArray)==4  ) {
            $todayAndTime=date('Ymd_Hi');
            $backupSQLFileName= $credsKeyArrayThisScript[0] . "_" . $todayAndTime . ".sql";
            $backupSQLFileNameAndPath= $backUpProjectsArr[$a]['0']. $backupSQLFileName;
            $backupWebSpaceFileNameAndPath= $backUpProjectsArr[$a]['0']. $backUpProjectsArr[$a]['2']. "_" . $todayAndTime .".tar.gz";

            //Build mysqldump statement
            $myDump=$mysqlBinPath.'mysqldump -a --add-drop-table -u\''.$credsKeyArrayThisScript[1].'\' -p\''.$credsKeyArrayThisScript[2].'\' -h\''.$credsKeyArrayThisScript[3].'\' --default-character-set='.$backUpProjectsArr[$a]['3'].' '.$credsKeyArrayThisScript[0].'  > '.$backupSQLFileNameAndPath;

            if ($backupDB) {
                $dumpCommandLastLine=system($myDump, $returnValue);
                $gzipCommandLastLine=system("gzip " . $backupSQLFileNameAndPath , $returnValue);
            }
        } else {
            echo 'Your creds aren\'t found'.PHP_EOL;
        }

        $tarCommand = "tar -czf '" . $backupWebSpaceFileNameAndPath . "' '" . $typo3Dir . "' --force-local";
        if ($backupWebspace) {
            $tarCommandLastLine=system($tarCommand, $returnValue);
        }

        //#######################################################################
        // DEBUG INFORMATION
        //#######################################################################

        if ($printDebug) {
            echo 'dbname='.$credsKeyArrayThisScript[0].PHP_EOL.
				'dbuser='.$credsKeyArrayThisScript[1].PHP_EOL.
				'dbpassword='.$credsKeyArrayThisScript[2].PHP_EOL.
				'dbhost='.$credsKeyArrayThisScript[3].PHP_EOL.
                'configFile='.$configFile.PHP_EOL.
				'pathSystemEnvironmentBuilderPhp='.$pathSystemEnvironmentBuilderPhp.PHP_EOL.
                'backupSQLFileNameAndPath='.$backupSQLFileNameAndPath.PHP_EOL.
                'myDump='.$myDump.PHP_EOL.
                'backupWebSpaceFileNameAndPath='.$backupWebSpaceFileNameAndPath.PHP_EOL.
                'tarCommand='.$tarCommand;
        }

    }

}