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
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Updates user's password.
 *
 * @property \siripray\userhelper\Module $module
 *
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class PasswordController extends Controller
{
    /**
     * Updates user's password to given.
     *
     * @param string $search   Email or username
     * @param string $password New password
     */
    public function actionIndex($search, $password)
    {
        /** @var User $user */
        $user = \Yii::createObject(User::class);
        $user = $user::find()->byEmailOrUsername($search)->one();

        if ($user === null) {
            $this->stdout(Yii::t('user', 'User is not found') . "\n", Console::FG_RED);
        } else {
            if ($user->resetPassword($password)) {
                $this->stdout(Yii::t('user', 'Password has been changed') . "\n", Console::FG_GREEN);
            } else {
                $this->stdout(Yii::t('user', 'Error occurred while changing password') . "\n", Console::FG_RED);
            }
        }
    }
}
