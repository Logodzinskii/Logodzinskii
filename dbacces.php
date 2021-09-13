<?php
//соединяемся с базой данных

//Подключаемся к БД Хост, Имя пользователя MySQL, его пароль, имя нашей базы

    $connect = new mysqli("localhost", "u643288077_myfunnyadmin", "6^f;yZPW]F", "u643288077_myfunnybant");
    $connect->query("SET NAMES 'utf8' ");


//Кодировка данных получаемых из базы

