<?php
namespace Rails\ActionDispatch\Http;

# TODO
class UploadedFiles
{
    protected $files;
    
    /**
     * @para array $files   Normally it would be $_FILES
     */
    public function __construct(array $files)
    {
        $this->files = $this->import($files);
    }
    
    public function files()
    {
        return $this->files;
    }
    
    protected function import(array $files)
    {
        $imported = [];
    
        foreach ($files as $mainName => $data) {
            if (!is_array($data['name'])) {
                if ($data['error'] != UPLOAD_ERR_NO_FILE) {
                    $imported[$mainName] = new UploadedFile($files[$mainName]);
                }
            } else {
                $imported[$mainName] = $this->getSubNames($data);
            }
        }
        
        return $imported;
    }
    
    protected function getSubNames(array $arr)
    {
        $arranged = new \ArrayObject();
        
        foreach ($arr['name'] as $k => $value) {
            if (is_string($value)) {
                if ($arr['error'] != UPLOAD_ERR_NO_FILE) {
                    $arranged[$k] = new UploadedFile([
                        'name'     => $value,
                        'type'     => $arr['type'][$k],
                        'tmp_name' => $arr['tmp_name'][$k],
                        'error'    => $arr['error'][$k],
                        'size'     => $arr['size'][$k],
                    ]);
                }
            } else {
                $keys = ['name', $k];
                $this->getSubNamesRecursive($arranged, $keys, $arr);
            }
        }
        
        return $arranged->getArrayCopy();
    }
    
    protected function getSubNamesRecursive($arranged, $keys, $arr)
    {
        $baseArr = $arr;
        foreach ($keys as $key) {
            $baseArr = $baseArr[$key];
        }
        
        foreach ($baseArr as $k => $value) {
            if (is_string($value)) {
                $this->setArranged($arranged, array_merge($keys, [$k]), [
                    'name'     => $value,
                    'type'     => $this->foreachKeys(array_merge(['type'] + $keys, [$k]), $arr),
                    'tmp_name' => $this->foreachKeys(array_merge(['tmp_name'] + $keys, [$k]), $arr),
                    'error'    => $this->foreachKeys(array_merge(['error'] + $keys, [$k]), $arr),
                    'size'     => $this->foreachKeys(array_merge(['size'] + $keys, [$k]), $arr),
                ]);
            } else {
                $tmpKeys = $keys;
                $tmpKeys[] = $k;
                $this->getSubNamesRecursive($arranged, $tmpKeys, $arr);
            }
        }
    }
    
    protected function foreachKeys($keys, $arr)
    {
        $baseArr = $arr;
        foreach ($keys as $key) {
            $baseArr = $baseArr[$key];
        }
        return $baseArr;
    }
    
    protected function setArranged($arr, $keys, $val)
    {
        if ($val['error'] == UPLOAD_ERR_NO_FILE) {
            return;
        }
        
        array_shift($keys);
        $lastKey = array_pop($keys);
        $baseArr = &$arr;
        foreach ($keys as $key) {
            if (!isset($baseArr[$key])) {
                $baseArr[$key] = [];
            }
            $baseArr = &$baseArr[$key];
        }
        $baseArr[$lastKey] = new UploadedFile($val);
    }
}
