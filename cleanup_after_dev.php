<?php //// VERSION:1.0.1

error_reporting(E_ALL);
ini_set('display_errors',1);

# todo: updates
# todo: payment methods
# todo: shipping
# todo: excluded files
# exit, disabled cache, compilation, merge, indexation,test orders, test products, scan visible errors, scan js errors

class FileInspector
{

    public $errorReporting = array();
    public $iniSet = array();
    public $varDump = array();
    public $printR = array();

    private $errors = array();
    private $allowedExtensions = array('php', 'htaccess');
    private $sizeThreshold = 5000;

    public function __construct()
    {

    }

    public function inspectDirectory($dir)
    {
        $dh = opendir($dir);
        if (!$dh) {
            $this->errors[] = "Can't read directory $dir";
            return;
        }

        while ($file = readdir($dh)) {
            if (is_dir($file)) {
                if ($file != '.' and $file != '..') {
                    $this->inspectDirectory($dir . $file . '/');
                }
            } else {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if (in_array($ext, $this->allowedExtensions)) {
                    if (filesize($dir . $file) <= $this->sizeThreshold) {
                        $contents = file_get_contents($dir . $file);

                        if (preg_match('/error_reporting\s*\(\s*E_ALL/ism', $contents)) {
                            $this->errorReporting[] = $dir . $file;
                        }
                        if (preg_match('/ini_set\s*\(\s*("|\')display_errors("|\')\s*,\s*(1|true)/ism', $contents)) {
                            $this->iniSet[] = $dir . $file;
                        }
                        if (strpos($contents, 'var_dump') !== false) {
                            $this->varDump[] = $dir . $file;
                        }
                        if (strpos($contents, 'print_r') !== false) {
                            $this->printR[] = $dir . $file;
                        }

                        unset($contents);
                    }
                }
            }
        }
    }

}

class Updater
{
    public $git = '';

    public function __construct($url)
    {
        $this->git = $url;
    }

    public function handleRoute()
    {
        if (isset($_GET['r']) and $_GET['r'] == 'update') {
            print 'running update';
            exit;
        }
    }

    public function getUpdateUrl()
    {
        return $_SERVER['REQUEST_URI'] . '?r=update';
    }

    public function getVersionFromFile($file)
    {
        $fh = fopen($file, 'r');
        if (!$fh) {
            return 'unknown';
        }
        $line = fgets($fh);
        fclose($fh);
        $tmp = explode(':', $line);
        return trim($tmp[1]);
    }

    public function compareVersions($v1, $v2)
    {
        $version1 = explode('.', $v1);
        $version2 = explode('.', $v2);

        $n = max(count($version1), count($version2));
        for ($i = 0; $i < $n; $i++) {
            if (@$version1[$i] > @$version2[$i]) {
                return 1;
            } elseif (@$version1[$i] < @$version2[$i]) {
                return -1;
            }
            return 0;
        }
    }

    public function hasNewVersion()
    {
        $myVer = $this->getVersionFromFile(__FILE__);
        $gitVer = $this->getVersionFromFile($this->git);
        if ($this->compareVersions($myVer, $gitVer) > 0) {
            return true;
        }
        return false;
    }
}


$updater = new Updater('https://raw.githubusercontent.com/kostofffan/cleanup-after-dev/master/cleanup_after_dev.php');
$updater->handleRoute();

$inspector = new FileInspector();
$inspector->inspectDirectory('./');

?>
<html>
<head>
    <title>Cleanup <?php print htmlspecialchars($_SERVER['SERVER_NAME']); ?> after developer</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" integrity="sha512-dTfge/zgoMYpP7QbHy4gWMEGsbsdZeCXz7irItjcC3sPUFtf0kuFbDz/ixG7ArTxmDjLXDmezHubeNikyKGVyQ==" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css" integrity="sha384-aUGj/X2zp5rLCbBxumKTCw2Z50WgIr1vs/PFN4praOTvYXWlVyh2UtNUU0KAUhAX" crossorigin="anonymous">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js" integrity="sha512-K1qjQ+NcF2TYO/eI3M6v8EiNYZfA95pQumfvcVrTHtwQVDG+aHRqLi/ETn2uB+1JqwYqVG3LIvdm9lj6imS/pQ==" crossorigin="anonymous"></script>
</head>
<body>
<div class="container">
    <?php if ($updater->hasNewVersion()) { ?>
        <p class="alert alert-danger">New version is available. Please <a href="<?php print htmlspecialchars($updater->getUpdateUrl()); ?>">update</a>.</p>
    <?php } ?>
    <?php
    print "error_reporting(E_ALL): " . htmlspecialchars(implode(', ', $inspector->errorReporting)) . "<br/>\n";
    print "ini_set('display_errors', 1): " . htmlspecialchars(implode(', ', $inspector->iniSet)) . "<br/>\n";
    print "var_dump(): " . htmlspecialchars(implode(', ', $inspector->varDump)) . "<br/>\n";
    print "print_r(): " . htmlspecialchars(implode(', ', $inspector->printR)) . "<br/>\n";
    ?>
</div>
</body>
</html>