<?php

namespace Polyfony\Security;

// framework internals
use Polyfony\Security as Security;
use Polyfony\Config as Config;
use Polyfony\Logger as Logger;
use Polyfony\Store\Cookie as Cookie;

// models
use \Models\{
	Accounts as ExtendedAccounts,
	AccountsSessions,
	AccountsLogins,
	AccountsPermissions,
	AccountsPermissionsAssigned,
	AccountsRolesAssigned,
	AccountsRoles
};

class Accounts extends \Polyfony\Entity {

	public static function getByLogin(
		string $posted_login
	) :?Accounts {
		return ExtendedAccounts::_select()
			->first()
			->where([
				'login'		=> $posted_login,
				'is_enabled'=> 1
			])
			->execute();
	}

	public static function getBySession(
		string $signature
	) :?Accounts {
		return ExtendedAccounts::_select([
				'Accounts.*'
			])
			->join(
				'AccountsSessions', 
				'AccountsSessions.id_account', 
				'Accounts.id'
			)
			->object('Accounts')
			->first()
			->where([
				'AccountsSessions.signature'	=>$signature,
				'Accounts.is_enabled'	=>1
			])
			->whereGreaterThan([
				'AccountsSessions.is_expiring_on'=>time()
			])
			->execute();
	}

	public function getSession() :AccountsSessions {

		return AccountsSessions::_select()
			->first()
			->where([
				'id_account'=>$this->get('id')
			])
			->execute();

	}

	public function disconnect() :bool {

		return 
			$this->terminateSession() && 
			$this->terminateCookie();

	}

	private function terminateSession() :bool {

		return $this
			->getSession()
			->delete();
			
	}

	private function terminateCookie() :bool {

		return !Cookie::has(Config::get('security', 'cookie')) ? true : 
			Cookie::remove(
				Config::get('security', 'cookie')
			);

	}

	public function hasExpired() :bool {
		return 
			$this->get('is_expiring_on') && 
			time() > $this->get('is_expiring_on',true);
	}

	public function login() :bool {

		// generate the expiration date
		$session_expiration = 
			time() + 
			( 
				Config::get('security', 'session_duration') * 
				3600 
			);
		
		// generate a session key with its expiration, 
		// the login, the password, the ip, the user agent
		$session_signature = Security::getSignature(
			$this->get('login', true).
			$this->get('password', true).
			$session_expiration
		);

		// if we manage to open the session properly
		return 
			// if the cookie creation went right
			$this->createCookieSession($session_signature) && 
			// and the account record updating went right too
			$this->createDatabaseSession(
				$session_signature, 
				$session_expiration
			);

	}

	public function isBeingForcedFrom(
		string $originating_from
	) :bool {
		// this new method prevents DDoS
		// since only the bruteforcer gets blocked, the actual user
		return 
			AccountsLogins::_select([
				'count'=>'id'
			])
			->where([
				'id_account'		=>$this->get('id'),
				'has_failed'		=>1,
				'originating_from'	=>$originating_from
			])
			->whereGreaterThan([
				'creation_date' => 
					time() - 
					Config::get(
						'security', 
						'forcing_timeframe'
					)
			])
			->first()
			->execute()
			->get('count_id') > Config::get(
				'security', 
				'forcing_maximum_attempts'
			);

	}

	// first part of the session opening process
	private function createCookieSession(
		string $session_signature
	) :bool {

		// store a cookie with our current session key in it
		return Cookie::put(
			Config::get('security', 'cookie'), 
			$session_signature, 
			true, 
			Config::get('security', 'session_duration')
		);

	}

	// second part of the session opening process
	private function createDatabaseSession(
		string $session_signature,
		int $expiration_date
	) :bool {

		// remove any remaining session
		AccountsSessions::_delete()
			->where(['id_account'=>$this->get('id')])
			->execute();

		// open a new session
		return (new AccountsSessions)->set([
			'id_account'		=> $this->get('id'),
			'is_expiring_on'	=> $expiration_date,
			'signature'			=> $session_signature,
		])->save();

	}

