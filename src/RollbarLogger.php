<?php

namespace Kelemen\Rollbar;

use Rollbar;
use \Tracy\Debugger as TDebugger;

/**
 * Logger wit rollbar connect for Tracy.
 * Usage:
 * <code>
 * \Kelemen\Rollbar\RollbarLogger::register($container->parameters['rollbar']);
 * </code>
 */

class RollbarLogger extends \Tracy\Logger
{		
	/**
	 * Register logger to \Tracy\Debugger
	 * @param array $config
	 */
	public static function register($config) {			
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
	 * @param string $message
	 * @param string $priority
	 * @return bool
	 */
    public function log($message, $priority = NULL) {
		$response = parent::log($message, $priority);

        if ($priority == TDebugger::ERROR) {
            Rollbar::report_message($message[1]);
        }
		
		return $response;
	}
}