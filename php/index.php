<!DOCTYPE html>
<html ng-app="Mealbookers">
<head>
	<meta charset="utf-8">
	<title ng-bind="title">Mealbookers</title>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.10/angular.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.10/angular-route.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/angularjs/1.2.10/angular-resource.min.js"></script>
	<link rel="stylesheet" href="css/main.css" />
	<link rel="stylesheet" href="css/bootstrap.min.css" />
	<link rel="stylesheet" href="css/bootstrap-theme.min.css" />
	<script type="text/javascript" src="lib/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/app.js"></script>
	<script type="text/javascript" src="js/controllers.js"></script>
	<script type="text/javascript" src="js/directives.js"></script>
	<script type="text/javascript" src="js/filters.js"></script>
	<script type="text/javascript" src="js/services.js"></script>
	<script type="text/javascript" src="js/localization.js"></script>
	<script type="text/javascript" src="lib/angular-ui-router.js"></script>
</head>
<body>
	<!-- Page content -->
	<div ui-view></div>
	
</body>
</html>