	public function logSuccessfulLogin() :bool {

		// log the loggin action
		Logger::info("Account {$this->get('login')} has logged in");

		// also insert in a table
		return (new AccountsLogins)
			->set([
				'originating_from'	=>Security::getSafeRemoteAddress(),
				'has_succeeded'		=>1,
				'id_account'		=>$this->get('id')
			])
			->save();
	}

	public function logFailedLogin(
		?string $reason = ''
	) :bool {

		// log the loggin action
		Logger::warning(
			"Account {$this->get('login')} has failed to log in" . 
			($reason ? " ($reason)" : '')
		);

		// also insert in a table
		return (new AccountsLogins)
			->set([
				'creation_date'		=>time(),
				'originating_from'	=>Security::getSafeRemoteAddress(),
				'has_failed'		=>1,
				'id_account'		=>$this->get('id')
			])
			->save();
	}

	public function setPassword(
		string $plaintext_password
	) :self {
		return $this->set([
			'password'=>Security::getPassword($plaintext_password)
		]);
	}

	public function hasThisPassword(
		string $uncertain_password
	) :bool {
		// compare the existing signature, with the signature of the password to check
		return 
			$this->get('password') === 
			Security::getPassword($uncertain_password);

	}

	public function getRoles() :array {

		// maybe		
		// cache roles in ->_['roles']			

		$roles = AccountsRoles::_select(['AccountsRoles.*'])
			->join(
				'AccountsRolesAssigned',
				'AccountsRolesAssigned.id_role',
				'AccountsRoles.id'
			)
			->where(['AccountsRolesAssigned.id_account'=>$this->get('id')])
			->execute();

		return $roles;
	}

	public function hasRole(
		// PHP8 typing : string | int | AccountsRoles
		$role_id_or_name_or_object
	) :bool {
		$role = AccountsRoles::getFromMixed(
			$role_id_or_name_or_object
		);
		
		return in_array(
			$role, 
			$this->getRoles()
		);
	}

	public function getPermissions() :array {

		// if we have cached permissions in ->_['permissions']
		if(isset($this->_['permissions'])) {
			return $this->_['permissions'];
		}

		// directly assigned permissions
		$directly_assigned_permissions = AccountsPermissions::_select([
				'AccountsPermissions.*'
			])
			->join(
				'AccountsPermissionsAssigned',
				'AccountsPermissionsAssigned.id_permission',
				'AccountsPermissions.id'
			)
			->where(['AccountsPermissionsAssigned.id_account'=>$this->get('id')])
			->execute();

		// inherited permissions
		$inherited_permissions = AccountsPermissions::_select([
				'AccountsPermissions.*',
				// leave a trace of where that permission comes from
				// to allow for better end user understanding of their permissions
				// and inheritence of permissions
				'AccountsRolesAssigned.id_role'
			])
			// get permissions that are actually assigned
			->join(
				'AccountsPermissionsAssigned',
				'AccountsPermissions.id',
				'AccountsPermissionsAssigned.id_permission'
			)
			// get permissions that are assigned to a role
			->join(
				'AccountsRolesAssigned',
				'AccountsRolesAssigned.id_role',
				'AccountsPermissionsAssigned.id_role'
			)
			// restrict to roles that are assigned to that account
			->where(['AccountsRolesAssigned.id_account'=>$this->get('id')])
			->execute();

		// deduplicate
		$permissions = array_unique(array_merge(
			$directly_assigned_permissions, 
			$inherited_permissions
		));

		// cache the permissions for later
		$this->_['permissions'] = $permissions;
		// return those
		return $permissions;
	}

	public function hasPermission(
		// PHP8 typing : string | int | AccountsPermissions
		$permission_id_or_name_or_object
	) :bool {
		
		$permission = AccountsPermissions::getFromMixed(
			$permission_id_or_name_or_object
		);

		return in_array(
			$permission,
			$this->getPermissions()
		);

	}

}

?>
