<?php

class Chart
{
	public static function addChart($type, $title, $dataset)
	{
		if (!is_array($dataset)) throw new Exception("Parameter 2, dataset, must be an array");
		$json = json_encode($dataset);
		$chartID = Db::queryField("select chartID from zz_charts where dataset = :dataset", "chartID", array(":dataset" => $json), 0);
		if ($chartID === null) {
			Db::execute("insert into zz_charts (type, title, dataset) values (:type, :title, :dataset)",
									array(":type" => $type, ":title" => $title, ":dataset" => $json));
			$chartID = Db::queryField("SELECT LAST_INSERT_ID()", "LAST_INSERT_ID()", array(), 0);
		}
		global $baseDir;
		//if (file_exists("$baseDir/public/img/charts/chart{$chartID}.png")) return "/img/charts/chart{$chartID}.png";
		return "/chart/$chartID/";
	}

	public static function buildChart($chartID)
	{
		$row = Db::queryRow("select type, title, dataset from zz_charts where chartID = :chartID", array(":chartID" => $chartID));
		$type = $row["type"];
		$title = $row["title"];
		$json = $row["dataset"];
		$dataset = json_decode($json, true);
		unset($row);
		unset($json);

		switch ($type) {
			case "KillLossChart":
				return self::buildKillLossChart($chartID, $title, $dataset);
		}
		die();
	}

	private static function buildKillLossChart($chartID, $title, $dataset)
	{
		global $baseDir;
		$pChart = $baseDir . "/vendor/pChart2/";

		if (file_exists("$baseDir/public/img/charts/chart{$chartID}.png")) return "/img/charts/chart{$chartID}.png";

		// Standard inclusions
		include("$pChart/class/pData.class.php");
		include("$pChart/class/pDraw.class.php");
		include("$pChart/class/pImage.class.php");

		// Dataset definition
		$indexes = array();
		$kills = array();
		$losses = array();
		$labels = array();
		$lastYear = 0;
		$maxValue = 0;
		foreach ($dataset as $values) {
			$kills[] = $values["kills"];
			$losses[] = $values["losses"];
			$maxValue = max($maxValue, $values["kills"]);
			$maxValue = max($maxValue, $values["losses"]);
			$labels[] = Util::getMonth($month);
		}
		$MyData = new pData();
		$MyData->addPoints($kills, "Kills");
		$MyData->addPoints($losses, "Losses");
		$MyData->addPoints($labels, "Labels");
		$MyData->setAbscissa("Labels");

		$MyData->setPalette("Kills", array("R" => 2, "G" => 77, "B" => 2, "Alpha" => 100));
		$MyData->setPalette("Losses", array("R" => 88, "G" => 11, "B" => 11, "Alpha" => 100));

		/* Create the pChart object */
		$myPicture = new pImage(700, 230, $MyData);

		/* Create a solid background */
		$Settings = array("R" => 179, "G" => 217, "B" => 91, "Dash" => 1, "DashR" => 199, "DashG" => 237, "DashB" => 111);
		$myPicture->drawFilledRectangle(0, 0, 700, 230, $Settings);

		/* Do a gradient overlay */
		$Settings = array("StartR" => 194, "StartG" => 231, "StartB" => 44, "EndR" => 43, "EndG" => 107, "EndB" => 58, "Alpha" => 75);
		$myPicture->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $Settings);
		$myPicture->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, array("StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 100));

		/* Add a border to the picture */
		$myPicture->drawRectangle(0, 0, 699, 229, array("R" => 0, "G" => 0, "B" => 0));

		/* Write the picture title */
		$myPicture->setFontProperties(array("FontName" => "$pChart/fonts/verdana.ttf", "FontSize" => 7));
		//$myPicture->drawText(10,13, $title, array("R"=>255,"G"=>255,"B"=>255));

		/* Draw the scale */
		$myPicture->setFontProperties(array("FontName" => "$pChart/fonts/Forgotte.ttf", "FontSize" => 11));
		$myPicture->setGraphArea(50, 60, 670, 190);
		$myPicture->drawFilledRectangle(50, 60, 670, 190, array("R" => 255, "G" => 255, "B" => 255, "Surrounding" => -200, "Alpha" => 10));
		//$myPicture->drawScale(array("CycleBackground"=>TRUE));
		$AxisBoundaries = array(0 => array("Min" => 0, "Max" => ($maxValue)));
		$ScaleSettings = array("Mode" => SCALE_MODE_MANUAL, "ManualScale" => $AxisBoundaries, "DrawSubTicks" => TRUE, "DrawArrows" => TRUE, "ArrowSize" => 6);
		$myPicture->drawScale($ScaleSettings);

		/* Graph title */
		$myPicture->setShadow(TRUE, array("X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10));
		$myPicture->drawText(55, 52, "$title", array("FontSize" => 20, "Align" => TEXT_ALIGN_BOTTOMLEFT));

		/* Draw the bar chart chart */
		$myPicture->setFontProperties(array("FontName" => "$pChart/fonts/pf_arma_five.ttf", "FontSize" => 6));
		//$MyData->setSerieDrawable("Losses",false);
		$myPicture->drawBarChart(array("DisplayValues" => TRUE, "DisplayColor" => DISPLAY_AUTO, "Surrounding" => 60));
		/* Draw the line and plot chart */
		//$myPicture->drawSplineChart();
		//$myPicture->drawPlotChart();

		/* Make sure all series are drawable before writing the scale */
		//$MyData->setSerieDrawable("Kills",TRUE);

		/* Write the legend */
		$myPicture->drawLegend(540, 35, array("Style" => LEGEND_ROUND, "Alpha" => 20, "Mode" => LEGEND_HORIZONTAL));

		/* Render the picture (choose the best way) */
		$myPicture->render("$baseDir/public/img/charts/chart{$chartID}.png");

		return "/img/charts/chart{$chartID}.png";
	}
}
