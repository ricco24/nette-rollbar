<?php

namespace Kelemen\Rollbar;

use Rollbar;
use \Tracy\Debugger as TDebugger;

/**
 * Logger wit rollbar connect for Tracy.
 * Usage:
 * <code>
 * // Init, best in bootstrap.php
 * \Kelemen\Rollbar\RollbarLogger::register($container->parameters['rollbar']);
 * </code>
 * 
 * Use nette user:
 * <code>
 * // Call in presenter
 * \Kelemen\Rollbar\RollbarLogger::setUser($this->getUser());
 * </code>
 */

class RollbarLogger extends \Tracy\Logger
{		
	/** @var boolean */
	private $ignoreNotice;
	
	/**
	 * Register logger to \Tracy\Debugger
	 * @param array $config
	 * @param boolean $ignoreNotice
	 */
	public static function register($config, $ignoreNotice = false) {	
		$this->ignoreNotice = $ignoreNotice;
		
		if($config['sendErrors']) {
			unset($config['sendErrors']);
			Rollbar::init($config, FALSE, FALSE);
			
			$logger = new self();
			$logger->directory = & TDebugger::$logDirectory;
			$logger->email = & TDebugger::$email;
			$logger->mailer = & TDebugger::$mailer;
			$logger->emailSnooze = & TDebugger::$emailSnooze;
			
			TDebugger::setLogger($logger);
		}
	}
	
	/**
	 * Wrapper for log function
	 * @param array $message
	 * @param string $priority
	 * @return bool
	 */
    public function log($message, $priority = NULL) {
		if($this->ignoreNotice && (strpos($message, 'PHP Notice') !== false)) {
			return true;
		}
		
		$response = parent::log($message, $priority);

        if ($priority == TDebugger::ERROR) {
            Rollbar::report_message($message[1]);
        }
		
		return $response;
	}
	
	/*************************** NETTE HELP FUNCTION **************************/
	
	/**
	 * Set nette user to logger
	 * @param \Nette\Security\User $user
	 * @param string $usernameCol			username column name to get from identity
	 * @param string $emailCol				email column name to get from identity
	 */
	public static function setUser(\Nette\Security\User $user, $usernameCol = 'username', $emailCol = 'email') {
		\Rollbar::$instance->person = self::getRollbarUser($user, $usernameCol, $emailCol);
	}
	
	/**
	 * Set rollbar user from given nette user
	 * @param \Nette\Security\User $user
	 * @param string $usernameCol
	 * @param string $emailCol
	 * @return array
	 */
	private static function getRollbarUser(\Nette\Security\User $user, $usernameCol = 'username', $emailCol = 'email') {
		if(!$user->isLoggedIn()) {
			return array(
				'id' => 'unlogged'
			);
		}			
			
		$userData = $user->getIdentity()->getData();
		return array(
			'id' => $user->getId(),
			'username' => $userData[$usernameCol],
			'email' => $userData[$emailCol]
		);
	}
}