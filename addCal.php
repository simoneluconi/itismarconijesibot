<?php
function squarecandy_add_to_gcal(
  $name,
  $startdate,
  $enddate = false,
  $description = false,
  $location = "Istituto Tecnico Industriale Statale G. Marconi, Via Raffaello Sanzio, Jesi, AN, Italia",
  $allday = false
) {

   $startdate = date("d/m/Y H:i", strtotime($startdate));
   if ($enddate)
   {
      $enddate = date("d/m/Y H:i", strtotime($enddate));
   }
  // calculate the start and end dates, convert to ISO format
  if ($allday) {
    $startdate = date('Ymd',strtotime($startdate));
  }
  else {
    $startdate = date('Ymd\THis',strtotime($startdate));
  }

  if ($enddate && !empty($enddate) && strlen($enddate) > 2) {
    if ($allday) {
      $enddate = date('Ymd',strtotime($enddate . ' + 1 day'));
    }
    else {
      $enddate = date('Ymd\THis',strtotime($enddate));
    }
  }
  else {
    $enddate = date('Ymd\THis',strtotime($startdate . ' + 2 hours'));
  }

  // build the url
  $url = 'http://www.google.com/calendar/event?action=TEMPLATE';
  $url .= '&text=' . rawurlencode($name);
  $url .= '&dates=' . $startdate . '/' . $enddate;
  if ($description) {
    $url .= '&details=' . rawurlencode($description);
  }
  if ($location) {
    $url .= '&location=' . rawurlencode($location);
  }

  return $url;
}

/********************
 *
 *  Example Usage:
 *
 *  echo squarecandy_add_to_gcal('Example Event', '30/06/2017 8:00');
 *  echo squarecandy_add_to_gcal('Example Event', '30/06/2017 8:00', '2/07/2017 10:00', 'This is my detailed event description', 'Rome, Italy');
 *  echo squarecandy_add_to_gcal('Example Event', '30/06/2017', '2/07/2017', 'This is my detailed event description', 'Rome, Italy', true);
 *
 */
?>