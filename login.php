<?php
@session_start();
foreach ( $_SESSION as $key => $value ) {
  $dummy[$key] = $value; // Copy to a dummy array.
}
if ( ! empty ( $dummy ) ) {
  foreach ( $dummy as $key => $value ) {
    if ( substr ( $key, 0, 6 ) == 'webcal' )
      unset ( $_SESSION[$key] );
  }
}
// PHP 4.1.0 may have issues with the above code.
unset ( $_SESSION['webcal_login'] );
unset ( $_SESSION['webcalendar_session'] );

include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.php';

$WebCalendar = new WebCalendar( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;
include_once 'includes/access.php';
include 'includes/gradient.php';

$WebCalendar->initializeSecondPhase();

load_global_settings();

// Set this true to show "no such user" or "invalid password" on login failures.
$showLoginFailureReason = (!empty($settings['mode']) && $settings['mode'] = 'dev');
$message = '';

if ( ! empty ( $last_login ) )
  $login = '';

if ( empty ( $webcalendar_login ) )
  $webcalendar_login = '';

if ( $REMEMBER_LAST_LOGIN == 'Y' && empty ( $login ) )
  $last_login = $login = $webcalendar_login;

load_user_preferences ( 'guest' );

$WebCalendar->setLanguage();

// Look for action=logout.
$logout = false;
$action = getGetValue('action');
if (!empty($action) && $action == 'logout') {
  $logout = true;
  $return_path = '';
  SetCookie('webcalendar_login', '', 0);
  SetCookie('webcalendar_last_view', '', 0);
  $message = translate('You have been logged out.');
} else
if (empty($return_path)) {
  // See if a return path was set.
  $return_path = get_last_view(false);
}

if (!empty($return_path)) {
  $url = $return_path = clean_whitespace($return_path);
} else {
  $url = 'index.php';
}

// If Application Name is set to "Title" then get translation.
// If not, use the Admin defined Application Name.
$appStr = generate_application_name();

$login = getPostValue('login');
$password = getPostValue('password');
$remember = getPostValue('remember');

// Calculate path for cookie.
if (empty($PHP_SELF)) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}

$cookie_path = str_replace('login.php', '', $PHP_SELF);

if ($single_user == 'Y' || $use_http_auth) {
  // No login for single-user mode or when using HTTP authorization.
  do_redirect('index.php');
} else {
  if (!empty($login) && !$logout) {
    $login = trim($login);
    $badLoginStr = translate('Illegal characters in login XXX.');

    if ($login != addslashes($login))
      die_miserable_death(
        str_replace('XXX', htmlentities($login), $badLoginStr)
      );

    if (empty($password)) {
      if (empty($error) && $showLoginFailureReason) {
        $error = translate('You must provide a password.');
      } else if (empty($error)) {
        $error = translate('Invalid login');
      }
    } else if (user_valid_login($login, $password)) {
      user_load_variables($login, '');

      $salt = chr(rand(ord('A'), ord('z')))
        . chr(rand(ord('A'), ord('z')));
      $encoded_login = encode_string($login . '|' . crypt($password, $salt));
      // If $remember, set login to expire in 365 days.
      $timeStr = (!empty($remember) && $remember == 'yes'
        ? time() + 31536000 : 0);
      SetCookie('webcalendar_session', $encoded_login, $timeStr, $cookie_path);

      // The cookie "webcalendar_login" is provided as a convenience to other
      // apps that may wish to know what was the last calendar login,
      // so they can use week_ssi.php as a server-side include.
      // As such, it's not a security risk to have it un-encoded since it is not
      // used to allow logins within this app. It is used to load user
      // preferences on the login page (before anyone has logged in)
      // if $REMEMBER_LAST_LOGIN is set to "Y" (in admin.php).
      SetCookie('webcalendar_login', $login, $timeStr, $cookie_path);

      if (!empty($GLOBALS['newUserUrl'])) {
        $url = $GLOBALS['newUserUrl'];
      }

      do_redirect($url);
    } else {
      // Invalid login.
      if (empty($error) || !$showLoginFailureReason) {
        $error = translate('Invalid login', true);
        echo "ERROR: $error"; exit;
      }

      activity_log(
        0,
        'system',
        '',
        LOG_LOGIN_FAILURE,
        str_replace(
          ['XXX', 'YYY'],
          [$login, $_SERVER['REMOTE_ADDR']],
          translate('Activity login failure')
        )
      );
    }
  } else {
    // No login info... just present empty login page.
    //$error = "Start";
  }
  // Delete current user.
  SetCookie('webcalendar_session', '', 0, $cookie_path);
  // In older versions, the cookie path had no trailing slash and NS 4.78
  // thinks "path/" and "path" are different, so the line above does not
  // delete the "old" cookie. This prohibits the login. So we also delete the
  // cookie with the trailing slash removed.
  if (substr($cookie_path, -1) == '/') {
    SetCookie('webcalendar_session', '', 0, substr($cookie_path, 0, -1));
  }
}
echo send_doctype($appStr);
?>

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<!-- Tiny color: https://github.com/bgrins/TinyColor/ -->
<script src="includes/js/tinycolor.js"></script>
<!-- Readable: https://github.com/aramk/readable-color -->
<script src="includes/js/readable.js"></script>
<link href="css_cacher.php" rel="stylesheet" />
<link href="includes/css/styles.css" rel="stylesheet" />

