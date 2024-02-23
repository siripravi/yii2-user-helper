<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace siripray\userhelper\traits;

use siripray\userhelper\events\AuthEvent;
use siripray\userhelper\events\ConnectEvent;
use siripray\userhelper\events\FormEvent;
use siripray\userhelper\events\ProfileEvent;
use siripray\userhelper\events\ResetPasswordEvent;
use siripray\userhelper\events\UserEvent;
use siripray\userhelper\models\Account;
use siripray\userhelper\models\Profile;
use siripray\userhelper\models\RecoveryForm;
use siripray\userhelper\models\Token;
use siripray\userhelper\models\User;
use yii\authclient\ClientInterface;
use yii\base\Model;

/**
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
trait EventTrait
{
    /**
     * @param  Model     $form
     * @return FormEvent
     * @throws \yii\base\InvalidConfigException
     */
    protected function getFormEvent(Model $form)
    {
        return \Yii::createObject(['class' => FormEvent::class, 'form' => $form]);
    }

    /**
     * @param  User|null $user
     * @return UserEvent
     * @throws \yii\base\InvalidConfigException
     */
    protected function getUserEvent(User $user = null)
    {
        return \Yii::createObject(['class' => UserEvent::class, 'user' => $user]);
    }

    /**
     * @param  Profile      $profile
     * @return ProfileEvent
     * @throws \yii\base\InvalidConfigException
     */
    protected function getProfileEvent(Profile $profile)
    {
        return \Yii::createObject(['class' => ProfileEvent::class, 'profile' => $profile]);
    }


    /**
     * @param  Account      $account
     * @param  User         $user
     * @return ConnectEvent
     * @throws \yii\base\InvalidConfigException
     */
    protected function getConnectEvent(Account $account, User $user)
    {
        return \Yii::createObject(['class' => ConnectEvent::class, 'account' => $account, 'user' => $user]);
    }

    /**
     * @param  Account         $account
     * @param  ClientInterface $client
     * @return AuthEvent
     * @throws \yii\base\InvalidConfigException
     */
    protected function getAuthEvent(Account $account, ClientInterface $client)
    {
        return \Yii::createObject(['class' => AuthEvent::class, 'account' => $account, 'client' => $client]);
    }

    /**
     * @param  Token        $token
     * @param  RecoveryForm $form
     * @return ResetPasswordEvent
     * @throws \yii\base\InvalidConfigException
     */
    protected function getResetPasswordEvent(Token $token = null, RecoveryForm $form = null)
    {
        return \Yii::createObject(['class' => ResetPasswordEvent::class, 'token' => $token, 'form' => $form]);
    }
}
