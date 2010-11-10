<?php
/**
 * Couier class
 *
 * @package             Framework
 */

require_once('Machine.php');

/**
 * Courier management class.
 *
 * @author              Marco Ceppi <marco.ceppi@seacrow.org>
 * @since               November 9, 2010
 * @package             Framework
 * @subpackage          Users
 */
class Courier
{
	function Courier()
	{
		// Not sure if we're going to construct yet...
	}
	
	/**
	 * Users Method
	 * 
	 * @return array|false List of users
	 */
	public static function accounts()
	{
		$users = array();
		
		if( !$data_array = file('/etc/passwd') )
		{
			return false;
		}
		
		foreach( $data_array as $line )
		{
			list($user, $opts) = explode("\t", $line);
			
			$tmp = array();
			$opts = explode('|', $opts);
			
			foreach( $opts as $opt ) 
			{
				list($key, $val) = explode('=', $opt);
				
				$tmp[$key] = $val;
			}
			
			$users[$user] = $tmp;
		}
		
		return $users;
	}
	
	/**
	 * User Method
	 * 
	 * @param string $username
	 * 
	 * @return array|false List of user details
	 */
	public static function account( $username )
	{
		$users = self::accounts();
		
		if( array_key_exists($username, $users) )
		{
			return $users[$username];
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Rebuild Courier User Databse
	 * 
	 * @return bool
	 */
	public static function makeUserdb()
	{
		system('makeuserdb', $status);
		
		return ( $status > 0 ) ? false : true;
	}

	/**
	 * Create maildir Directory
	 * 
	 * @param string $dir Directory to create as maildir
	 * 
	 * @return bool
	 */
	public static function makeMailDir( $dir )
	{
		system("maildirmake $dir", $status);
		
		return ( $status > 0 ) ? false : true;
	}
	
	/**
	 * Remove Courier mail account
	 * 
	 * @param string $username Email user to remove from Courier Database
	 * 
	 * @return bool
	 */
	public static function removeAccount( $username )
	{
		if( !$userdata = self::account($username) )
		{
			return false;
		}
		
		Machine::rm($userdata['home'], true);
		
		system("userdb $email del", $status);
		
		self::makeUserdb();
		
		return ( $status > 0 ) ? false : true;
	}
	
	/**
	 * Add Courier mail account
	 * 
	 * @param string $email Email account (username) to add
	 * @param string $home Path for home
	 * @param string $mail Maildir directory
	 * @param int $mail_uid System uid for Mail user
	 * @param int $mail_gid System gid for Mail user
	 * 
	 * @return bool
	 */
	public static function addAccount( $email, $home, $mail, $mail_uid = NULL, $mail_gid = NULL )
	{
		if( is_null($mail_uid) || is_null($mail_gid) )
		{
			$userdata = Machine::user('mail');
			
			$mail_uid = (is_null($mail_uid)) ? $userdata['uid'] : $mail_uid;
			$mail_gid = (is_null($mail_gid)) ? $userdata['gid'] : $mail_gid;
		}
		
		if( !is_dir($home) )
		{
			if( !mkdir( $home ) )
			{
				return false;
			}
		}
		else
		{
			return false;
		}
		
		self::makeMailDir($mail);
		Machine::chown($home, $mail_uid, $mail_gid, true);

		system("userdb $email set uid=$mail_uid gid=$mail_gid home=$home mail=$mail", $status);
		
		if( $status > 0 )
		{
			//Whoops. We've got an issue. Backup slowly.
			self::removeAccount($email);
			
			return false;
		}
		else
		{
			self::makeUserdb();
			
			return true;
		}
	}
	
	public static function addForwarder($from, $to)
	{
		
	}
}
?>