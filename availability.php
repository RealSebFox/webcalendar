<?php
/* $Id$ 
 * Page Description:
 * Display a timebar view of a single day.
 *
 * Input Parameters:
 * month (*) - specify the starting month of the timebar
 * day (*) - specify the starting day of the timebar
 * year (*) - specify the starting year of the timebar
 * users (*) - csv of users to include
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in
 *   System Settings unless the user is an admin user ($is_admin).
 */

include_once 'includes/init.php';

// Don't allow users to use this feature if "allow view others" is
// disabled.
if ( $ALLOW_VIEW_OTHER == 'N' && ! $is_admin ) {
  // not allowed...
  exit;
}

// input args in URL
// users: list of comma-separated users
if ( empty ( $users ) ) {
  echo 'Program Error: No users specified!'; exit;
} else if ( empty ( $year ) ) {
  echo 'Program Error: No year specified!'; exit;
} else if ( empty ( $month ) ) {
  echo 'Program Error: No month specified!'; exit;
} else if ( empty ( $day ) ) {
  echo 'Program Error: No day specified!'; exit;
}

$parent_form = getGetValue ('form');

$INC = array ( "js/availability.php/false/$month/$day/$year/$parent_form" );
print_header($INC, '', 'onload="focus();"', true, false, true );

$span = ($WORK_DAY_END_HOUR - $WORK_DAY_START_HOUR) * 3 + 1;
$time = mktime(0,0,0,$month,$day,$year);
$date = date ( 'Ymd', $time );
$wday = strftime ( "%w", $time );
$base_url = "?users=$users";
$prev_url = $base_url . strftime('&amp;year=%Y&amp;month=%m&amp;day=%d', $time - ONE_DAY);
$next_url = $base_url . strftime('&amp;year=%Y&amp;month=%m&amp;day=%d', $time + ONE_DAY);

$users = explode(',',$users);
?>

<div style="width:99%;">
<a title="<?php etranslate( 'Previous' )?>" class="prev" href="<?php echo $prev_url ?>"><img src="images/leftarrow.gif" class="prevnext" alt="<?php etranslate( 'Previous' )?>" /></a>
<a title="<?php etranslate( 'Next' )?>" class="next" href="<?php echo $next_url ?>"><img src="images/rightarrow.gif" class="prevnext" alt="<?php etranslate( 'Next' )?>" /></a>
<div class="title">
<span class="date"><?php 
  printf ( "%s, %s %d, %d", weekday_name ( $wday ), month_name ( $month - 1 ), $day, $year ); 
?></span><br />
</div></div>
<br />

<form action="availability.php" method="post">
<?php echo daily_matrix($date,$users); ?>
</form>

<?php echo print_trailer ( false, true, true ); ?>

