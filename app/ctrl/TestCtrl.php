<?php
/**
 * 测试一些实验功能
 */
namespace app\ctrl;

use base\Ctrl as Base;
use Swift_Mailer;
use Swift_MailTransport;
use Swift_Message;
use Swift_SendmailTransport;
use Swift_SmtpTransport;


class TestCtrl extends Base
{

    //private $usersDao;
    //private $testDao;

    public function init()
    {
        //$this->usersDao = $this->getDao('UsersDataDao');
        //$this->testDao  = $this->getDao('TestDao');
    }

    public function helloAction()
    {
        echo 'hello world' . PHP_EOL;
        var_dump($this->params);
    }

    public function finishAction()
    {
        print_r($this->params);
    }

}
