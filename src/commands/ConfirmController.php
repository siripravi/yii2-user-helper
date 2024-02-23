<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace siripray\userhelper\commands;

use siripray\userhelper\models\User;
use siripray\userhelper\traits\ServiceTrait;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Confirms a user.
 *
 * @property \siripray\userhelper\Module $module
 *
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class ConfirmController extends Controller
{
    use ServiceTrait;

    /**
     * Confirms a user by setting confirmed_at field to current time.
     *
     * @param string $search Email or username
     */
    public function actionIndex($search)
    {
        /** @var User $user */
        $user = \Yii::createObject(User::class);
        $user = $user::find()->byEmailOrUsername($search)->one();

        if ($user === null) {
            $this->stdout(Yii::t('user', 'User is not found') . "\n", Console::FG_RED);
        } else {
            if ($this->getConfirmationService()->confirm($user)) {
                $this->stdout(Yii::t('user', 'User has been confirmed') . "\n", Console::FG_GREEN);
            } else {
                $this->stdout(Yii::t('user', 'Error occurred while confirming user') . "\n", Console::FG_RED);
            }
        }
    }
}
