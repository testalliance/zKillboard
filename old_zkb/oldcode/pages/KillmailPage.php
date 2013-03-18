<?php

class KillmailPage extends Page
{

    function initialize(&$p, &$context)
    {
        parent::initialize($p, $context);

        $killmailIndex = array_search("killmail", $this->p);
        $killID = $killmailIndex < sizeof($this->p) - 1 ? $this->p[$killmailIndex + 1] : null;
        if (!is_numeric($killID)) $killID = null;
        if ($killID == null) die("Invalid killmail ID specified");

        $killDetail = getKillDetail($killID);
        $this->context['killDetail'] = $killDetail;
    }

    function controllerMidPane()
    {
    }

    function controllerLeftPane()
    {
        $killDetail = $this->context['killDetail'];
        $attackers = $killDetail['attackers'];
        $finalBlow = null;
        foreach ($attackers as $attacker) {
            if ($attacker['finalBlow'] == '1') {
                $finalBlow = $attacker;
            }
        }
        $this->context['finalBlow'] = $finalBlow;
        $topDamage = null;
        foreach ($attackers as $attacker) {
            if ($topDamage != null) break;
            if ($attacker['characterID'] == 0) continue;
            $topDamage = $attacker;
        }
        if ($topDamage != null) $this->context['topDamage'] = $topDamage;
    }

    function viewLeftPane($xml)
    {
    }

    function viewRightPane($xml)
    {
        global $disqus_id;

        echo "<span class='smallCorner menuSpan'><span class='title'>Navigation</span><span>";
        $killDetail = $this->context['killDetail'];
        $detail = $killDetail["detail"];
        $time = $detail['unix_timestamp'];
        $system = Info::getSystemName($detail['solarSystemID']);
        $time = $time - ($time % 3600);
        echo "<a href='/related/$system," . date("YmdH", $time) . "'>Related</a><br/>";
        echo "</span></span>";

        displayTopDogs("Final Blow", "pilot", "characterID", array($this->context['finalBlow']));
        if (isset($this->context['topDamage'])) displayTopDogs("Top Damage", "pilot", "characterID", array($this->context['topDamage']));

        if (isset($disqus_id) && $disqus_id != "") {
            echo "<span class='typeHeader typeHeaderSmall'>Comments</span>";
            echo "<div class='smallCorner comments'>"; // comments

            echo "<div id=\"disqus_thread\"></div>
<script type=\"text/javascript\">
    var disqus_shortname = '$disqus_id';
    (function() {
        var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
        dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';
        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
    })();
</script>
<noscript>Please enable JavaScript to view the <a href=\"http://disqus.com/?ref_noscript\">comments powered by Disqus.</a></noscript>
<a href=\"http://disqus.com\" class=\"dsq-brlink\">blog comments powered by <span class=\"logo-disqus\">Disqus</span></a>
";

            echo "</div>"; // / comments
        }
    }