<?php

// Print custom header (since we do not call print_header function).
if ( ! empty ( $CUSTOM_SCRIPT ) && $CUSTOM_SCRIPT == 'Y' ) {
  echo load_template ( $login, 'S' );
}
?>
</head>
<body id="login">
<div class="container">
<?php
// Print custom header (since we do not call print_header function).
if ( ! empty ( $CUSTOM_HEADER ) && $CUSTOM_HEADER == 'Y' ) {
  echo load_template ( $login, 'H' );
}
?>
<div id="login-container" class="container">
<div class="row">
  <form id="login-form" class="form" action="login.php" method="post">
    <div class="row justify-content-md-center">
      <h3><?php echo htmlentities($appStr); ?> Login</h3>
    </div>
  <?php if ( ! empty ( $message )) { ?>
    <div class="alert alert-info" role="alert">
      <?php echo $message; ?>
    </div>
  <?php } ?>
  <?php if ( ! empty ( $error )) { ?>
    <div class="alert alert-warning" role="alert">
      <?php echo $error; ?>
    </div>
  <?php } ?>
    <div class="form-group row">
      <label for="login" class="text-info">Username:</label><br>
      <input type="text" name="login" id="user" class="form-control">
    </div>
    <div class="form-group row">
      <label for="password" class="text-info">Password:</label><br>
      <input type="password" name="password" id="password" class="form-control">
    </div>
    <div class="form-group form-check row">
      <input type="checkbox" class="form-check-input" id="remember-me">
      <label class="form-check-label" for="exampleCheck1">Remember me</label>
    </div>
    <div class="form-group row justify-content-md-center">
      <button type="submit" class="btn btn-primary">Submit</button>
    </div>
                
    <?php // Non-user calendars
      $nulist = get_nonuser_cals();
      $accessStr = translate ( 'Access XXX calendar' );
      for ( $i = 0, $cnt = count ( $nulist ); $i < $cnt; $i++ ) {
        if ( $nulist[$i]['cal_is_public'] == 'Y' ) {
          echo '<div id="form_' . $nulist[$i]['cal_login'] . '" class="form-group row">' .
            '<a class="nav" href="nulogin.php?login=' . $nulist[$i]['cal_login'] . '">'
            . str_replace ( 'XXX', $nulist[$i]['cal_fullname'], $accessStr )
            . '</a></div>';
        }
      }
      // Self registration
      if ( ! empty ( $ALLOW_SELF_REGISTRATION ) && $ALLOW_SELF_REGISTRATION == 'Y' ) {
        // We can limit what domain is allowed to self register.
        // $self_registration_domain should have this format  "192.168.220.0:255.255.240.0";
        $valid_ip = validate_domain();
      
        if ( ! empty ( $valid_ip ) ) {
          echo '<div id="register-link" class="form-group row"><a href="register.php">'
           . translate ( 'Not yet registered? Register here!' ) . '</a></div>';
        }
      }
    ?>

  </form>
</div>
</div>

<br>

<?php
echo '<div id="webcalendarVersion"><a href="' . $PROGRAM_URL . '" target="_blank" id="programname">'
    . $PROGRAM_NAME . '</a></div>';

// Print custom trailer (since we do not call print_trailer function).
if ( ! empty ( $CUSTOM_TRAILER ) && $CUSTOM_TRAILER == 'Y' ) {
  echo load_template ( $login, 'T' );
}
?>
</div>

<?php
// TODO use variable from init.php for these version numbers
?>
<script src="includes/js/jquery/jquery-3.5.1.slim.min.js"></script>
<script src="includes/js/bootstrap/bootstrap-4.6.0.bundle.min.js"></script>
</body>
</html>
