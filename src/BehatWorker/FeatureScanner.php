<?php
namespace BehatWorker;

class FeatureScanner
{
    private $features = [];

    public function __construct($dir)
    {
        $this->findFeatures($dir);
    }

    public function getFeaturesList()
    {
        return $this->features;
    }

    /**
     * Scan directory recursively.
     *
     * @param $dir
     * @param string $prefix
     *
     * @return array
     */
    private function findFeatures($dir, $prefix = '') {
        $dir = rtrim($dir, '\\/');
        $result = array();

        foreach (scandir($dir) as $file) {
            if ($file !== '.' && $file !== '..' && $file !== 'bootstrap' && $file !== 'log') {
                if (is_dir("$dir/$file")) {
                    $result = array_merge($result, $this->findFeatures("$dir/$file", "$prefix$file/"));
                }
                else {
                    if (strpos($file, '.feature') > 0) {
                        $result[] = $dir . '/' .$file;
                    }
                }
            }
        }
        //shuffle($result);
        $this->features =  $result;
    }
}
