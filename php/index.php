<?php
if ($_SERVER['SERVER_NAME'] != 'mealbookers.net') {
    header("Location: http://mealbookers.net");
    die;
}
?><!DOCTYPE html>
<html ng-app="Mealbookers">
<head>
	<meta charset="utf-8">
	<title ng-bind="title">Mealbookers</title>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
    <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/webfont/1.4.7/webfont.js"></script>
    <script type="text/javascript" src="lib/jquery.equalheights.min.js"></script>
	<script type="text/javascript" src="lib/jquery.cookie.js"></script>
    <script type="text/javascript" src="lib/sortable.js"></script>
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.10/angular.min.js"></script>
	<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.10/angular-resource.min.js"></script>
	<script type="text/javascript" src="//angular-ui.github.io/ui-router/release/angular-ui-router.js"></script>
	<script type="text/javascript" src="lib/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/app.js"></script>
	<script type="text/javascript" src="js/controllers.js"></script>
	<script type="text/javascript" src="js/directives.js"></script>
	<script type="text/javascript" src="js/filters.js"></script>
	<script type="text/javascript" src="js/services.js"></script>
	<script type="text/javascript" src="js/localization.js"></script>
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/main.css" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#D6E2FF">
    <link rel="shortcut icon" sizes="16x16" href="images/icon16.png">
    <link rel="apple-touch-icon" href="images/icon57.png" sizes="57x57">
    <link rel="apple-touch-icon" href="images/icon72.png" sizes="72x72">
    <link rel="apple-touch-icon" href="images/icon76.png" sizes="76x76">
    <link rel="apple-touch-icon" href="images/icon114.png" sizes="114x114">
    <link rel="apple-touch-icon" href="images/icon120.png" sizes="120x120">
    <link rel="apple-touch-icon" href="images/icon144.png" sizes="144x144">
    <link rel="apple-touch-icon" href="images/icon152.png" sizes="152x152">
    <link rel="shortcut icon" sizes="196x196" href="images/icon196.png">
    <meta name="msapplication-TileImage" content="images/icon144.png">
    <meta name="msapplication-TileColor" content="#D6E2FF">
    <link rel="shortcut icon" sizes="16x16" href="images/icon16.png">

</head>
<body>
	<!-- Page content -->
	<div ui-view id="page"></div>
</body>
</html>