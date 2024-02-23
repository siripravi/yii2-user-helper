<?php

/*
 * This file is part of the Dektrium project.
 *
 * (c) Dektrium project <http://github.com/dektrium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace siripravi\authhelper;

use siripray\userhelper\mail\RegistrationEmail;
use siripray\userhelper\models\Token;
use siripray\userhelper\models\User;
use Yii;
use yii\base\Component;

/**
 * Mailer.
 *
 * @author Dmitry Erofeev <dmeroff@gmail.com>
 */
class Mailer extends Component
{
    /**
     * @var string
     */
    public $viewPath;// = '@dektrium/user/views/mail';

    /**
     * @var string|array Default: `Yii::$app->params['adminEmail']` OR `no-reply@example.com`
     */
    public $sender;

    /**
     * @var string
     */
    protected $registrationSubject;

    /**
     * @var string
     */
    protected $confirmationSubject;

     /** @var string */
     protected $welcomeSubject;
    /**
     * @var string
     */
    protected $reconfirmationSubject;

    /**
     * @var string
     */
    protected $recoverySubject;

    /**
     * @var string
     */
    protected $approvalSubject;

    /**
     * @var \siripray\userhelper\Module
     */
    protected $module;

    /**
     * @return string
     */
    public function getRegistrationSubject()
    {
        if ($this->registrationSubject == null) {
            $this->setRegistrationSubject(Yii::t('user', 'Welcome to {0}', Yii::$app->name));
        }

        return $this->registrationSubject;
    }

    /**
     * @param string $registrationSubject
     */
    public function setRegistrationSubject($registrationSubject)
    {
        $this->registrationSubject = $registrationSubject;
    }

    /**
     * @return string
     */
    public function getConfirmationSubject()
    {
        if ($this->confirmationSubject == null) {
            $this->setConfirmationSubject(Yii::t('user', 'Confirm account on {0}', Yii::$app->name));
        }

        return $this->confirmationSubject;
    }

    /**
     * @param string $confirmationSubject
     */
    public function setConfirmationSubject($confirmationSubject)
    {
        $this->confirmationSubject = $confirmationSubject;
    }

    /**
     * @return string
     */
    public function getReconfirmationSubject()
    {
        if ($this->reconfirmationSubject == null) {
            $this->setReconfirmationSubject(Yii::t('user', 'Confirm email change on {0}', Yii::$app->name));
        }

        return $this->reconfirmationSubject;
    }
     /**
     * @return string
     */
    public function getWelcomeSubject()
    {
        if ($this->welcomeSubject == null) {
            $this->setWelcomeSubject(Yii::t('user', 'Welcome to {0}', Yii::$app->name));
        }

        return $this->welcomeSubject;
    }

    /**
     * @param string $welcomeSubject
     */
    public function setWelcomeSubject($welcomeSubject)
    {
        $this->welcomeSubject = $welcomeSubject;
    }

    /**
     * @param string $reconfirmationSubject
     */
    public function setReconfirmationSubject($reconfirmationSubject)
    {
        $this->reconfirmationSubject = $reconfirmationSubject;
    }

    /**
     * @return string
     */
    public function getRecoverySubject()
    {
        if ($this->recoverySubject == null) {
            $this->setRecoverySubject(Yii::t('user', 'Complete password reset on {0}', Yii::$app->name));
        }

        return $this->recoverySubject;
    }

    /**
     * @param string $recoverySubject
     */
    public function setRecoverySubject($recoverySubject)
    {
        $this->recoverySubject = $recoverySubject;
    }

    /**
     * @return string
     */
    public function getApprovalSubject()
    {
        if ($this->approvalSubject == null) {
            $this->setApprovalSubject(Yii::t('user', 'Your account on {0} has been approved', Yii::$app->name));
        }

        return $this->approvalSubject;
    }

    /**
     * @param string $approvalSubject
     */
    public function setApprovalSubject($approvalSubject)
    {
        $this->approvalSubject = $approvalSubject;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->module = Yii::$app->getModule('user');
        parent::init();
    }

    /**
     * Sends registration email.
     *
     * @param  RegistrationEmail|null $email
     * @return bool
     */
    public function sendRegistrationMessage(RegistrationEmail $email = null)
    {
        if ($email instanceof RegistrationEmail) {
            return $this->sendMessage(
                $email->getUser()->email,
                $this->getRegistrationSubject(),
                'registration',
                ['email' => $email]
            );
        }

        return true;
    }

    /**
     * Sends an email to a user with confirmation link.
     *
     * @param User  $user
     * @param Token $token
     *
     * @return bool
     */
    public function sendConfirmationMessage(User $user, Token $token)
    {
        return $this->sendMessage(
            $user->email,
            $this->getConfirmationSubject(),
            'confirmation',
            ['user' => $user, 'token' => $token]
        );
    }

    /**
     * Sends an email to a user with reconfirmation link.
     *
     * @param User  $user
     * @param Token $token
     *
     * @return bool
     */
    public function sendReconfirmationMessage(User $user, Token $token)
    {
        if ($token->type == Token::TYPE_CONFIRM_NEW_EMAIL) {
            $email = $user->unconfirmed_email;
        } else {
            $email = $user->email;
        }

        return $this->sendMessage(
            $email,
            $this->getReconfirmationSubject(),
            'reconfirmation',
            ['user' => $user, 'token' => $token]
        );
    }

    /**
     * Sends an email to a user with recovery link.
     *
     * @param User  $user
     * @param Token $token
     *
     * @return bool
     */
    public function sendRecoveryMessage(User $user, Token $token)
    {
        return $this->sendMessage(
            $user->email,
            $this->getRecoverySubject(),
            'recovery',
            ['user' => $user, 'token' => $token]
        );
    }

    /**
     * Sends an email when user's account has been approved.
     * @param  User $user
     * @return bool
     */
    public function sendApprovalMessage(User $user)
    {
        return $this->sendMessage(
            $user->email,
            $this->getApprovalSubject(),
            'approval'
        );
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $view
     * @param array  $params
     *
     * @return bool
     */
    protected function sendMessage($to, $subject, $view, $params = [])
    {
        /** @var \yii\mail\BaseMailer $mailer */
        $mailer = Yii::$app->mailer;
        $mailer->viewPath = $this->viewPath;
        $mailer->getView()->theme = Yii::$app->view->theme;

        if ($this->sender === null) {
            $this->sender = isset(Yii::$app->params['adminEmail']) ?
                Yii::$app->params['adminEmail']
                : 'no-reply@example.com';
        }

        return $mailer->compose(['html' => $view, 'text' => 'text/' . $view], $params)
            ->setTo($to)
            ->setFrom($this->sender)
            ->setSubject($subject)
         
         
            ->send();
    }
     /**
     * Sends an email to a user after registration.
     *
     * @param User  $user
     * @param Token $token
     * @param bool  $showPassword
     *
     * @return bool
     */
    public function sendWelcomeMessage(User $user, Token $token = null, $showPassword = false)
    {
        return $this->sendMessage(
            $user->email,
            $this->getWelcomeSubject(),
            'welcome',
            ['user' => $user, 'token' => $token, 'module' => $this->module, 'showPassword' => $showPassword]
        );
    }
}
