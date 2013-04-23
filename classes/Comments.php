<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Comments
{
	public static function getPageComments($pageID)
	{
		$comments = Db::query("SELECT datePosted, characterID, theComment FROM zz_comments WHERE pageID = :id ORDER BY commentID",
													array(":id" => $pageID), 0);
		$comments = Info::addInfo($comments);
		return $comments;
	}

	public static function getPageCommentCount($pageID)
	{
		return Db::queryField("SELECT count(*) count FROM zz_comments WHERE pageID = :id", "count",
													array(":id" => $pageID), 0);
	}

	public static function addComment($comment, $characterID, $pageID)
	{
		$userID = User::getUserID();
		// Sanity check the user
		if ($userID == 0) throw new Exception("Must be logged in to comment!");

		// sanity check the comment itself
		$comment = trim($comment);
		if (strlen($comment) == 0) throw new Exception("Empty comments are not welcome here.");
		if (strlen($comment) > 512) throw new Exception("Comment is too large, keep it under 512 characters please.");

		// sanity check the character ID
		if ($characterID != 0) { // Not an anonymous post
			$exists = Db::queryField("select count(*) count from zz_characters where characterID = :id", "count",
															 array(":id" => $characterID));
			if ($exists === 0) throw new Exception("Who the hell is characterID $characterID ?");
		}

		// Add the comment
		Db::execute("insert into zz_comments (pageID, userID, characterID, theComment) values
				(:pageID, :userID, :characterID, :theComment)",
								array(":pageID" => $pageID, ":userID" => $userID,
										 ":characterID" => $characterID, ":theComment" => $comment));
		return true;
	}
}
