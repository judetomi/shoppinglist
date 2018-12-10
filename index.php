<?php
	require_once('app.php');

	$app = new ShoppingList();
	$lists = $app->getLists();

	if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
	{
		switch ($_POST['action'])
		{
			case 'new':
				$app->addItem();
				break;
			case 'new_list':
				$app->addList();
				break;
			case 'delete':
				$app->deleteItem();
				break;
			case 'clear':
				$app->clearList();
				break;
			case 'delete_list':
				$app->deleteList();
				break;
			case 'update_position':
				$app->updatePosition();
				break;
			default:
				$app->getList();
		}
	}

	$activeList = (isset($_GET['id'])) ? $_GET['id'] : 1

 ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Ostoslista</title>
  <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Open+Sans:300,400,700">
	<link rel="stylesheet" href="css/themes/default/jquery.mobile-1.4.5.min.css">
	<link rel="stylesheet" href="_assets/css/jqm-demos.css">
	<link rel="stylesheet" href="css/app.css">
	<script src="js/jquery.js"></script>
	<script src="http://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
	<script src="_assets/js/index.js"></script>
	<script src="js/jquery.mobile-1.4.5.min.js"></script>
	<script src="https://cdn.auth0.com/js/auth0/9.3.1/auth0.min.js"></script>
  <script src="js/auth0-variables.js"></script>
  <script src="js/app.js"></script>
	<script src="js/mustache.min.js"></script>
	<script src="js/jquery.ui.touch-punch.min.js"></script>
