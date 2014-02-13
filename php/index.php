<!DOCTYPE html>
<html ng-app="Mealbookers">
<head>
	<meta charset="utf-8">
	<title ng-bind="title">Mealbookers</title>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.10/angular.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.10/angular-route.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.10/angular-resource.min.js"></script>
	<script type="text/javascript" src="lib/angular-ui-router.js"></script>
	<script type="text/javascript" src="lib/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/app.js"></script>
	<script type="text/javascript" src="js/controllers.js"></script>
	<script type="text/javascript" src="js/directives.js"></script>
	<script type="text/javascript" src="js/filters.js"></script>
	<script type="text/javascript" src="js/services.js"></script>
	<script type="text/javascript" src="js/localization.js"></script>
	<link rel="stylesheet" href="css/main.css" />
	<link rel="stylesheet" href="css/bootstrap.min.css" />
	<link rel="stylesheet" href="css/bootstrap-theme.min.css" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
	<!-- Page content -->
	<div ui-view></div>
</body>
</html>