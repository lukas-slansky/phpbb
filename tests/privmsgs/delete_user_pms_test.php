<?php
/**
*
* @package testing
* @copyright (c) 2011 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

require_once dirname(__FILE__) . '/../../phpBB/includes/functions_privmsgs.php';

class phpbb_privmsgs_delete_user_pms_test extends phpbb_database_test_case
{
	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__).'/fixtures/delete_user_pms.xml');
	}

	static public function delete_user_pms_data()
	{
		return array(
		//	array(
		//		(user we delete),
		//		array(remaining privmsgs ids),
		//		array(remaining privmsgs_to),
		//	),
			array(
				2,
				array(
					array('msg_id' => 1),
				),
				array(
					array('msg_id' => 1, 'user_id' => 3),
				),
			),
			array(
				3,
				array(
					array('msg_id' => 1),
					array('msg_id' => 2),
					array('msg_id' => 3),
					array('msg_id' => 5),
				),
				array(
					array('msg_id' => 1, 'user_id' => 2),
					array('msg_id' => 1, 'user_id' => 4),
					array('msg_id' => 2, 'user_id' => 2),
					array('msg_id' => 2, 'user_id' => 4),
					array('msg_id' => 3, 'user_id' => 2),
					array('msg_id' => 5, 'user_id' => 2),
					array('msg_id' => 5, 'user_id' => 4),
				),
			),
			array(
				5,
				array(
					array('msg_id' => 1),
					array('msg_id' => 2),
					array('msg_id' => 3),
					array('msg_id' => 4),
					array('msg_id' => 5),
				),
				array(
					array('msg_id' => 1, 'user_id' => 2),
					array('msg_id' => 1, 'user_id' => 3),
					array('msg_id' => 1, 'user_id' => 4),
					array('msg_id' => 2, 'user_id' => 2),
					array('msg_id' => 2, 'user_id' => 4),
					array('msg_id' => 3, 'user_id' => 2),
					array('msg_id' => 4, 'user_id' => 3),
					array('msg_id' => 5, 'user_id' => 2),
					array('msg_id' => 5, 'user_id' => 3),
					array('msg_id' => 5, 'user_id' => 4),
				),
			),
		);
	}

	/**
	* @dataProvider delete_user_pms_data
	*/
	public function test_delete_user_pms($delete_user, $remaining_privmsgs, $remaining_privmsgs_to)
	{
		global $db;

		$db = $this->new_dbal();

		phpbb_delete_user_pms($delete_user);

		$sql = 'SELECT msg_id
			FROM ' . PRIVMSGS_TABLE;
		$result = $db->sql_query($sql);

		$this->assertEquals($remaining_privmsgs, $db->sql_fetchrowset($result));

		$sql = 'SELECT msg_id, user_id
			FROM ' . PRIVMSGS_TO_TABLE;
		$result = $db->sql_query($sql);

		$this->assertEquals($remaining_privmsgs_to, $db->sql_fetchrowset($result));
	}
}
