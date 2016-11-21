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

        function scan($dir, $prefix = '')
        {
            $dir = rtrim($dir, '\\/');
            $result = [];

            foreach (scandir($dir) as $file) {
                if ($file !== '.' && $file !== '..') {
                    if (is_dir("$dir/$file")) {
                        $result = array_merge($result, scan("$dir/$file", "$prefix$file/"));
                    }
                    else {
                        if (strpos($file, '.feature') !== false) {
                            $result[] = $dir . '/' .$file;
                        }
                    }
                }
            }
            return $result;
        }
        $features = scan($dir, $prefix = '');
        shuffle($features);
        $this->features = $features;
    }
}
