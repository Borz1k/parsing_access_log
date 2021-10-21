<?
include('lib/FileLog.php');

$fileLog = new FileLog();
$fileLog->readLogFile();

echo '<pre>';
echo $fileLog->jsonData();
echo '</pre>';
