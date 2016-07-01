@ECHO OFF

:: IMPORTANT: Adjust this path to a directory on your local system where you want the analysis results to be written to.
set RESULT_DIR="U:\TGMPA analysis results"

:: IMPORTANT: Adjust this path to point to the directory in which you've slurped the Plugin repository.
cd U:\WordPress-Plugin-Directory-Slurper

set LOGFILE_STAMP=%DATE:~9,4%%DATE:~6,2%%DATE:~3,2%-%TIME:~0,2%.%TIME:~3,2%

@ECHO ON

php -f update

@ECHO OFF

ECHO(
ECHO Executing searches in plugins

ECHO Searching for strings
FINDSTR /S /L /M /C:"class TGM_Plugin_Activation" /C:"Automatic plugin installation and activation library." /C:"Creates a way to automatically install and activate plugins from within themes." /C:"do_action( 'tgmpa_register' )" /C:"* Plugin installation and activation for WordPress themes." /C:"@package   TGM-Plugin-Activation" *.php > "%RESULT_DIR%\%LOGFILE_STAMP%-plugins TGMPA search COMBINED.log"

ECHO Searching for files: *plugin-activation.php
DIR "*plugin-activation.php" /S /B > "%RESULT_DIR%\%LOGFILE_STAMP%-plugins TGMPA search partial file name.log"

ECHO Searching for Depends: string
FINDSTR /S /M /L /C:"Depends: " *.php > "%RESULT_DIR%\%LOGFILE_STAMP%-plugins with Depends header.log"

ECHO Finished executing searches in plugins
ECHO(

copy "%RESULT_DIR%\%LOGFILE_STAMP%-plugins TGMPA search COMBINED.log" "%RESULT_DIR%\latest-plugins TGMPA search COMBINED.log"

copy "%RESULT_DIR%\%LOGFILE_STAMP%-plugins TGMPA search partial file name.log" "%RESULT_DIR%\latest-plugins TGMPA search partial file name.log"

copy "%RESULT_DIR%\%LOGFILE_STAMP%-plugins with Depends header.log" "%RESULT_DIR%\latest-plugins with Depends header.log"
