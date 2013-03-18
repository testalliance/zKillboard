<?php

$chartID = (int) $chartID;
if ($chartID == 0) throw new Exception("Invalid chart ID");

$chartURL = Chart::buildChart($chartID);
$app->redirect($chartURL);
