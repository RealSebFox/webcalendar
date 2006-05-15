<?php
include_once 'includes/init.php';
load_user_categories();

$error = "";

if ( empty ( $id ) )
  $error = translate( 'Invalid entry id' ) . '.';
else if ( $CATEGORIES_ENABLED != 'Y' )
  $error = translate( 'You are not authorized' ) . '.';
else if ( empty ( $categories ) )
  $error = translate( 'You have not added any categories' ) . '.';

// make sure user is a participant
$res = dbi_execute ( "SELECT  cal_status FROM webcal_entry_user " .
  "WHERE cal_id = ? AND cal_login = ?" , array ( $id , $login ) );
if ( $res ) {
  if ( $row = dbi_fetch_row ( $res ) ) {
    if ( $row[0] == "D" ) // User deleted themself
      $error = translate( 'You are not authorized' ) . '.';
  } else {
    // not a participant for this event
    $error = translate( 'You are not authorized' ) . '.';
  }
  dbi_free_result ( $res );
} else {
  $error = translate( 'Database error' ) . ': ' . dbi_error ();
}
 
$cat_id = getPostValue ( 'cat_id' );
$cat_ids = array();
$cat_name = array();
$catNames = '';

//get user's categories for this event
$sql = "SELECT  DISTINCT cal_login, webcal_entry_categories.cat_id, " .
 " webcal_entry_categories.cat_owner, cat_name " .
 " FROM webcal_entry_user, webcal_entry_categories, webcal_categories " .
 " WHERE ( webcal_entry_user.cal_id = webcal_entry_categories.cal_id AND " .
 " webcal_entry_categories.cat_id = webcal_categories.cat_id AND " .
 " webcal_entry_user.cal_id = ? ) AND " . 
 " webcal_categories.cat_owner = ?".
 " ORDER BY webcal_entry_categories.cat_order";
$res = dbi_execute ( $sql , array ( $id , $login ) );
if ( $res ) {
 while ( $row = dbi_fetch_row ( $res ) ) {
   $cat_ids[] = $row[1];
   $cat_name[] = $row[3];    
 }
 dbi_free_result ( $res );
}
//get global categories
$globals_found = false;
$sql = "SELECT  webcal_entry_categories.cat_id, cat_name " .
  " FROM webcal_entry_categories, webcal_categories " .
  " WHERE webcal_entry_categories.cat_id = webcal_categories.cat_id AND " .
  " webcal_entry_categories.cal_id = ? AND " . 
  " webcal_categories.cat_owner IS NULL ";
$res = dbi_execute ( $sql , array ( $id ) );
if ( $res ) {
 while ( $row = dbi_fetch_row ( $res ) ) {
   $cat_ids[] = '-' .$row[0];
   $cat_name[] = $row[1] . '*';  
   $globals_found = true;  
 }
 dbi_free_result ( $res );
}
$catNames = $catList = '';
if ( ! empty ( $cat_name ) ) $catNames = implode(", " , array_unique($cat_name));
if ( ! empty ( $cat_ids ) ) $catList = implode(", ", array_unique($cat_ids));
// Get event name and make sure event exists
$event_name = "";
$res = dbi_execute ( "SELECT cal_name FROM webcal_entry " .
  "WHERE cal_id = ?" , array ( $id ) );
if ( $res ) {
  if ( $row = dbi_fetch_row ( $res ) ) {
    $event_name = $row[0];
  } else {
    // No such event
    $error = translate( 'Invalid entry id' ) . '.';
  }
  dbi_free_result ( $res );
} else {
  $error = translate( 'Database error' ) . ': ' . dbi_error ();
}

// If this is the form handler, then save now
if ( ! empty ( $cat_id ) && empty ( $error ) ) {
 dbi_execute ( "DELETE FROM webcal_entry_categories WHERE cal_id = ? " .
    "AND ( cat_owner = ? )" , array ( $id , $login ) );
 $categories = explode (",", $cat_id );

 $sql_params = array();
 $cnt = count( $categories );
 for ( $i =0; $i < $cnt; $i++ ) {
   //don't process Global Categories
   if ( $categories[$i] > 0 ) {
   $names = array();
   $values = array();
   $names[] = 'cal_id';
   $sql_params[]  = $id; 
   $values[]  = '?'; 
   $names[] = 'cat_id';
   $sql_params[]  = abs($categories[$i]);
   $values[]  = '?'; 
   $names[] = 'cat_order';
   $sql_params[]  = ($i +1);
   $values[]  = '?'; 
   $names[] = 'cat_owner';
   $sql_params[]  = $login; 
   $values[]  = '?'; 
   $sql = "INSERT INTO webcal_entry_categories ( " . implode ( ", ", $names ) .
     " ) VALUES ( " . implode ( ", ", $values ) . " )";
   } 
 }
 $view_type = 'view_entry';  
  
 if ( ! dbi_execute ( $sql , $sql_params ) ) {
    $error = translate ( 'Database error' ) . ': ' . dbi_error ();
  } else {
    $url = $view_type .".php?id=$id";
    if ( ! empty ( $date ) )
      $url .= "&amp;date=$date";
    do_redirect ( $url );
  }
}
$INC = array('js/set_entry_cat.php/true');
print_header($INC);

if ( ! empty ( $error ) ) { ?>
<h2><?php etranslate( 'Error' )?></h2>
<blockquote>
<?php echo $error; ?>
</blockquote>

<?php } else { ?>
<h2><?php etranslate( 'Set Category' )?></h2>

<form action="set_entry_cat.php" method="post" name="selectcategory">

<input type="hidden" name="date" value="<?php echo $date?>" />
<input type="hidden" name="id" value="<?php echo $id?>" />

<table border="0" cellpadding="5">
<tr style="vertical-align:top;"><td style="font-weight:bold;">
 <?php etranslate( 'Brief Description' )?>:</td><td>
 <?php echo $event_name; ?>
</td></tr>
     <tr><td class="tooltip" title="<?php etooltip( 'category-help' )?>" valign="top">
      <label for="entry_categories"><?php etranslate( 'Category' )?>:<br /></label>
   <input type="button" value="Edit" onclick="editCats(event)" /></td><td valign="top">
      <input  readonly=""type="text" name="catnames" 
     value="<?php echo $catNames ?>"  size="75" 
    onclick="alert('<?php etranslate( 'Use the Edit button to make changes.', true) ?>')"/>
    <br />
    <?php if ( $globals_found) echo '*' . 
     translate( 'Global Categories can not be changed')?>
   <input  type="hidden" name="cat_id" id="entry_categories" value="<?php echo $catList ?>" />
     </td></tr>
  <tr><td colspan="2">
    
  </td></tr>
<tr style="vertical-align:top;"><td colspan="2">
 <input type="submit" value="<?php etranslate( 'Save' );?>" />
</td></tr>
</table>
</form>
<?php }
print_trailer(); ?>
</body>
</html>
