<?php
  // This code is where the user is taken after logging in at userLogin.php
  // if the user is not logged in when accessing this page, they will be prompted
  // to sign in using HTTP authentication. Until logged in, the page is inaccessible.
  //
  // Once user authentication is complete, the user will be able to upload
  // "dictionary" .txt files to the MYSQL database, then enter text to be translated
  // using any of their previously uploaded dictionaries.
  session_start();
  require_once "dbConnect.php";
  create_database($hn, $un, $pw, $db, $table1, $table2);
  $conn = new mysqli($hn, $un, $pw, $db);
  if ($conn->connect_error) die (mysql_error());

  authenticate_login($conn, $table1, $table2);

  function authenticate_login($conn, $table1, $table2){

      if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])){
          $username = sanitizer($conn,$_SERVER['PHP_AUTH_USER']);
          $password = sanitizer($conn,$_SERVER['PHP_AUTH_PW']);

          $query = "SELECT * FROM $table1 WHERE userName = '$username'";
          $result = $conn->query($query);
          if (!$result) die (mysql_error());
          $r = $result->num_rows;
          if($r>0) {
            $result->data_seek(0);
            $row = $result->fetch_array(MYSQLI_ASSOC);
            if (password_verify($password, $row['password'])) {
              startUploader($conn, $table2);
              dictionarySelect($conn, $table2);
            }
          }
          else {
            header('WWW-Authenticate: Basic realm="Restricted Section"');
            header('HTTP/1.0 401 Unauthorized');
            die ("Please enter your username and password");
          }
        }
        else if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
          $_SERVER['PHP_AUTH_USER'] = $_SESSION['username'];
          $_SERVER['PHP_AUTH_PW'] = $_SESSION['password'];
          authenticate_login($conn, $table1, $table2);
        }
        else die(mysql_error());
      }

    function mysql_error() {
      header('WWW-Authenticate: Basic realm="Restricted Section"');
      header('HTTP/1.0 401 Unauthorized');
      die ("Please enter your username and password");
    }


      function startUploader($conn, $table2) {
      echo <<<_END
        <h1>User Portal</h1>
        <p> Upload a .txt file dictionary</p>
        <p> File must be formatted in one line with a space between translations, e.g. one uno two dos
        <form method='post' enctype="multipart/form-data" >
        <p><input type="text" name="filename" value="" placeholder="File Name"></p>
        <p><input type="file" name='filepath' size='20'></p>
        <p><input type="submit" name='uploader' value="Upload"></p>
        </form>
      _END;


      if($_FILES && isset($_POST['filename'])) {
          //$newUser = sanitizer($conn, $newUser);
          $newUser = $_SESSION['username'];
          $newName = sanitizer($conn, $_POST["filename"]);
          $newFile = sanitizer($conn, $_FILES['filepath']['name']);

          if($_FILES['filepath']['type'] == 'text/plain') {
            $ext = 'txt';
          }
          else {
            $ext = '';
          }

          if ($ext) {
              $data=file_get_contents($newFile);
              $data = sanitizer($conn, $data);
              $data = strtolower($data);
              $query=$conn->prepare("INSERT INTO $table2 VALUES (?, ?, ?)");
              $query->bind_param('sss', $newUser, $newName, $data);
              $query->execute();
              echo "The file has been uploaded!<br>";
              $query->close();

              echo "Uploaded file '$newName'.<br>";
            }
          else echo "'$newName' is not an accepted file";
        }
      }

    function translate($conn, $table2, $data, $text) {
        $arr = explode(" ", $data);
        $arr2 = explode(" ", $text);
        $output = "";
        if (empty($data)) {
          $output = $text;
        }
        else {
        foreach($arr2 as $word) {
          for ($i=0; $i<count($arr)-1; $i=$i+2) {
            if ($arr[$i] == $word) {
              $output .= $arr[$i+1] . " ";
              break;
            }
            if ($i == count($arr)-2) {
              $output .= $word . " ";
            }
          }
        }
      }
        echo "TRANSLATION: " . $output . "<br><br>";
        echo "DICTIONARY: <br>";
        for($j=0; $j<count($arr)-1; $j=$j+2) {
          echo $arr[$j] . " " . $arr[$j+1] . "<br>";
        }

    }


    function dictionarySelect($conn, $table2) {
      echo "<p> Or, start translating with your uploaded dictionaries: </p>";
          $newUser = $_SESSION['username'];
          $query = "SELECT fileName FROM $table2 WHERE user = '$newUser'";
          $result = mysqli_query($conn, $query);
          if (!$result) die(mysql_error());
          echo "<form method='post' enctype='multipart/form-data' >";
          echo '<label>Dictionaries:';
          echo '<select name="dict">';
          echo '<option value = "default">Choose Dictionary</option>';
          $num_results = mysqli_num_rows($result);
          for ($i=0;$i<$num_results;$i++) {
            $row = mysqli_fetch_array($result);
            $name = $row['fileName'];
            echo '<option value="' .$name. '">' .$name. '</option>';
          }
          echo "</select>";
          echo "</label>";
          echo "</end>";
          echo <<<_END
            <form method='post' enctype="multipart/form-data" >
            <p><input type="text" name="original" placeholder="Enter text to be translated"></p>
            <p><input type="submit" name='translator' value="Translate"></p>
            </form>
          _END;

        if (isset($_POST['translator']) && isset($_POST['original'])) {
          if (isset($_POST['dict'])) {
            //$query = "SELECT fileName FROM $table2 WHERE user = '$newUser'";

            $search = sanitizer($conn, $_POST['dict']);
            if ($search == "default") {
              $text = sanitizer($conn, $_POST['original']);
              $data = "";
              translate($conn, $table2, $data, $text);
            }
            else {
              $query = "SELECT fileContent FROM $table2 WHERE user = '$newUser' AND fileName = '$search'";
              $result = mysqli_query($conn, $query);
              if (!$result) die(mysql_error());
              $num_results = mysqli_num_rows($result);
              $r = $result->num_rows;
              if ($r==0)
                mysql_error();
              else {
                $result->data_seek(0);
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $data = sanitizer($conn, $row['fileContent']);

                $text = sanitizer($conn, $_POST['original']);
                $text = strtolower($text);

                translate($conn, $table2, $data, $text);

              }
            }
          }
        }
    }



    function sanitizer($conn, $string) {
        return htmlentities(sanitize($conn, $string));
    }
    function sanitize($conn, $string) {
        if(get_magic_quotes_gpc()) $string = stripslashes($string);
        return $conn->real_escape_string($string);
    }

?>
