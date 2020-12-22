<?php
  // This code uses the database created by dbConenct.php to create a secure
  // user login and signup functionality. Once a user has been authenticated,
  // they are redirected to userPage.php
    session_start();
    require_once "dbConnect.php";
    create_database($hn, $un, $pw, $db, $table1, $table2);
    $conn = new mysqli($hn, $un, $pw, $db);
    if ($conn->connect_error) die (mysql_error());
    addUser($conn, $table1);

    echo <<<_END
      <html><body>
      <h1> Login Portal </h1>
      <form method='post' enctype='multipart/form-data'>
      Username: <p><input type='username' name='userName'  placeholder='Type Username'></p>
      Password: <p><input type='password' name='passWord'  placeholder='Type Password'></p>
      <p><input type='submit' name='loginButton' value='Log in'></p>
      Or, sign up here:
      </form>
      <form method='post'>
      <p><input type='submit' name='signUp' value='Sign up'></p>
      </form>
      <br>
    _END;


    if (isset($_POST['signUp'])) {
      echo <<<_END
        <h1>Sign Up</h1>
        <form method='post' enctype='multipart/form-data'>
        <p><input type='email' name='emaiL' value='' placeholder='Email id'></p>
        <p><input type="text" name="useR" value='' placeholder='Username'></p>
        <p><input type="password" name="pworD" value='' placeholder="Password"></p>
        <p class="submit"><input type="submit" name='en' value="SignUp"></p>
        </form>
        _END;
      }


    if (isset($_POST['loginButton'])) {
      if ((!$_POST['userName']) || (!$_POST['passWord'])) {
        echo "Please input a value in every field.";
      }
      else {
        $newUser=sanitizer($conn, $_POST["userName"]);
        $newPass=sanitizer($conn, $_POST["passWord"]);

        $q = "SELECT * FROM $table1 WHERE userName = '$newUser'";
        $output = $conn->query($q);
        if (!$output) die (mysql_error());
        $r = $output->num_rows;
        if ($r==0)
          echo "Wrong Username or Password!";
          else {
            $output->data_seek(0);
            $row = $output->fetch_array(MYSQLI_ASSOC);

            if (password_verify($newPass, $row['password'])) {
              $_SESSION['username']=$newUser;
              $_SESSION['password']=$newPass;
              //$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
              header("Location: userPage.php");
            }
            else {
              echo "Wrong Username or Password!";
            }
          }
        }
      }

      function addUser($conn, $table1) {
        if (isset($_POST['en'])) {
          if ((!$_POST['emaiL']) || (!$_POST['useR']) || (!$_POST['pworD'])) {
            echo "Please input a value in every field.";
          }
          else {
            $newEmail=sanitizer($conn, $_POST["emaiL"]);
            $newUser= sanitizer($conn, $_POST["useR"]);
            $newPass= password_hash(sanitizer($conn, $_POST["pworD"]),PASSWORD_DEFAULT);
            $q="SELECT * FROM $table1 WHERE username='$newUser'";
            $output=$conn->query($q);
            if (!$output) die (mysql_error());
            $r = $output->num_rows;
            if ($r==0) {
              $query = $conn->prepare("INSERT INTO $table1 VALUES (?, ?, ?)");
              $query->bind_param('sss', $newEmail, $newUser, $newPass);
              $query->execute();
              echo "Your profile has been added. <br>";
              $query->close();
            }
            else echo "<br>Username unavailable. Please try again.";
          }
        }
      }

      function mysql_error() {
        echo "<br> Oops! Something went wrong. <br>";
      }
      function sanitizer($conn, $string) {
          return htmlentities(sanitize($conn, $string));
        }
      function sanitize($conn, $string) {
        if(get_magic_quotes_gpc()) $string = stripslashes($string);
        return $conn->real_escape_string($string);
      }

?>
