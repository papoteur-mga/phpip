<?php

class AuthController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        
    }

    public function loginAction()
    {
        // action body
        $auth = Zend_Auth::getInstance();
        $identity = $auth->getIdentity();
//        $registry = Zend_Registry::getInstance();

        if ($identity && $identity !="") {
            $this->_helper->redirector('index','index');
        }else{
            $authTable = new Application_Model_DbTable_User();
            $loginForm = new Application_Form_Auth_Login($_POST);
            $modelUser = new Application_Model_User();
            
            $loginForm->getElement('submit')->setOptions(array('class' => 'button-style'));

            if ($loginForm->isValid($_POST)){
                $adapter = new Zend_Auth_Adapter_DbTable(
                    $authTable->getAdapter(),
                    'User',
                    'Username',
                    'Password',
                    'MD5(CONCAT(?, PasswordSalt))'
                );
                    

                $adapter->setIdentity($loginForm->getValue('Email'));
                $adapter->setCredential($loginForm->getValue('Password'));
                
                $result = $auth->authenticate($adapter);

                if ($result->isValid()){
                    #$authSession = Zend_Session_Namespace::getInstance('');
//                    $registry->set('id', $id);
                    $this->_helper->FlashMessenger('Successful Login');
                    $siteInfoNamespace = new Zend_Session_Namespace('siteInfoNamespace');
                    $userInfo = $modelUser->getAll($loginForm->getValue('Email'));
                    
                    $modelUser->updateLastLogin($userInfo['ID']);
                    
                    $siteInfoNamespace->userId = $userInfo['ID'];
                    $siteInfoNamespace->Firstname = $userInfo['Firstname'];
                    $siteInfoNamespace->username = $userInfo['Username'];
                    $siteInfoNamespace->password = $loginForm->getValue('Password');
                                        
                    if($siteInfoNamespace->requestURL){
                        $redirectURL = $siteInfoNamespace->requestURL;
                        $siteInfoNamespace->requestURL = "";
                        $this->_helper->redirector($redirectURL['action'],$redirectURL['controller'],$redirectURL['module']);
                    } else {
                        $this->_helper->redirector('index','index');
                    }
                    return;
                }else{
                	 $layout = Zend_Layout::getMvcInstance();
                   $layout->setLayout('layout.login');
                   $code = array();
                   switch($result->getCode())
                   {
                   	case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                   		$auth_error = "Username not found!";
                   		$code[$result->getCode()] = "Username not found!";
                   		break;
                   	case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                   		$auth_error = "Invalid password!";
                   		$code[$result->getCode()] = "Invalid password!";
                   		break;
                   	case Zend_Auth_Result::SUCCESS:
                   		$code[$result->getCode()] = "success";
                   		break;
                   	case Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS:
                   		$code[$result->getCode()] = "ambiguous";
                   		break;
                   	default:
                   		break;
                   }
                   $this->view->auth_code = $code;
                   $this->view->auth_error = $auth_error;
                }

            }else{
                $layout = Zend_Layout::getMvcInstance();
                $layout->setLayout('layout.login');
	          }
        }

        $this->view->loginForm = $loginForm;
    }

    public function logoutAction()
    {
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();
        Zend_Session::namespaceUnset("siteInfoNamespace");
        $this->_helper->redirector('login','auth');
        // action body
    }

}
