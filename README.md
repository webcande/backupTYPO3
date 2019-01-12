# backupTYPO3
Script which finds TYPO3 database credentials automatically and backups multiple TYPO3 projects

Erdal Gök, https://www.webcan.de/ 

Script to backup TYPO3 projects: www and database. Finds creds on his own if not something exotic. Tested on TYPO3 v4, v6, v7, v8.7 and v9.5.
PHP 7. Trigger it with 'php7 backupTypo3.php'. Needs gnu tar tool on windows. Backups multiple projects at once - adjust array. Also tested via cron.
SystemEnvironmentBuilder.php exists in 6.2, 7.6 and 8.7. Had to make this change to differ between 7 and 8. But maybe very handy to change script in version detection.

v 0.95 - 2019.01.12
TYPO3 9.5 LTS

v 0.94 - 2018.10.10
minor fixes

v 0.93 - 2017.09.28
Add paths for TYPO3 8.7 LTS
some adjustment to debug information

v 0.92 - 2017.09.06
minor fixes

v 0.91 - 2017.08.28
Changed mysqldump to one with quotes 
