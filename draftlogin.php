<DOCTYPE html>
<html>
<head>
<title>Login</title>
</head>
<body>
<form action="" method="POST">
<input type="email" name="email" placeholder="Email">
<br><br>
<input type="password" name="password" placeholder="Password">
<br><br>
<input type="submit" name="login_btn" placeholder="Password">
</form> </body> </html>

<?php
if(isset($_POST['login_btn']))
    {
        $email=$_POST['email'];
        $loginpass=$_POST['password'];

        $select="SELECT * FROM users WHERE Username= '$email' && Password=
        '$loginpass'";
        
        $query=mysqli_query($config,$select);
        $row=mysqli_num_rows($query);
        echo $row;
       
    }?>
