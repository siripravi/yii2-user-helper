<?php


namespace siripray\userhelper\traits;

use siripray\userhelper\Module;

/**
 * Trait ModuleTrait
 * @property-read Module $module
 * @package siripray\userhelper\traits
 */
trait ModuleTrait
{
    /**
     * @return Module
     */
    public function getModule()
    {
        return \Yii::$app->getModule('user');
    }
}
