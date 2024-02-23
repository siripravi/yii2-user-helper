<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace siripray\userhelper\service;

use siripray\userhelper\service\exceptions\InvalidTokenException;
use siripray\userhelper\service\exceptions\InvalidUserException;
use siripray\userhelper\events\RegistrationEvent;
use siripray\userhelper\events\UserEvent;
use siripray\userhelper\mail\RegistrationEmail;
use siripray\userhelper\Mailer;
use siripray\userhelper\models\Token;
use siripray\userhelper\models\User;
use yii\base\Component;
use yii\base\Event;

/**
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class ConfirmationService extends Component
{
    const EVENT_BEFORE_CONFIRM = 'beforeConfirm';
    const EVENT_AFTER_CONFIRM = 'afterConfirm';
    const EVENT_BEFORE_APPROVE = 'beforeApprove';
    const EVENT_AFTER_APPROVE = 'afterApprove';

    /**
     * Whether account confirmation is enabled.
     *
     * @var bool
     */
    public $isEnabled = true;

    /**
     * Whether user can log in even if his account is not confirmed.
     *
     * @var bool
     */
    public $isLoginWhileUnconfirmedEnabled = false;

    /**
     * Whether user needs to click confirmation link sent by email.
     *
     * @var bool
     */
    public $isEmailConfirmationEnabled = true;

    /**
     * Whether user needs to be confirmed by admin.
     *
     * @var bool
     */
    public $isAdminApprovalEnabled = false;

    /**
     * Whether user should be automatically logged in after confirmation.
     *
     * @var bool
     */
    public $isAutoLoginEnabled = true;

    /**
     * @inheritdoc
     */
    public function confirm(User $user)
    {
        if (!$this->isEnabled) {
            return false;
        }

        $this->checkUser($user);

        $this->trigger(self::EVENT_BEFORE_CONFIRM, $this->getUserEvent($user));
        $user->confirmed_at = time();
        $user->save(false);
        $this->trigger(self::EVENT_AFTER_CONFIRM, $this->getUserEvent($user));

        return true;
    }

    /**
     * @inheritdoc
     */
    public function approve(User $user)
    {
        if (!$this->isEnabled) {
            return false;
        }

        if ($user instanceof User && !$user->isApproved()) {
            $this->trigger(self::EVENT_AFTER_APPROVE, $this->getUserEvent($user));
            $user->approved_at = time();
            $user->save(false);

            /** @var Mailer $mailer */
            $mailer = \Yii::createObject(Mailer::class);
            $mailer->sendApprovalMessage($user);

            $this->trigger(self::EVENT_AFTER_APPROVE, $this->getUserEvent($user));
            return true;
        }

        throw new InvalidUserException();
    }

    /**
     * @inheritdoc
     */
    public function attemptConfirmation(User $user = null, $code)
    {
        $this->checkUser($user);

        $token = $this->findConfirmationToken($user, $code);
        if (!$this->isTokenValid($token)) {
            \Yii::$app->session->setFlash(
                'error',
                \Yii::t('user', 'The confirmation link is invalid or expired. Please try requesting a new one.')
            );
            throw new InvalidTokenException();
        }

        if ($this->confirm($user)) {
            if ($this->isAutoLoginEnabled) {
                \Yii::$app->user->login($user, \Yii::$app->getModule('user')->rememberFor);
            }
            \Yii::$app->session->setFlash(
                'success',
                \Yii::t('user', 'Thank you, registration is now complete.')
            );
            return true;
        }

        return false;
    }

    /**
     * Initializes confirmation status of user.
     *
     * @param RegistrationEvent $event
     */
    public function initializeConfirmationStatus(RegistrationEvent $event)
    {
        if (!$this->isEnabled || !$this->isEmailConfirmationEnabled) {
            $event->getUser()->confirmed_at = time();
        }
    }

    /**
     * Creates new confirmation token and sends it to user.
     *
     * @param RegistrationEvent $event
     */
    public function sendConfirmationMessage(RegistrationEvent $event)
    {
        $token = $this->createTokenModel();
        $token->type = Token::TYPE_CONFIRMATION;
        $token->link('user', $event->getUser());

        $event->getEmail()->setConfirmationLink($token->getUrl());
    }

    /**
     * @inheritdoc
     */
    public function resendConfirmationMessage(User $user = null)
    {
        \Yii::$app->session->setFlash(
            'info',
            \Yii::t('user', 'Confirmation message has been resent to your email address')
        );

        $this->checkUser($user);

        $token = $this->createTokenModel();
        $token->type = Token::TYPE_CONFIRMATION;
        $token->link('user', $user);

        /** @var RegistrationEmail $email */
        $email = \Yii::createObject(RegistrationEmail::class, [$user]);
        $email->setConfirmationLink($token->getUrl());

        /** @var Mailer $mailer */
        $mailer = \Yii::createObject(Mailer::class);
        $mailer->sendRegistrationMessage($email);
    }

    /**
     * Whether token is valid.
     *
     * @param  Token|null $token
     * @return bool
     */
    protected function isTokenValid(Token $token = null)
    {
        return $token instanceof Token && !$token->isExpired();
    }

    /**
     * Finds confirmation token.
     *
     * @param  User   $user
     * @param  string $code
     * @return Token|null
     */
    protected function findConfirmationToken(User $user, $code)
    {
        $token = $this->createTokenModel();
        return $token::find()->byUserId($user->id)->byCode($code)->byType(Token::TYPE_CONFIRMATION)->one();
    }

    /**
     * Creates new token object.
     *
     * @return object|Token
     */
    protected function createTokenModel()
    {
        return \Yii::createObject(Token::class);
    }

    /**
     * @param  User|null $user
     * @return bool
     * @throws InvalidUserException
     */
    protected function checkUser(User $user = null)
    {
        if ($user instanceof User && !$user->getIsConfirmed()) {
            return true;
        }

        throw new InvalidUserException();
    }

    /**
     * @param  User $user
     * @return object|UserEvent
     */
    protected function getUserEvent(User $user)
    {
        return \Yii::createObject([
            'class' => UserEvent::class,
            'user' => $user,
        ]);
    }
}