</head>
<body>
	<div data-role="page" id="shoppinglist-page" data-title="Ostoslista" data-url="shoppinglist-page">
			<div data-role="header" data-position="fixed" data-theme="b">
			<h1>&nbsp;</h1>
			<div id="action-buttons" data-role="control-group" data-type="horizontal" class="ui-mini ui-btn-left">
				<a id="btn-login" class="ui-btn ui-btn-icon-right ui-icon-lock">Login</a>
				<a id="btn-logout" class="ui-btn ui-btn-icon-right ui-icon-action">Logout</a>
				<a href="#addItem" id="addNewItem" data-rel="popup" data-position-to="window" data-transition="pop" class="ui-btn ui-btn-icon-right ui-icon-plus">Lisää</a>
				<a id="refresh" class="ui-btn ui-btn-icon-right ui-icon-back">Päivitä</a>
				<a href="#addList" id="addNewList" data-rel="popup" data-position-to="window" data-transition="pop" class="ui-btn ui-btn-icon-right ui-icon-bars">Uusi lista</a>
			</div>
			    <div class="navigation" data-role="navbar" data-iconpos="right">
						<ul>
							<li>
								<select id="shopLists" name="list" data-native-menu="false">
									<?php
										foreach($lists as $list)
										{
											$selected = ($list['id'] == $activeList) ? 'selected' : '';
											echo '<option id="list-' . $list['id'] . '" value="' . $list['id'] . '" ' . $selected . '>' . $list['title'] . '</option>';
										}
									 ?>
								</select>
							</li>
						</ul>
			    </div><!-- /navbar -->
			</div><!-- /header -->
	    <div role="main" class="ui-content">
	        <ul id="list" class="touch" data-role="listview" data-icon="false" data-split-icon="delete"></ul>
	    </div><!-- /content -->
	    <div id="confirm" class="ui-content" data-role="popup" data-theme="a">
	        <p id="question">Oletko varma että haluat poistaa:</p>
	        <div class="ui-grid-a">
	            <div class="ui-block-a">
	                <a id="yes" class="ui-btn ui-corner-all ui-mini ui-btn-a" data-rel="back">Kyllä</a>
	            </div>
	            <div class="ui-block-b">
	                <a id="cancel" class="ui-btn ui-corner-all ui-mini ui-btn-a" data-rel="back">Peruuta</a>
	            </div>
	        </div>
	    </div><!-- /popup -->
			<div id="confirm-new" class="ui-content" data-role="popup" data-theme="a">
	        <p id="question">Oletko varma että haluat poistaa kaikki listan kohteet:</p>
	        <div class="ui-grid-a">
	            <div class="ui-block-a">
	                <a id="yes" class="ui-btn ui-corner-all ui-mini ui-btn-a" data-rel="back">Kyllä</a>
	            </div>
	            <div class="ui-block-b">
	                <a id="cancel" class="ui-btn ui-corner-all ui-mini ui-btn-a" data-rel="back">Peruuta</a>
	            </div>
	        </div>
	    </div><!-- /popup -->
			<div id="confirm-delete" class="ui-content" data-role="popup" data-theme="a">
	        <p id="question">Oletko varma että haluat poistaa listan ja kaikki listan kohteet:</p>
	        <div class="ui-grid-a">
	            <div class="ui-block-a">
	                <a id="yes" class="ui-btn ui-corner-all ui-mini ui-btn-a" data-rel="back">Kyllä</a>
	            </div>
	            <div class="ui-block-b">
	                <a id="cancel" class="ui-btn ui-corner-all ui-mini ui-btn-a" data-rel="back">Peruuta</a>
	            </div>
	        </div>
	    </div><!-- /popup -->
			<div id="dialog" class="ui-content" data-role="popup" data-theme="a">
	        <p id="question">Et voi poistaa oletuslistaa:</p>
	        <div class="ui-grid-a">
						<a id="cancel" class="ui-btn ui-corner-all ui-mini ui-btn-a" data-rel="back">OK</a>
	        </div>
	    </div><!-- /popup -->
			<div data-role="popup" id="addItem" data-theme="a" class="ui-corner-all">
    	<form action="./" method="post" id="addItemForm">
        	<div style="padding:10px 20px;">
            <h3 id="popUpTitle">Uusi artikkeli</h3>
            <label for="item_name" class="ui-hidden-accessible">Nimi:</label>
            <input name="item_name" id="item_name" value="" placeholder="Artikkelin nimi" data-theme="a" type="text">
            <label for="item_desc" class="ui-hidden-accessible">Kuvaus:</label>
            <textarea name="item_desc" id="item_desc" value="" placeholder="Lisätietoja" data-theme="a"></textarea>
						<label for="slider">Kappalemäärä:</label>
						<input name="item_qty" id="slider" value="1" min="0" max="30" type="range">
						<input type="hidden" name="csrf" value="<?= $_SESSION["token"]; ?>">
						<input type="hidden" name="id" id="id" value="">
            <button type="submit" id="addItemBtn" class="ui-btn ui-corner-all ui-shadow ui-btn-b ui-btn-icon-left ui-icon-check">Lisää</button>
        	</div>
    	</form>
		</div><!-- /add new item-->
		<div data-role="popup" id="addList" data-theme="a" class="ui-corner-all">
		<form action="./" method="post" id="addListForm">
				<div style="padding:10px 20px;">
					<h3 id="popUpTitle">Uusi Lista</h3>
					<label for="list_name" class="ui-hidden-accessible">Nimi:</label>
					<input name="list_name" id="list_name" value="" placeholder="Listan nimi" data-theme="a" type="text">
					<input type="hidden" name="csrf" value="<?= $_SESSION["token"]; ?>">
					<button type="submit" id="addListBtn" class="ui-btn ui-corner-all ui-shadow ui-btn-b ui-btn-icon-left ui-icon-check">Lisää</button>
				</div>
		</form>
		</div><!-- /add new list-->

		<div class="navigation" data-role="footer" data-theme="b" style="overflow:hidden;">
		    <div data-role="navbar" data-iconpos="top">
		        <ul>
		            <li><a id="clearList" href="#" data-icon="recycle">Tyhjennä lista</a></li>
								<li><a id="deleteList" href="#" data-icon="delete">Poista lista</a></li>
								<li><a id="sortList" href="#" data-icon="edit">Muuta järjestystä</a></li>
								<!--li><a id="importList" href="#" data-icon="cloud">Tuo lista</a></li //-->
		        </ul>
		    </div><!-- /navbar -->
		</div><!-- /footer -->
	</div>
</body>
<script id="template" type="x-tmpl-mustache">
<li class="shoppinglistItem" data-id="{{itemid}}" data-position="{{position}}">
		<a href="#">
				<h3>{{title}}</h3>
				<p>{{description}}</p>
				<p class="ui-li-aside"><strong>{{qty}}</strong>kpl</p>
		</a>
		<a href="#" class="delete">Poista</a>
</li>
</script>
<script id="list-template" type="x-tmpl-mustache">
	<option id="list-{{id}}" value="{{id}}">{{title}}</option>
</script>
</html>
