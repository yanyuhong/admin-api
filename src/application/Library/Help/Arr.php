<?php
namespace App\Library\Help;

/**
 * Class Arr
 *
 * @package App\Library\Help
 */
class Arr
{
    /**
     * @param  $aArr
     * @param string $sKey
     *
     * @return null
     */
    public static function get($aArr, $sKey, $mDefault = '')
    {
        return (is_array($aArr) && isset($aArr[$sKey])) ? $aArr[$sKey] : $mDefault;
    }

    public static function getNoEmpty($aArr, $sKey, $mDefault = '')
    {
        return (is_array($aArr) && isset($aArr[$sKey]) && !empty($aArr[$sKey])) ? $aArr[$sKey] : $mDefault;
    }

    /**
     * @param array $aArr
     * @param string $sKey
     *
     * @return mixed
     * @throws \OutOfRangeException
     */
    public static function getForce(array $aArr, $sKey)
    {
        if (!array_key_exists($sKey, $aArr)) {
            throw new \OutOfRangeException("$sKey is not in array" . json_encode($aArr));
        }
        return $aArr[$sKey];
    }

    /**
     * @param array $aArr
     * @param string $sKey
     * @param array $aExcept
     * @param array $aLeft
     *
     * @return array
     */
    public static function changeIndex(array $aArr, $sKey = 'id', array $aExcept = null, array $aLeft = null)
    {
        $aResult = array();
        if (!empty($aLeft)) {
            $aLeft = array_flip($aLeft);
            foreach ($aArr as $aItem) {
                $aResult[$aItem[$sKey]] = array_intersect_key($aItem, $aLeft);
            }
        } elseif (!empty($aExcept)) {
            $aExcept = array_flip($aExcept);
            foreach ($aArr as $aItem) {
                $aResult[$aItem[$sKey]] = array_diff_key($aItem, $aExcept);;
            }
        } else {
            foreach ($aArr as $aItem) {
                $aResult[$aItem[$sKey]] = $aItem;
            }
        }
        return $aResult;
    }

    /**
     * @param array $aArr
     * @param string $sKeyName
     * @param string $sValueName
     *
     * @return array
     */
    public static function changeIndexToKVMap(array $aArr, $sKeyName, $sValueName)
    {
        $aResult = array();
        foreach ($aArr as $aItem) {
            $aResult[$aItem[$sKeyName]] = $aItem[$sValueName];
        }
        return $aResult;
    }

    /**
     * @param array $aArr
     * @param string $sKey
     *
     * @return array
     */
    public static function getIndex(array $aArr, $sKey = 'id')
    {
        $aResult = array();
        foreach ($aArr as $aItem) {
            $aResult[] = $aItem[$sKey];
        }
        return $aResult;
    }

    public static function getFields($aArr, array $fields)
    {
        $data = [];
        foreach ($fields as $field) {
            if (isset($aArr[$field])) {
                $data[$field] = $aArr[$field];
            }
        }
        return $data;
    }
}
