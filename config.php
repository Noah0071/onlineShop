<?php

//comnect database ด้วย my SQL
$host = "localhost";
$username = "root";
$password = "";
$database = "onlineshop";

$dns = "mysql:host=$host;dbname=$database";

//   $conn = new mysqli($host,$username,$password,$database,);

//   if($conn -> connect_error){
//       die(" เชื่อมต่อไม่สำเร็จ : " . $conn->connect_error);
//   }else{
//       echo "เชื่อมต่อสำเร็จ";
//  }

//connect database ด้วย PDO
try {
    //$conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn = new PDO($dns, $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //echo " PDO : เชื่อมต่อสำเร็จ ";
} catch (PDOException $e) {
    //echo " เชื่อมต่อไม่สำเร็จ: " .$e->getMessage();
}













?>