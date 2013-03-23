<?php

if (!User::isLoggedIn()) {
	$app->render("login.html");
	die();
}

$key = "me";
$error = "";

if(isset($req))
	$key = $req;

if($_POST)
{
    //var_dump($_POST);
	// Post is just generic, we'll figure out what the user wants, based on what is set
	$keyid = "";
	$vcode = "";
	$label = null;
	$theme = "";
	$viewtheme = "";
	$deletekeyid = "";
    $deleteentity = "";
	$orgpw = "";
	$password = "";
	$password2 = "";
	$defaultcommentcharacter = "";
	$timeago = "";
	$entity = "";
	$entitytype = "";
	$ddcombine = "";
    $deleteentityid = "";
    $deleteentitytype = "";
    $domainname = "";
    $domainentity = "";
    $domainentitytype = "";

    if(isset($_POST["domainname"]))
        $domainname = $_POST["domainname"];
    if(isset($_POST["domainentity"]))
        $domainentity = $_POST["domainentity"];
    if(isset($_POST["domainentitytype"]))
        $domainentitytype = $_POST["domainentitytype"];
	if(isset($_POST["keyid"]))
		$keyid = trim($_POST["keyid"]);
	if(isset($_POST["vcode"]))
		$vcode = trim($_POST["vcode"]);
	if(isset($_POST["label"]))
		$label = $_POST["label"];
	if(isset($_POST["viewtheme"]))
		$viewtheme = $_POST["viewtheme"];
	if(isset($_POST["theme"]))
		$theme = $_POST["theme"];
	if(isset($_POST["deletekeyid"]))
		$deletekeyid = $_POST["deletekeyid"];
	if(isset($_POST["deleteentity"]))
		$deleteentity = $_POST["deleteentity"];
	if(isset($_POST["orgpw"]))
		$orgpw = $_POST["orgpw"];
	if(isset($_POST["password"]))
		$password = $_POST["password"];
	if(isset($_POST["password2"]))
		$password2 = $_POST["password2"];
	if(isset($_POST["defaultcommentcharacter"]))
		$defaultcommentcharacter = $_POST["defaultcommentcharacter"];
	if(isset($_POST["timeago"]))
		$timeago = $_POST["timeago"];
	if(isset($_POST["addentitybox"]))
		$entity = $_POST["addentitybox"];
	if(isset($_POST["entitytype"]))
		$entitytype = $_POST["entitytype"];
	if(isset($_POST["ddcombine"]))
		$ddcombine = $_POST["ddcombine"];
    if(isset($_POST["deleteentityid"]))
        $deleteentityid = $_POST["deleteentityid"];
    if(isset($_POST["deleteentitytype"]))
        $deleteentitytype = $_POST["deleteentitytype"];
        
	// Apikey stuff
	if($keyid || $vcode)
	{
		$check = Api::checkAPI($keyid, $vcode);
		if($check == "success")
		{
			$error = Api::addKey($keyid, $vcode, $label);
		}
		else
		{
			$error = $check;
		}
	}

	// Delete an apikey
	if($deletekeyid && !$deleteentity)
	{
		$error = Api::deleteKey($deletekeyid);
	}

    if($deletekeyid && $deleteentity)
    {
        $error = Domains::deleteEntity($deletekeyid, $deleteentity);
    }
    
	// Theme stuff
	if($viewtheme)
	{
		UserConfig::set("viewtheme", $viewtheme);
		$app->redirect($_SERVER["REQUEST_URI"]);
	}
	if($theme)
	{
		UserConfig::set("theme", $theme);
		$error = "Theme has been set";
	}
	
	// Password
	if($orgpw && $password && $password2)
	{
		if($password != $password2)
			$error = "Passwords don't match, try again";
		elseif(Password::checkPassword($orgpw) == true)
		{
			Password::updatePassword($password);
			$error = "Password updated";
		}
		else
			$error = "Original password is wrong, please try again";
	}

	// Default comment character
	if($defaultcommentcharacter > 0) 
	{
		UserConfig::set("defaultCommentCharacter", $defaultcommentcharacter);
		$error = "Default Character has been set to $defaultcommentcharacter";
	}

	if($timeago)
	{
		UserConfig::set("timeago", $timeago);
		$error = "Timeago has been set to $timeago";
	}

    if($deleteentityid && $deleteentitytype)
    {
        $q = UserConfig::get($deleteentitytype);
        foreach($q as $k => $ent)
        {
            if($ent["id"] == $deleteentityid)
            {
                unset($q[$k]);
                $error = $ent["name"]." has been removed";
            }
        }
        UserConfig::set($deleteentitytype, $q);
    }
    
	if($entity && $entitytype)
	{
		if($entitytype == "character")
			$query = "SELECT characterID AS id, name FROM zz_characters WHERE name = :name";
		if($entitytype == "corporation")
			$query = "SELECT corporationID AS id, name FROM zz_corporations WHERE name = :name AND memberCount > 0";
		if($entitytype == "alliance")
			$query = "SELECT allianceID AS id, name FROM zz_alliances WHERE name = :name AND memberCount > 0";
		if($entitytype == "faction")
			$query = "SELECT factionID AS id, name FROM zz_factions WHERE name = :name";
		if($entitytype == "ship")
			$query = "SELECT typeID as id, typeName AS name FROM ccp_invTypes WHERE typeName = :name";
		if($entitytype == "system")
			$query = "SELECT solarSystemID AS id, solarSystemName AS name FROM ccp_systems WHERE solarSystemName = :name";
		if($entitytype == "region")
			$query = "SELECT regionID AS id, regionName AS name FROM ccp_regions WHERE regionName = :name";

		$id = Db::query($query, array(":name" => $entity));
        if($id)
        {
            $q = UserConfig::get($entitytype);
            if(!empty($q))
            {
                if(in_array($id, $q))
                    $error = "$entity is already added";
                else
                {
                    $q[] = $id[0];
                    UserConfig::set($entitytype, $q);
                    $error = "$entity has been added";
                }
            }
            else
            {
                UserConfig::set($entitytype, $id);
                $error = "$entity has been added";
            }
        }
        else
            $error = "No entity found with that name";
	}

	if($ddcombine)
	{
		UserConfig::set("ddcombine", $ddcombine);
		$error = "Combine Dropped/Destroyed set to $ddcombine";
	}
    
    if($domainname && $domainentity && $domainentitytype)
    {
        $error = Domains::updateEntities($domainname, $domainentity, $domainentitytype);
    }
}
$entitytypes = array("character", "corporation", "alliance", "faction", "ship", "system", "region");
$entlist = array();
foreach($entitytypes as $ent)
{
	$result = UserConfig::get($ent);
	$a = array();
	if ($result != null) foreach($result as $row) {
        if($ent == "system")
        {
            $row["solarSystemID"] = $row["id"];
            $row["solarSystemName"] = $row["name"];
            $sunType = Db::queryField("SELECT sunTypeID FROM ccp_systems WHERE solarSystemID = :id", "sunTypeID", array(":id" => $row["id"]));
            $row["sunTypeID"] = $sunType;
        }
        elseif($ent == "ship")
        {
            $row["shipTypeID"] = $row["id"];
            $row["${ent}Name"] = $row["name"];
        }
        else
        {
            $row["${ent}ID"] = $row["id"];
            $row["${ent}Name"] = $row["name"];
        }
		$a[] = $row;
	}
	$entlist[$ent] = $a;
}

$userID = User::getUserID();
$data["domainentities"] = Domains::getUserEntities($userID);
$data["entities"] = $entlist;
$data["themes"] = array("default", "amelia", "cerulean", "cyborg", "journal", "readable", "simplex", "slate", "spacelab", "spruce", "superhero", "united");
$data["viewthemes"] = array("bootstrap", "edk");
$data["apiKeys"] = Api::getKeys($userID);
$data["apiChars"] = Api::getCharacters();
$charKeys = Api::getCharacterKeys();
$charKeys = Info::addInfo($charKeys);
$data["apiCharKeys"] = $charKeys;
$data["cmtChars"] = Api::getCharacters();
$data["cmtChars"][] = array("characterID" => 0, "characterName" => "Anonymous");
$data["userInfo"] = User::getUserInfo();
$data["currentTheme"] = UserConfig::get("theme", "default");
$data["timeago"] = UserConfig::get("timeago");
$data["ddcombine"] = UserConfig::get("ddcombine");

$app->render("account.html", array("data" => $data, "message" => $error, "key" => $key));
