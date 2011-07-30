<?php

class scbHooks {
	private static $mangle_name;

	public static function add( $class ) {
		self::_do( 'add_filter', $class );
	}

	public static function remove( $class ) {
		self::_do( 'remove_filter', $class );
	}

	public static function debug( $class, $mangle_name = false ) {
		self::$mangle_name = $mangle_name;

		echo "<pre>";
		self::_do( array( __CLASS__, '_print' ), $class );
		echo "</pre>";
	}

	private static function _print( $tag, $callback, $prio, $argc ) {
		$static = !is_object( $callback[0] );

		if ( self::$mangle_name )
			$class = $static ? '__CLASS__' : '$this';
		else if ( $static )
			$class = "'" . $callback[0] . "'";
		else
			$class = '$' . get_class( $callback[0] );

		$func = " array( $class, '$callback[1]' )";

		echo "add_filter( '$tag', $func";

		if ( $prio != 10 || $argc > 1 ) {
			echo ", $prio";

			if ( $argc > 1 )
				echo ", $argc";
		}

		echo " );\n";
	}

	private static function _do( $action, $class ) {
		$reflection = new ReflectionClass( $class );

		foreach ( $reflection->getMethods() as $method ) {
			if ( $method->isPublic() && !$method->isConstructor() ) {
				$comment = $method->getDocComment();

				if ( preg_match( '/@nohook[ \t\*\n]+/', $comment ) ) {
					continue;
				}

				$hook = preg_match( '/@hook:?\s+(.+)/', $comment, $matches ) ? $matches[1] : $method->name;
				$priority = preg_match( '/@priority:?\s+([0-9]+)/', $comment, $matches ) ? $matches[1] : 10;

				call_user_func( $action, $hook, array( $class, $method->name ), $priority, $method->getNumberOfParameters() );
			}
		}
	}
}

