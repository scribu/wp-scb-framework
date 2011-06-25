<?php

class scbHooks {

	public static function add( $class ) {
		self::_do( 'add_filter', $class );
	}

	public static function remove( $class ) {
		self::_do( 'remove_filter', $class );
	}

	public static function debug( $class ) {
		echo "<pre>";
		self::_do( array( __CLASS__, '_print' ), $class );
		echo "</pre>";
	}

	private static function _print( $tag, $callback, $prio, $argc ) {
		if ( is_object( $callback[0] ) )
			$class = '$' . get_class( $callback[0] );
		else
			$class = "'" . $callback[0] . "'";

		$func = " array( $class, '$callback[1]' )";

		echo "add_filter( '$tag', $func, $prio, $argc );\n";
	}

	private static function _do( $action, $class ) {
		$reflection = new ReflectionClass( $class );

		foreach ( $reflection->getMethods() as $method ) {
			if ( $method->isPublic() && !$method->isConstructor() ) {
				$comment = $method->getDocComment();

				$hook = preg_match( '/@hook:?\s+(.+)/', $comment, $matches ) ? $matches[1] : $method->name;
				$priority = preg_match( '/@priority:?\s+([0-9]+)/', $comment, $matches ) ? $matches[1] : 10;

				call_user_func( $action, $hook, array( $class, $method->name ), $priority, $method->getNumberOfParameters() );
			}
		}
	}
}

