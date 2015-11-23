<?php //// VERSION:1

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

class VersionDetector
{
    public static function getVersionFromFile($file)
    {
        $fh = fopen($file, 'r');
        if (!$fh) {
            return 'unknown';
        }
        $line = fgets($fh);
        close($fh);
        $tmp = explode(':', $line);
        return $tmp[1];
    }
}

$myVer = VersionDetector::getVersionFromFile(__FILE__);
$gitVer = VersionDetector::getVersionFromFile('');

$inspector = new FileInspector();
$inspector->inspectDirectory('./');

print "error_reporting(E_ALL): " . htmlspecialchars(implode(', ', $inspector->errorReporting)) . "<br/>\n";
print "ini_set('display_errors', 1): " . htmlspecialchars(implode(', ', $inspector->iniSet)) . "<br/>\n";
print "var_dump(): " . htmlspecialchars(implode(', ', $inspector->varDump)) . "<br/>\n";
print "print_r(): " . htmlspecialchars(implode(', ', $inspector->printR)) . "<br/>\n";