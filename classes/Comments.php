<?php

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
