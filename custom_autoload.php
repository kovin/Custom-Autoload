<?
// Set autoloader
spl_autoload_register('custom_autoload');

function custom_autoload($search_class){
	if (class_exists($search_class, FALSE))
	{
		return TRUE;
	}

	$paths	= array();
	$type	= FALSE;
	
	/**
	 * Split $search_class in class name, class 
	 * type(Controller, Model, etc) and paths information
	 */ 
	if(preg_match( '#^(.+)_([^_]+)$#', $search_class, $match) )
	{
		$class		= $match[2];
		$paths		= explode( '_', $match[1] );
		$known_types= array( 'Core', 'Controller', 'Model', 'Driver' );
		if( in_array( $class, $known_types ) ){
			$type = $class;
			$class= end($paths);
			$paths= array_slice( $paths, 0, count($paths)-1 );
		}
	}
	
	/*
	 * Search for the class file in different paths
	 * eg: if $search_class has this structure
	 * 		Path_SubPath_Class_Name_Controller
	 * it will look for the following files and in
	 * the following order
	 * 		- controllers/path_subpath_class_name.php
	 * 		- controllers/path/subpath_class_name.php	
	 * 		- controllers/path/subpath/class_name.php (This should be the file)
	 *		- controllers/path/subpath/class/name.php
	 */
	for( $i = 0; $i <=count($paths) ; $i++ )
	{
		// In this iteration will look in this path
		$full_path		= implode( '/', array_slice($paths, 0, $i) );
		
		$class_prefix	= implode( '_', array_slice( $paths, $i ) );
		// In this iteration will look for this class name
		$full_class		= ( $class_prefix ? $class_prefix.'_' : '') . $class;
		
		switch($type){
			case 'Core':
				$full_path = strtolower( 'libraries' . ( $full_path ? '/'.$full_path : '' ) );
				if ( $filename = Kohana::find_file( $full_path, $full_class ) )
				{
					require $filename;
					return include_class( $search_class, $full_class.'_Core' );
				}
			break;
			case 'Controller':
				$full_path	= strtolower( 'controllers' . ( $full_path ? '/'.$full_path : '' ) );
				$file		= strtolower( $full_class );

				if ( $filename = Kohana::find_file( $full_path, $file ) )
				{
					require $filename;
					return include_class( $search_class, $full_class.'_Controller' );
				}
			break;
			case 'Model':
				$full_path	= strtolower( 'models' . ( $full_path ? '/'.$full_path : '' ) );
				$file		= strtolower( $full_class );

				if ( $filename = Kohana::find_file( $full_path, $file ) )
				{
					require $filename;
					return include_class( $search_class, $full_class.'_Model' );
				}				
			break;
			case 'Driver':
				$full_path = 'libraries/drivers' . ( $full_path ? '/'.$full_path : '' );
				if ( $filename = Kohana::find_file( $full_path, $full_class ) )
				{
					require $filename;
					return include_class( $search_class, $full_class );
				}	
			break;
			default:
				/* 
				 * This could be either a library or a helper, but libraries must
				 * always be capitalized, so we check if the first character is
				 * uppercase. If it is, we are loading a library, not a helper.
				 */
				if( $full_class[0] < 'a' ){
					$full_path = strtolower( 'libraries' . ( $full_path ? '/'.$full_path : '' ) );	
				}else{
					$full_path = strtolower( 'helpers' . ( $full_path ? '/'.$full_path : '' ) );
					$full_class= strtolower( $full_class );
				}
				
				if ( $filename = Kohana::find_file( $full_path, $full_class) )
				{
					require $filename;
					return include_class( $search_class, $full_class );
				}	
			break;	
		}
	}
	
	return FALSE;
}

function include_class($search_class, $found_class){
	if( class_exists( $found_class, FALSE) ){
		if( $search_class == $found_class ){
			return TRUE;
		}
		 
		// Class extension to be evaluated
		$extension = 'class '.$search_class.' extends '.$found_class.' { }';

		// Start class analysis
		$found_class = new ReflectionClass($found_class);

		if ($found_class->isAbstract())
		{
			// Make the extension abstract
			$extension = 'abstract '.$extension;
		}

		/*
		 * Transparent class extensions are handled using eval. This is
		 * a disgusting hack, but it gets the job done.
		 */ 
		eval($extension);
		
		return TRUE;
	}else{
		return FALSE;
	}
}