    function viewMidPane($xml)
    {
        $context = $this->context;

        $killDetail = $context['killDetail'];
        $detail = $killDetail["detail"];
        $victim = $killDetail['victim'];
        $attackers = $killDetail['attackers'];
        $items = $killDetail['items'];
        $killID = $detail['killID'];

        echo "<div class='leftHalf involvedParties'>";
        echo "<div class='killmailVictimInfo smallCorner'>\n";
        displayPilotDetails($detail, $victim, true);
        echo "</div>";
        echo "<span class='typeHeader typeHeaderSmall'>Involved Pilots</span>";

        echo "<div class='killmailAttackers'>";
        foreach ($attackers as $attacker) {
            echo "<div class='killmailAttackerInfo smallCorner'>\n";
            displayPilotDetails($detail, $attacker);
            echo "</div>";
        }
        echo "</div>";

        echo "</div>"; // Involved parties

        echo "<div class='rightHalf shipDetails'>";
        echo "<div class='killmailShipDisplay'>";
        displayShip($victim['shipTypeID'], $items);
        echo "</div><br/>";

		        global $infernoFlags;

        $infernoKill = false;
        foreach ($items as $item) {
                if ($infernoKill) continue;
                $itemFlag = $item['flag'];
                foreach ($infernoFlags as $flagID=>$values) {
                        $infernoKill |= ($itemFlag >= $values[0] && $itemFlag <= $values[1]);
                }
        }


		$firstHeader = true;
        $totalDroppedValue = 0;
        $totalDestroyedValue = 0;
		$itemFlag = null;
		$lastSlot = null;
        echo "<div class='smallCorner'><table class='itemsTable' border='1'>";
        echo "<tr><th/><th>&nbsp;</th><th colspan='2'>Dropped</th><th colspan='2'>Destroyed</th></tr>";
        foreach ($items as $item) {
			$flag = $item['flag'];
            $typeID = $item['typeID'];
			$singleton = $item['singleton'];
            $itemName = Info::getItemName($item['typeID']);
            $price = $item['price'];
            $dropped = $item['qtyDropped'];
            $destroyed = $item['qtyDestroyed'];
            $droppedPrice = ($dropped * $price);
            $destroyedPrice = ($destroyed * $price);
            $totalDroppedValue += $droppedPrice;
            $totalDestroyedValue += $destroyedPrice;
			global $infernoFlags, $effectToSlot;
			$infernoBays = array(90=>"Ship Bay", 154=>"Quafe Bay", 133=>"Fuel Bay", 134=>"Ore Hold",135=>"Gas Hold",136=>"Mineral Hold",137=>"Salvage Hold",
									138=>"Ship Bay", 148=>"Command Center Hold", 149=>"Planetary Commodities Hold", 151=>"Material Bay");
			if ($itemFlag !== $flag) {
				$itemFlag = $flag;
				//echo "<tr>";
				switch ($itemFlag) {
					case 0:
        				echo ($infernoKill ? "<th/><th>Corporate Hangar</th>"  :"<th/><th>Fitted</th>");
					break;
					case 5:
        				echo ($infernoKill ? "<tr><th/><th>Cargo</th></tr>" : "<tr><th/><th>Cargo</th></tr>");
					break;
					case 87:
        				echo "<th/><th>Drones</th>";
					break;
					case 89:
        				echo "<th/><th>Implants</th>";
					break;
					default:
						if (isset($infernoBays[$itemFlag])) {
							echo "<th/><th>" . $infernoBays[$itemFlag] . "</th>";
						} else {
						$found = false;
						foreach($infernoFlags as $slotType=>$flagTypes) {
							if ($itemFlag >= $flagTypes[0] && $itemFlag <= $flagTypes[1]) {
								$effectiveSlot = $effectToSlot["$slotType"];
								if ($effectiveSlot != $lastSlot) echo "<th/><th>$effectiveSlot</th>";
								$found = true;
								$lastSlot = $effectiveSlot;
							}
						}
        				if (!$found) echo "<tr><th/><th>Flag $itemFlag</th></tr>";
						}
				}
				if ($firstHeader) echo "<tr><th colspan='2'><th>Qty</th><th>Value</th><th>Qty</th><th>Value</th></tr>";
				$firstHeader = false;
			}
			if ($singleton == 2) $itemName .= " Copy";
            echo "
    <tr align='right'>
        <td>";
            eveImageLink($typeID, "item", $itemName, false, 32);
            echo "</td>
        <td align='left'>$itemName</td>
        <td class='itemDropped'>", ($dropped == 0 ? "" : number_format($dropped)), "</td>
        <td class='itemDropped'>", ($dropped > 0 && $droppedPrice == 0 ? "?" : formatIskPrice($droppedPrice)), "</td>
        <td class='itemDestroyed'>", ($destroyed == 0 ? "" : number_format($destroyed)), "</td>
        <td class='itemDestroyed'>", ($destroyed > 0 && $destroyedPrice == 0 ? "?" : formatIskPrice($destroyedPrice)), "</td>
    </tr>";
        }
        $shipTypeID = $victim['shipTypeID'];
        $shipName = Info::getItemName($shipTypeID);
        echo "
    <tr align='right'>
        <td>";
        eveImageLink($shipTypeID, "ship", $shipName, false, 32);
        echo "</td>
        <td colspan='4' align='left'>$shipName</td>
        <td class='itemDestroyed'>", formatIskPrice($victim['shipPrice']), "</td>
    </tr>";
        $totalDestroyedValue += $victim['shipPrice'];
        echo "
    <tr align='right'>
        <td/><td>Total: </td>
        <td colspan='2' class='itemDropped'>", formatIskPrice($totalDroppedValue), "</td><td colspan='2' class='itemDestroyed'>", formatIskPrice($totalDestroyedValue), "</td>
    </tr>
    <tr align='right' class='bold'>
        <td/><td>Grand Total: </td><td align='center' colspan='4'>", formatIskPrice($totalDestroyedValue + $totalDroppedValue), "</td>
    </tr>";
        echo "</table></div>";
        echo "</div>"; // rightHalf

    }
}
