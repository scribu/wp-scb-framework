<?php

foreach ( array(
	'Util', 'Options', 'Forms', 'Table',
	'Widget', 'AdminPage', 'BoxesPage',
	'Cron', 'Hooks',
) as $name ) {
	require __DIR__ . "/$name.php";
}

unset( $name );

